<#
.SYNOPSIS
    Deploy a published APES Intranet release to the Cloudron LAMP app, rolling
    back by itself if verification fails.

.DESCRIPTION
    In order:
      1. Refuses unless the tag exists, is a published GitHub release, and the
         ci check is green on its commit.
      2. Runs preflight.ps1 and stops on any FAIL.
      3. Takes a Cloudron backup and notes its id.
      4. Notes the live release, for a rollback.
      5. Builds the tag with build-release.ps1 (WampServer's PHP 8.4, Composer
         and host npm) and uploads it to /app/data/releases/<ReleaseId>.
      6. Checks the uploaded REVISION is the tag's commit.
      7. Runs activate-release.sh, then restarts the app.
      8. Verifies /healthz (200, "status":"ok", revision = first 12 characters
         of the commit), the queue worker and scheduler processes, and the logs.
      9. On any failure from step 7 on: rollback-release.sh to the earlier
         release, restart, verify again, and exit 1.
     10. Prints a summary to paste on the ship issue.

    Works in Windows PowerShell 5.1. Never prints .env values or APP_KEY.

.PARAMETER ForceVerifyFailure
    Test only. Verifies against a deliberately wrong revision, so the new
    release is activated and then rolled back. Proves the rollback works.

.PARAMETER Php
    PHP 8.4 for the build. Defaults to WampServer's newest PHP 8.4.

.PARAMETER Composer
    Path to composer.phar. Defaults to the one next to composer on PATH.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\deploy.ps1 -Tag v0.0.2

.EXAMPLE
    ... deploy.ps1 -Tag v0.0.2 -Php C:\path\to\php.exe -Composer C:\path\to\composer.phar
#>
param(
    [Parameter(Mandatory = $true)][string]$Tag,
    [string]$App = '8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264',
    [string]$Url = 'https://intranet.apes.org.uk',
    [string]$Repo = 'APESCIC/APES.Intranet',
    [string]$Php = '',
    [string]$Composer = '',
    [switch]$ForceVerifyFailure
)

$ErrorActionPreference = 'Continue'
[Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12

$releases = '/app/data/releases'
$sha = ''
$id = ''
$previous = ''
$backupId = ''
$script:lastHealth = [pscustomobject]@{ Code = 0; Body = '' }
$script:restartedAt = [datetime]::UtcNow

function Restart-App {
    $script:restartedAt = [datetime]::UtcNow
    cloudron restart --app $App | Out-Host
}

function Write-Step([string]$Message) { Write-Host "==> $Message" }

function Write-Summary([string]$Outcome) {
    $previousId = if ($previous) { $previous.Split('/')[-1] } else { 'none' }
    Write-Output ''
    Write-Output '```text'
    Write-Output "Outcome:  $Outcome"
    Write-Output "Tag:      $Tag ($sha)"
    Write-Output "Release:  $id"
    Write-Output "Previous: $previousId"
    Write-Output "Backup:   $backupId"
    Write-Output "/healthz: $($script:lastHealth.Code) $($script:lastHealth.Body)"
    Write-Output '```'
}

function Stop-Deploy([string]$Message) {
    Write-Host "FAILED: $Message"
    Write-Summary "failed before activation: $Message"
    exit 1
}

# Runs a command inside the app. The '--' must stay quoted: Windows PowerShell
# 5.1 drops a bare -- before it reaches the cloudron CLI. Arguments must not
# need shell quoting; anything with $ or " belongs in a script pushed to /tmp.
function Invoke-App {
    cloudron exec --app $App '--' @args
}

function Get-Health {
    try {
        $response = Invoke-WebRequest -UseBasicParsing -TimeoutSec 15 -Uri "$Url/healthz"
        return [pscustomobject]@{ Code = [int]$response.StatusCode; Body = $response.Content.Trim() }
    }
    catch {
        $code = 0
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        return [pscustomobject]@{ Code = $code; Body = '' }
    }
}

function Test-Live([string]$ExpectedRevision) {
    $health = Get-Health
    for ($i = 0; $i -lt 24 -and $health.Code -ne 200; $i++) {
        Start-Sleep -Seconds 5
        $health = Get-Health
    }
    $script:lastHealth = $health
    if ($health.Code -ne 200) {
        Write-Host "  /healthz returned $($health.Code)"
        return $false
    }
    $json = $health.Body | ConvertFrom-Json
    if ($json.status -ne 'ok') {
        Write-Host "  /healthz status is $($json.status)"
        return $false
    }
    if ($json.revision -ne $ExpectedRevision) {
        Write-Host "  /healthz revision is $($json.revision), expected $ExpectedRevision"
        return $false
    }
    foreach ($worker in 'queue:work', 'schedule:work') {
        Invoke-App pgrep -f $worker | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "  no $worker process is running"
            return $false
        }
    }
    # run.sh stamps its lines with UTC HH:MM:SS; only lines since the restart count.
    $since = $script:restartedAt.AddSeconds(-60).ToString('HH:mm:ss')
    $now = [datetime]::UtcNow.ToString('HH:mm:ss')
    $logs = @(cloudron logs --app $App --lines 500)
    foreach ($worker in 'queue worker', 'scheduler') {
        $seen = $logs | Where-Object {
            $_ -match "\[run\.sh (\d\d:\d\d:\d\d)\] Starting $worker" -and ($Matches[1] -ge $since -or $since -gt $now)
        }
        if (-not $seen) {
            Write-Host "  the logs do not show 'Starting $worker' since the restart"
            return $false
        }
    }
    Write-Host "  /healthz: $($health.Body)"
    return $true
}

$repoRoot = (git -C $PSScriptRoot rev-parse --show-toplevel).Trim()
Push-Location $repoRoot
try {
    $started = [datetime]::UtcNow

    Write-Step "Checking release $Tag"
    git fetch --quiet --tags origin
    $sha = "$(git rev-parse --verify --quiet "refs/tags/$Tag^{commit}")".Trim()
    if ($sha -notmatch '^[0-9a-f]{40}$') { Stop-Deploy "tag $Tag does not exist" }
    $release = gh release view $Tag -R $Repo --json isDraft | Out-String
    if ($LASTEXITCODE -ne 0) { Stop-Deploy "$Tag is not a GitHub release" }
    if (($release | ConvertFrom-Json).isDraft) { Stop-Deploy "the $Tag release is still a draft" }
    $ci = "$(gh api "repos/$Repo/commits/$sha/check-runs?check_name=ci&filter=latest" --jq '.check_runs[0].conclusion')".Trim()
    if ($ci -ne 'success') { Stop-Deploy "ci on $sha is '$ci', not success" }
    $expected = $sha.Substring(0, 12)
    if ($ForceVerifyFailure) {
        $expected = '000000000000'
        Write-Host '  -ForceVerifyFailure: verification will fail on purpose and roll back'
    }

    Write-Step 'Preflight'
    & (Join-Path $PSScriptRoot 'preflight.ps1') -App $App
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'preflight failed' }

    Write-Step 'Creating a Cloudron backup'
    cloudron backup create --app $App | Out-Host
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'cloudron backup create failed' }
    $backups = cloudron backup list --raw --app $App | Out-String | ConvertFrom-Json
    $backup = $backups | Sort-Object { ([datetime]$_.creationTime).ToUniversalTime() } -Descending | Select-Object -First 1
    if ($null -eq $backup -or ([datetime]$backup.creationTime).ToUniversalTime() -lt $started.AddMinutes(-5)) {
        Stop-Deploy 'no new backup found'
    }
    $backupId = $backup.id
    Write-Host "  backup $backupId"

    $previous = "$(Invoke-App readlink -f /app/data/current)".Trim()
    if ($previous -notlike "$releases/*") { $previous = '' }
    $previousRevision = ''
    if ($previous) {
        $previousRevision = "$(Invoke-App cat "$previous/REVISION")".Trim()
        if ($previousRevision.Length -ge 12) { $previousRevision = $previousRevision.Substring(0, 12) }
        Write-Host "  live release $($previous.Split('/')[-1])"
    }

    Write-Step "Building $Tag ($sha)"
    $build = @{}
    try {
        & (Join-Path $PSScriptRoot 'build-release.ps1') -Ref $sha -Php $Php -Composer $Composer | ForEach-Object {
            if ("$_" -match '^(ReleaseId|ReleaseDir|Revision)=(.+)$') { $build[$Matches[1]] = $Matches[2] } else { Write-Host $_ }
        }
    }
    catch {
        Stop-Deploy "build failed: $($_.Exception.Message)"
    }
    if (-not $build.ReleaseId -or $build.Revision -ne $sha) { Stop-Deploy 'build failed' }
    $id = $build.ReleaseId
    $target = "$releases/$id"

    Write-Step "Uploading $id"
    cloudron sync push --app $App $build.ReleaseDir "$releases/" | Out-Host
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'upload failed' }
    $uploaded = "$(Invoke-App cat "$target/REVISION")".Trim()
    if ($uploaded -ne $sha) {
        Invoke-App rm -rf $target | Out-Null
        Stop-Deploy "uploaded REVISION is '$uploaded', expected $sha"
    }
    Remove-Item -Recurse -Force $build.ReleaseDir

    Write-Step "Activating $id"
    Invoke-App bash "$target/deploy/cloudron/activate-release.sh" $target | Out-Host
    $ok = $LASTEXITCODE -eq 0
    if ($ok) {
        Write-Step 'Restarting the app'
        Restart-App
        $ok = $LASTEXITCODE -eq 0
    }
    if ($ok) {
        Write-Step "Verifying $Url/healthz"
        $ok = Test-Live $expected
    }
    if ($ok) {
        Write-Step "Deployed $id"
        Write-Summary 'deployed'
        exit 0
    }

    if (-not $previous) {
        Write-Host "FAILED: verification failed and there is no earlier release to roll back to. Restore backup $backupId if needed."
        Write-Summary 'failed, no rollback possible'
        exit 1
    }
    Write-Step "Verification failed. Rolling back to $($previous.Split('/')[-1])"
    Invoke-App bash "$target/deploy/cloudron/rollback-release.sh" $previous | Out-Host
    Restart-App
    if (Test-Live $previousRevision) {
        if ("$(Invoke-App readlink -f /app/data/current)".Trim() -eq $previous) {
            Invoke-App rm -rf $target | Out-Null
            Write-Host "  removed $id"
        }
        Write-Host 'Rolled back. Migrations from the failed release were not undone.'
        Write-Summary "rolled back to $($previous.Split('/')[-1])"
    }
    else {
        Write-Host "ROLLBACK FAILED: the app is not healthy. Restore backup $backupId in the Cloudron dashboard."
        Write-Summary 'rollback failed'
    }
    exit 1
}
finally {
    Pop-Location
}
