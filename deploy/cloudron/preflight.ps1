<#
.SYNOPSIS
    Read-only check of the APES Intranet Cloudron app before a deploy.

.DESCRIPTION
    Prints one PASS, WARN or FAIL line per check and exits 1 on any FAIL.
    The in-app checks run from preflight.sh, pushed to /tmp and deleted
    afterwards. No value from .env or an addon variable is ever printed.
    Works in Windows PowerShell 5.1 and PowerShell 7. deploy.ps1 runs it first.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\preflight.ps1
#>
param(
    [string]$App = '8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264'
)

$ErrorActionPreference = 'Continue'
$failures = 0

function Write-Check([string]$Result, [string]$Message) {
    Write-Output "$Result $Message"
    if ($Result -eq 'FAIL') { $script:failures++ }
}

$status = cloudron status --app $App | Out-String
if ($LASTEXITCODE -ne 0) {
    Write-Check 'FAIL' "cloudron status failed for $App. Run cloudron login, then try again"
    exit 1
}
Write-Check 'PASS' 'cloudron CLI is signed in and the app is installed'
if ($status -match 'Run state:\s+running') {
    Write-Check 'PASS' 'app is running'
}
else {
    Write-Check 'FAIL' 'app is not running'
}

$backupsJson = cloudron backup list --raw --app $App | Out-String
$latest = $null
if ($LASTEXITCODE -eq 0 -and $backupsJson.Trim()) {
    $backups = $backupsJson | ConvertFrom-Json
    $latest = $backups |
        ForEach-Object { ([datetime]$_.creationTime).ToUniversalTime() } |
        Sort-Object -Descending | Select-Object -First 1
}
if ($null -eq $latest) {
    Write-Check 'WARN' 'no app backup found'
}
elseif (([datetime]::UtcNow - $latest).TotalHours -le 24) {
    Write-Check 'PASS' ('latest backup {0:yyyy-MM-dd HH:mm} UTC' -f $latest)
}
else {
    Write-Check 'WARN' ('latest backup is older than 24 hours ({0:yyyy-MM-dd HH:mm} UTC)' -f $latest)
}

$runSha = (Get-FileHash -Algorithm SHA256 (Join-Path $PSScriptRoot 'run.sh')).Hash.ToLowerInvariant()
$remote = '/tmp/apes-preflight-{0}.sh' -f [guid]::NewGuid().ToString('N').Substring(0, 8)
cloudron push --app $App (Join-Path $PSScriptRoot 'preflight.sh') $remote | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Check 'FAIL' 'could not push preflight.sh to the app'
    exit 1
}
try {
    $lines = @(cloudron exec --app $App '--' bash $remote $runSha)
    $remoteExit = $LASTEXITCODE
}
finally {
    cloudron exec --app $App '--' rm -f $remote | Out-Null
}
$lines | ForEach-Object { Write-Output $_ }
$failures += @($lines | Where-Object { $_ -like 'FAIL *' }).Count
if ($remoteExit -ne 0 -and $failures -eq 0) {
    Write-Check 'FAIL' "in-app checks exited with $remoteExit"
}

if ($failures -gt 0) {
    Write-Output "Preflight: $failures check(s) failed"
    exit 1
}
Write-Output 'Preflight: no failures'
exit 0
