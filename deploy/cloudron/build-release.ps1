<#
.SYNOPSIS
    Build an APES Intranet release folder from an exact commit, ready to upload to Cloudron.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref main

    Prints the release id and folder. See docs/deploy/cloudron.md for the next steps.

.EXAMPLE
    ... build-release.ps1 -Ref main -Php C:\laragon\bin\php\php-8.4.24-Win32-vs17-x64\php.exe -Composer C:\laragon\bin\composer\composer.phar

    Uses Laragon's PHP and Composer when they are not on PATH.
#>
param(
    [Parameter(Mandatory = $true)][string]$Ref,
    [string]$Php = 'php',
    [string]$Composer = 'composer'
)

$ErrorActionPreference = 'Stop'
$repo = (git rev-parse --show-toplevel).Trim()
$sha = (git -C $repo rev-parse $Ref).Trim()
$id = '{0}-{1}' -f $sha.Substring(0, 7), (Get-Date -Format 'yyyyMMddHHmm')
$src = Join-Path $env:TEMP "apes-intranet-src-$id"
$out = Join-Path $env:TEMP "apes-intranet-release-$id"

git -C $repo worktree add --detach $src $sha | Out-Null
try {
    Push-Location $src
    $composerArgs = @('install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader')
    if ($Composer -like '*.phar') { & $Php $Composer @composerArgs } else { & $Composer @composerArgs }
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
