<#
.SYNOPSIS
    Build an APES Intranet release folder from an exact commit, ready to upload to Cloudron.

.DESCRIPTION
    The release folder is named exactly <ReleaseId>, so uploading it with
    `cloudron sync push --app $app "<ReleaseDir>" /app/data/releases/` (no
    trailing slash on the source) creates /app/data/releases/<ReleaseId>.
    deploy.ps1 runs this for you.

    Composer runs in the apes-intranet-app image (PHP 8.4, as on Cloudron) and
    npm in node:22-bookworm-slim, so Docker Desktop must be running.
    -HostTools uses host PHP, Composer and npm instead, in an emergency only.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref v0.0.2

    Prints the release id and folder. See docs/deploy/cloudron.md for the next steps.

.EXAMPLE
    ... build-release.ps1 -Ref v0.0.2 -HostTools -Php C:\path\to\php.exe -Composer C:\path\to\composer.phar

    Emergency build without Docker, using host tools.
#>
param(
    [Parameter(Mandatory = $true)][string]$Ref,
    [switch]$HostTools,
    [string]$Php = 'php',
    [string]$Composer = 'composer'
)

$ErrorActionPreference = 'Stop'
$repo = (git rev-parse --show-toplevel).Trim()
$sha = (git -C $repo rev-parse --verify "$Ref^{commit}").Trim()
if ($LASTEXITCODE -ne 0 -or $sha -notmatch '^[0-9a-f]{40}$') { throw "Unknown ref: $Ref" }

$phpImage = 'apes-intranet-app'
$nodeImage = 'node:22-bookworm-slim'
$composerArgs = @('install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader')

if (-not $HostTools) {
    $dockerUp = $false
    try {
        docker info --format '{{.ServerVersion}}' 2>$null | Out-Null
        $dockerUp = $LASTEXITCODE -eq 0
    }
    catch { $dockerUp = $false }
    if (-not $dockerUp) { throw 'Docker is not running. Start Docker Desktop and try again, or pass -HostTools in an emergency.' }

    docker compose -f (Join-Path $repo 'compose.yaml') build app | Out-Host
    if ($LASTEXITCODE -ne 0) { throw "building the $phpImage image failed" }
}

$id = '{0}-{1}' -f $sha.Substring(0, 7), (Get-Date -Format 'yyyyMMddHHmm')
$src = Join-Path $env:TEMP "apes-intranet-src-$id"
$out = Join-Path (Join-Path $env:TEMP 'apes-intranet-build') $id
if (Test-Path $out) { Remove-Item -Recurse -Force $out }

git -C $repo worktree add --detach $src $sha | Out-Null
try {
    Push-Location $src
    if ($HostTools) {
        if ($Composer -like '*.phar') { & $Php $Composer @composerArgs } else { & $Composer @composerArgs }
        if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
        npm ci --no-audit --no-fund
        if ($LASTEXITCODE -ne 0) { throw 'npm ci failed' }
        npm run build
        if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
    }
    else {
        # Named volumes keep the package caches between builds.
        docker run --rm -v "${src}:/src" -w /src -v apes-intranet-composer-cache:/tmp/composer-cache -e COMPOSER_CACHE_DIR=/tmp/composer-cache $phpImage composer @composerArgs
        if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
        docker run --rm -v "${src}:/src" -w /src -v apes-intranet-npm-cache:/root/.npm $nodeImage sh -c 'npm ci --no-audit --no-fund && npm run build'
        if ($LASTEXITCODE -ne 0) { throw 'npm ci or npm run build failed' }
    }
    Set-Content -NoNewline -Path REVISION -Value $sha
    Pop-Location

    robocopy $src $out /E /NFL /NDL /NJH /NJS /NP /XD .git .github docs docker node_modules tests storage /XF .git .env .env.* compose.yaml | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed with $LASTEXITCODE" }
    New-Item -ItemType File -Force (Join-Path $out 'bootstrap\cache\.gitkeep') | Out-Null
}
finally {
    if ((Get-Location).Path -eq $src) { Pop-Location }
    git -C $repo worktree remove --force $src
}

Write-Output "ReleaseId=$id"
Write-Output "ReleaseDir=$out"
Write-Output "Revision=$sha"
exit 0
