<#
.SYNOPSIS
    Build an APES Intranet release folder from an exact commit, ready to upload to Cloudron.

.DESCRIPTION
    The release folder is named exactly <ReleaseId>, so uploading it with
    `cloudron sync push --app $app "<ReleaseDir>" /app/data/releases/` (no
    trailing slash on the source) creates /app/data/releases/<ReleaseId>.
    deploy.ps1 runs this for you.

    Composer runs with WampServer's newest PHP 8.4 (as on Cloudron), and npm
    with the host Node. The build stops if the PHP is not 8.4.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref v0.0.2

    Prints the release id and folder. See docs/deploy/cloudron.md for the next steps.

.EXAMPLE
    ... build-release.ps1 -Ref v0.0.2 -Php C:\path\to\php.exe -Composer C:\path\to\composer.phar

    Builds with a different PHP 8.4 or Composer.
#>
param(
    [Parameter(Mandatory = $true)][string]$Ref,
    [string]$Php = '',
    [string]$Composer = ''
)

$ErrorActionPreference = 'Stop'
$repo = (git rev-parse --show-toplevel).Trim()
$sha = (git -C $repo rev-parse --verify "$Ref^{commit}").Trim()
if ($LASTEXITCODE -ne 0 -or $sha -notmatch '^[0-9a-f]{40}$') { throw "Unknown ref: $Ref" }

if (-not $Php) {
    $wampPhp = Get-ChildItem 'C:\wamp64\bin\php' -Directory -Filter 'php8.4.*' -ErrorAction SilentlyContinue |
        Sort-Object { [version]($_.Name -replace '^php', '') } -Descending |
        Select-Object -First 1
    $Php = if ($wampPhp) { Join-Path $wampPhp.FullName 'php.exe' } else { 'php' }
}
$phpVersion = "$(& $Php -r 'echo PHP_VERSION;')".Trim()
if ($LASTEXITCODE -ne 0 -or $phpVersion -notlike '8.4.*') {
    throw "PHP 8.4 is needed to match Cloudron, but $Php is '$phpVersion'. Install PHP 8.4 in WampServer, or pass -Php."
}

if (-not $Composer) {
    $command = Get-Command composer -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($command) { $Composer = Join-Path (Split-Path $command.Source) 'composer.phar' }
}
if (-not $Composer -or -not (Test-Path $Composer)) { throw 'composer.phar was not found. Install Composer, or pass -Composer C:\path\to\composer.phar.' }
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) { throw 'npm was not found. Install Node.js.' }

# Xdebug slows Composer down and is not needed for a build.
$env:XDEBUG_MODE = 'off'
$composerArgs = @('install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader')

$id = '{0}-{1}' -f $sha.Substring(0, 7), (Get-Date -Format 'yyyyMMddHHmm')
$src = Join-Path $env:TEMP "apes-intranet-src-$id"
$out = Join-Path (Join-Path $env:TEMP 'apes-intranet-build') $id
if (Test-Path $out) { Remove-Item -Recurse -Force $out }

git -C $repo worktree add --detach $src $sha | Out-Null
try {
    Push-Location $src
    & $Php $Composer @composerArgs
    if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
    npm ci --no-audit --no-fund
    if ($LASTEXITCODE -ne 0) { throw 'npm ci failed' }
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
    Set-Content -NoNewline -Path REVISION -Value $sha
    Pop-Location

    robocopy $src $out /E /NFL /NDL /NJH /NJS /NP /XD .git .github docs node_modules tests storage /XF .git .env .env.* | Out-Null
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
