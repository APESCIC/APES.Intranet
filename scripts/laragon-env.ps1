<#
.SYNOPSIS
    Put Laragon's PHP, Composer and Node for APES Intranet first on PATH for
    this PowerShell session.

.DESCRIPTION
    Reads docs/laragon/profile.json and picks the newest matching PHP build
    (8.4 NTS) and Node (22) in Laragon, so nothing breaks when Laragon updates
    PHP. Also sets XDEBUG_MODE=off. Changes nothing on disk.

    Dot-source it, so the settings stay in your terminal:

        . .\scripts\laragon-env.ps1

.PARAMETER LaragonRoot
    Laragon's folder. Defaults to C:\laragon.
#>
[CmdletBinding()]
param(
    [string]$LaragonRoot = 'C:\laragon'
)

function Get-LaragonNewestFolder([string]$Parent, [string]$Pattern, [string]$VersionRegex) {
    Get-ChildItem $Parent -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match $Pattern } |
        Sort-Object {
            if ($_.Name -match $VersionRegex) {
                $v = $Matches[1]
                if ($v -notmatch '\.') { $v += '.0' }
                [version]$v
            } else { [version]'0.0' }
        } -Descending |
        Select-Object -First 1
}

$laragonProfile = Get-Content (Join-Path (Split-Path $PSScriptRoot -Parent) 'docs\laragon\profile.json') -Raw | ConvertFrom-Json
$phpPattern = "^php-$([regex]::Escape($laragonProfile.php))\.\d+(-\d+)?-" + $(if ($laragonProfile.phpThreadSafe) { '' } else { 'nts-' }) + 'Win32'
$phpDir = Get-LaragonNewestFolder "$LaragonRoot\bin\php" $phpPattern '^php-(\d+\.\d+\.\d+)'
$nodeDir = Get-LaragonNewestFolder "$LaragonRoot\bin\nodejs" "^node-v?$($laragonProfile.node)(\D|$)" '^node-v?(\d+(\.\d+){0,2})'

if (-not $phpDir) {
    Write-Warning "Laragon has no PHP $($laragonProfile.php) NTS build in $LaragonRoot\bin\php. Run scripts\laragon-setup.ps1."
    return
}
if (-not $nodeDir) {
    Write-Warning "Laragon has no Node $($laragonProfile.node) in $LaragonRoot\bin\nodejs. Run scripts\laragon-setup.ps1."
    return
}

$env:Path = "$($phpDir.FullName);$LaragonRoot\bin\composer;$($nodeDir.FullName);" + $env:Path
$env:XDEBUG_MODE = 'off'
Write-Host "PHP $($phpDir.Name), Node $($nodeDir.Name) and Composer are first on PATH; XDEBUG_MODE=off."
