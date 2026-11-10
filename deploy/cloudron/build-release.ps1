<#
.SYNOPSIS
    Build an APES Intranet release folder from an exact commit, ready to upload to Cloudron.

.DESCRIPTION
    The release folder is named exactly <ReleaseId>, so uploading it with
    `cloudron sync push --app $app "<ReleaseDir>" /app/data/releases/` (no
    trailing slash on the source) creates /app/data/releases/<ReleaseId>.
    deploy.ps1 runs this for you.

    Composer runs with Laragon's newest PHP 8.4 (as on Cloudron), and npm
    with Node from PATH, or Laragon's Node. The build stops if the PHP is not
    8.4.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref v0.0.2

    Prints the release id and folder. See docs/deploy/cloudron.md for the next steps.

.EXAMPLE
    ... build-release.ps1 -Ref v0.0.2 -Php C:\path\to\php.exe -Composer C:\path\to\composer.phar

    Builds with a different PHP 8.4 or Composer.

.PARAMETER Version
    The release version to stamp into VERSION, such as v0.3.11. Defaults to
    -Ref when -Ref is a vMAJOR.MINOR.PATCH tag. Without either, no VERSION
    file is written and the app reports "dev".

.PARAMETER NoCache
    Install vendor/ and node_modules/ from scratch. By default the build reuses
    them in its own worktree, %LOCALAPPDATA%\APES\apes-intranet-build\worktree,
    so an unchanged composer.lock leaves composer install nothing to do and an
    unchanged package-lock.json skips npm ci.
#>
param(
    [Parameter(Mandatory = $true)][string]$Ref,
    [string]$Version = '',
    [string]$Php = '',
    [string]$Composer = '',
    [switch]$NoCache
)

$ErrorActionPreference = 'Stop'
$repo = (git rev-parse --show-toplevel).Trim()
$sha = (git -C $repo rev-parse --verify "$Ref^{commit}").Trim()
if ($LASTEXITCODE -ne 0 -or $sha -notmatch '^[0-9a-f]{40}$') { throw "Unknown ref: $Ref" }

$versionPattern = '^v\d+\.\d+\.\d+$'
if (-not $Version -and $Ref -match $versionPattern) { $Version = $Ref }
if ($Version) {
    if ($Version -notmatch $versionPattern) { throw "Version must look like v1.2.3, not '$Version'." }
    $tagSha = "$(git -C $repo rev-parse --verify --quiet "refs/tags/$Version^{commit}")".Trim()
    if ($tagSha -and $tagSha -ne $sha) { throw "Tag $Version is $tagSha, not the commit being built ($sha)." }
}

$laragon = 'C:\laragon\bin'
if (-not $Php) {
    $laragonPhp = Get-ChildItem "$laragon\php" -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match '^php-8\.4\.\d+-' } |
        Sort-Object { [version]($_.Name -replace '^php-(\d+\.\d+\.\d+)-.*$', '$1') } -Descending |
        Select-Object -First 1
    $Php = if ($laragonPhp) { Join-Path $laragonPhp.FullName 'php.exe' } else { 'php' }
}
$phpVersion = "$(& $Php -r 'echo PHP_VERSION;')".Trim()
if ($LASTEXITCODE -ne 0 -or $phpVersion -notlike '8.4.*') {
    throw "PHP 8.4 is needed to match Cloudron, but $Php is '$phpVersion'. Add PHP 8.4 to Laragon (Menu > Tools > Quick add), or pass -Php."
}

if (-not $Composer) {
    $command = Get-Command composer -ErrorAction SilentlyContinue | Select-Object -First 1
    $Composer = if ($command) { Join-Path (Split-Path $command.Source) 'composer.phar' } else { "$laragon\composer\composer.phar" }
}
if (-not (Test-Path $Composer)) { throw 'composer.phar was not found. Use Laragon (Full), or pass -Composer C:\path\to\composer.phar.' }
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    $laragonNode = Get-ChildItem "$laragon\nodejs" -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 1
    if ($laragonNode) { $env:Path = "$($laragonNode.FullName);$env:Path" }
}
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) { throw 'npm was not found. Use Laragon (Full), or put Node.js on PATH.' }

# Xdebug slows Composer down and is not needed for a build.
$env:XDEBUG_MODE = 'off'
$composerArgs = @('install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader')

$id = '{0}-{1}' -f $sha.Substring(0, 7), (Get-Date -Format 'yyyyMMddHHmm')
$out = Join-Path (Join-Path $env:TEMP 'apes-intranet-build') $id
if (Test-Path $out) { Remove-Item -Recurse -Force $out }

# One build worktree is kept between builds, so vendor/ and node_modules/ stay in
# place: an unchanged lock file means composer install has nothing to install,
# npm ci is skipped, and Windows does not rescan thousands of new files.
$src = Join-Path $env:LOCALAPPDATA 'APES\apes-intranet-build\worktree'
git -C $repo worktree prune
if (-not (Test-Path (Join-Path $src '.git'))) {
    if (Test-Path $src) { Remove-Item -Recurse -Force $src }
    git -C $repo worktree add --detach $src $sha | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "git worktree add $src failed" }
} else {
    git -C $src checkout --quiet --detach --force $sha
    if ($LASTEXITCODE -ne 0) { throw "git checkout $sha in $src failed" }
}
$keep = if ($NoCache) { @() } else { @('-e', 'vendor', '-e', 'node_modules') }
git -C $src clean -ffdxq @keep
if ($LASTEXITCODE -ne 0) { throw "git clean in $src failed" }

try {
    Push-Location $src
    $clock = [Diagnostics.Stopwatch]::StartNew()
    $vendorWarm = Test-Path (Join-Path $src 'vendor\composer\installed.json')
    & $Php $Composer @composerArgs
    if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
    Write-Output ("Composer: {0:N0} s ({1})" -f $clock.Elapsed.TotalSeconds, $(if ($vendorWarm) { 'kept vendor' } else { 'cold' }))

    $clock.Restart()
    $lockHash = (Get-FileHash (Join-Path $src 'package-lock.json') -Algorithm SHA256).Hash
    $lockMarker = Join-Path $src 'node_modules\.apes-lock-hash'
    $nodeWarm = (Test-Path $lockMarker) -and ((Get-Content $lockMarker -Raw).Trim() -eq $lockHash)
    if (-not $nodeWarm) {
        npm ci --no-audit --no-fund
        if ($LASTEXITCODE -ne 0) { throw 'npm ci failed' }
        Set-Content -NoNewline -Path $lockMarker -Value $lockHash
    }
    Write-Output ("npm: {0:N0} s ({1})" -f $clock.Elapsed.TotalSeconds, $(if ($nodeWarm) { 'kept node_modules' } else { 'npm ci' }))

    $clock.Restart()
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
    Write-Output ("Vite build: {0:N0} s" -f $clock.Elapsed.TotalSeconds)
    Set-Content -NoNewline -Path REVISION -Value $sha
    if ($Version) { Set-Content -NoNewline -Path VERSION -Value $Version }
    Pop-Location

    $clock.Restart()
    robocopy $src $out /E /MT:16 /NFL /NDL /NJH /NJS /NP /XD .git .github docs node_modules tests storage /XF .git .env .env.* | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed with $LASTEXITCODE" }
    New-Item -ItemType File -Force (Join-Path $out 'bootstrap\cache\.gitkeep') | Out-Null
    Write-Output ("Copy release folder: {0:N0} s" -f $clock.Elapsed.TotalSeconds)
}
finally {
    if ((Get-Location).Path -eq $src) { Pop-Location }
}

Write-Output "ReleaseId=$id"
Write-Output "ReleaseDir=$out"
Write-Output "Revision=$sha"
Write-Output "Version=$(if ($Version) { $Version } else { 'dev' })"
exit 0
