<#
.SYNOPSIS
    Set up APES Intranet as its own Laragon project. Safe to run more than once.

.DESCRIPTION
    Reads docs/laragon/profile.json, then:
      1. finds Laragon's PHP, Composer, Node and MySQL that match the profile and
         puts them first on PATH for this PowerShell session only;
      2. warns if Laragon's Apache is not running the profile's PHP version;
      3. links C:\laragon\www\apes-intranet to this clone (a junction), unless it
         is already there;
      4. writes the "apes-intranet" Laragon profile (C:\laragon\usr\profile),
         unless it is already there;
      5. copies .env.laragon.example to .env if .env is missing;
      6. installs Composer and npm dependencies, and generates APP_KEY if blank;
      7. creates this project's database if missing, and runs the migrations;
      8. offers to load the fake demo data (php artisan demo:seed).

    It only touches this project's folder link, profile, .env and database.

.PARAMETER SeedDemo
    Load the demo data without asking.

.PARAMETER NoDemo
    Skip the demo data without asking.

.PARAMETER LaragonRoot
    Laragon's folder. Defaults to C:\laragon.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1 -SeedDemo
#>
[CmdletBinding()]
param(
    [switch]$SeedDemo,
    [switch]$NoDemo,
    [string]$LaragonRoot = 'C:\laragon'
)

$ErrorActionPreference = 'Stop'

function Write-Step([string]$Message) { Write-Host "==> $Message" -ForegroundColor Cyan }
function Write-Ok([string]$Message) { Write-Host "    $Message" -ForegroundColor Green }
function Write-Warn([string]$Message) { Write-Host "    WARNING: $Message" -ForegroundColor Yellow }

function Get-NewestFolder([string]$Parent, [string]$Pattern, [string]$VersionRegex) {
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

# Windows PowerShell turns a native tool's stderr into errors, so run tools
# with 'Continue' and judge them by their exit code.
function Invoke-Checked([string]$What, [scriptblock]$Command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { & $Command } finally { $ErrorActionPreference = $previous }
    if ($LASTEXITCODE -ne 0) { throw "$What failed (exit $LASTEXITCODE)." }
}

function Get-EnvValue([string]$Path, [string]$Key) {
    $line = Get-Content $Path | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if ($null -eq $line) { return $null }
    return ($line -replace "^$Key=", '').Trim('"')
}

if ($SeedDemo -and $NoDemo) { throw 'Use -SeedDemo or -NoDemo, not both.' }

$repo = Split-Path $PSScriptRoot -Parent
$profilePath = Join-Path $repo 'docs\laragon\profile.json'
$p = Get-Content $profilePath -Raw | ConvertFrom-Json

Write-Step "APES Intranet Laragon set-up ($($p.appUrl))"

# 1. Laragon tools ------------------------------------------------------------
Write-Step 'Checking Laragon'
if (-not (Test-Path (Join-Path $LaragonRoot 'laragon.exe'))) {
    throw "Laragon was not found at $LaragonRoot. Install Laragon (Full), or pass -LaragonRoot."
}

$phpMinor = [regex]::Escape($p.php)
$phpDir = Get-NewestFolder "$LaragonRoot\bin\php" "^php-$phpMinor\.\d+-(nts-)?Win32" '^php-(\d+\.\d+\.\d+)'
if (-not $phpDir) { throw "PHP $($p.php) is not installed in Laragon. Add it with Menu > Tools > Quick add, then run this again." }

$composerPhar = Join-Path $LaragonRoot 'bin\composer\composer.phar'
if (-not (Test-Path $composerPhar)) { throw "Composer was not found at $composerPhar." }

$nodeDir = Get-NewestFolder "$LaragonRoot\bin\nodejs" "^node-v?$($p.node)(\D|$)" '^node-v?(\d+(\.\d+){0,2})'
if (-not $nodeDir) { throw "Node $($p.node) is not installed in Laragon. Add it with Menu > Tools > Quick add, then run this again." }

$mysqlMinor = [regex]::Escape($p.database.version)
$mysqlDir = Get-NewestFolder "$LaragonRoot\bin\mysql" "^mysql-$mysqlMinor\." '^mysql-(\d+\.\d+\.\d+)'
if (-not $mysqlDir) { throw "MySQL $($p.database.version) is not installed in Laragon." }

$apacheDir = Get-NewestFolder "$LaragonRoot\bin\apache" "^httpd-$([regex]::Escape($p.apache))\." '^httpd-(\d+\.\d+\.\d+)'
if (-not $apacheDir) { throw "Apache $($p.apache) is not installed in Laragon." }

$env:Path = "$($phpDir.FullName);$(Split-Path $composerPhar);$($nodeDir.FullName);$($mysqlDir.FullName)\bin;" + $env:Path
if (-not $env:XDEBUG_MODE) { $env:XDEBUG_MODE = 'off' }

$phpVersion = "$(php -r 'echo PHP_VERSION;')".Trim()
$nodeVersion = "$(node -v)".Trim()
Write-Ok "PHP $phpVersion  ($($phpDir.FullName))"
Write-Ok "Node $nodeVersion  ($($nodeDir.FullName))"
Write-Ok "MySQL $($mysqlDir.Name), Apache $($apacheDir.Name)"
if ($phpVersion -notlike "$($p.php).*") { throw "PHP on PATH is $phpVersion, not $($p.php)." }
if ($nodeVersion -notlike "v$($p.node).*") { throw "Node on PATH is $nodeVersion, not $($p.node)." }

$loaded = "$(php -r "echo implode(',', array_map('strtolower', get_loaded_extensions()));")".Split(',')
$missing = @($p.phpExtensions | Where-Object { $loaded -notcontains $_ })
if ($missing.Count -gt 0) {
    throw "PHP $phpVersion is missing these extensions: $($missing -join ', '). Turn them on in Menu > PHP > Extensions, then run this again."
}
Write-Ok "PHP extensions: $($p.phpExtensions -join ', ')"

# 2. Laragon's Apache PHP -------------------------------------------------------
$modPhp = Join-Path $LaragonRoot 'etc\apache2\mod_php.conf'
$apachePhp = $null
if (Test-Path $modPhp) {
    $iniDir = Select-String -Path $modPhp -Pattern '^PHPIniDir\s+"([^"]+)"' | Select-Object -First 1
    if ($iniDir) { $apachePhp = Split-Path $iniDir.Matches[0].Groups[1].Value -Leaf }
}
if (-not $apachePhp) {
    Write-Warn "Could not tell which PHP Laragon's Apache runs. Check Menu > PHP > Version is $($p.php)."
} elseif ($apachePhp -notlike "php-$($p.php).*") {
    Write-Warn "Laragon's Apache runs $apachePhp, but this project needs PHP $($p.php). Choose Menu > Profile > $($p.laragonProfile) (or Menu > PHP > Version > $($p.php)) in Laragon."
} else {
    Write-Ok "Laragon's Apache runs $apachePhp"
}

if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) { Write-Warn 'Apache is not running. Click Start All in Laragon.' }

# 3. Project folder -------------------------------------------------------------
Write-Step "Project folder $($p.folder)"
$repoFull = (Resolve-Path $repo).Path.TrimEnd('\')
if (Test-Path $p.folder) {
    $item = Get-Item $p.folder -Force
    $target = if ($item.LinkType) { "$($item.Target)".TrimEnd('\') } else { $item.FullName.TrimEnd('\') }
    if ($target -ne $repoFull) {
        throw "$($p.folder) already exists and points at $target, not this clone ($repoFull). Remove or rename it yourself, then run this again."
    }
    Write-Ok 'Already linked to this clone'
} else {
    New-Item -ItemType Junction -Path $p.folder -Target $repoFull | Out-Null
    Write-Ok "Linked to $repoFull"
}

$vhost = Join-Path $LaragonRoot "etc\apache2\sites-enabled\auto.$($p.hostname).conf"
if (Test-Path $vhost) {
    Write-Ok "Laragon site: $vhost"
} elseif (Get-Process laragon -ErrorAction SilentlyContinue) {
    & (Join-Path $LaragonRoot 'laragon.exe') reload
    Start-Sleep -Seconds 5
    if (Test-Path $vhost) { Write-Ok "Reloaded Laragon, which created $vhost" }
    else { Write-Warn "Laragon has not created $vhost yet. Click Reload in Laragon." }
} else {
    Write-Warn "Laragon is not running. Start it and click Reload, so it creates http://$($p.hostname)."
}

# 4. Laragon profile ------------------------------------------------------------
$profileDir = Join-Path $LaragonRoot 'usr\profile'
$laragonProfile = Join-Path $profileDir "$($p.laragonProfile).ini"
Write-Step "Laragon profile $($p.laragonProfile)"
if (Test-Path $laragonProfile) {
    Write-Ok "Already there: $laragonProfile"
} else {
    $sections = [ordered]@{}
    $defaultProfile = Join-Path $profileDir 'default.ini'
    if (Test-Path $defaultProfile) {
        $section = $null
        foreach ($line in Get-Content $defaultProfile) {
            if ($line -match '^\[(.+)\]$') { $section = $Matches[1]; $sections[$section] = $null }
            elseif ($section -and $line -match '^Version=(.*)$') { $sections[$section] = $Matches[1] }
        }
    }
    $sections['apache'] = $apacheDir.Name
    $sections['mysql'] = $mysqlDir.Name
    $sections['php'] = $phpDir.Name
    $sections['nodejs'] = $nodeDir.Name
    $text = ($sections.Keys | Where-Object { $sections[$_] } | ForEach-Object { "[$_]`r`nVersion=$($sections[$_])`r`n" }) -join "`r`n"
    New-Item -ItemType Directory -Path $profileDir -Force | Out-Null
    [IO.File]::WriteAllText($laragonProfile, $text, (New-Object Text.UTF8Encoding $false))
    Write-Ok "Wrote $laragonProfile. Choose Menu > Profile > $($p.laragonProfile) in Laragon to use it."
}

# 5. .env -----------------------------------------------------------------------
Write-Step '.env'
Push-Location $repo
try {
    $envFile = Join-Path $repo '.env'
    if (Test-Path $envFile) {
        Write-Ok '.env already exists, left as it is'
    } else {
        Copy-Item (Join-Path $repo '.env.laragon.example') $envFile
        Write-Ok 'Copied .env.laragon.example to .env'
    }
    foreach ($check in @(@('APP_ENV', 'local'), @('APP_URL', $p.appUrl), @('DB_DATABASE', $p.database.name), @('DB_HOST', $p.database.host), @('DB_PORT', "$($p.database.port)"))) {
        $value = Get-EnvValue $envFile $check[0]
        if ($value -ne $check[1]) { Write-Warn "$($check[0]) in .env is '$value'. The Laragon profile expects '$($check[1])'." }
    }

    # 6. Dependencies -----------------------------------------------------------
    Write-Step 'Composer dependencies'
    Invoke-Checked 'composer install' { php $composerPhar install --no-interaction --no-progress }

    Write-Step 'npm dependencies'
    if (Test-Path (Join-Path $repo 'node_modules')) {
        Write-Ok 'node_modules already there. Run npm ci yourself after package-lock.json changes.'
    } else {
        Invoke-Checked 'npm ci' { npm ci --no-audit --no-fund }
    }

    if (-not (Get-EnvValue $envFile 'APP_KEY')) {
        Invoke-Checked 'key:generate' { php artisan key:generate --no-interaction }
    } else {
        Write-Ok 'APP_KEY already set'
    }

    # 7. Database ---------------------------------------------------------------
    $dbName = Get-EnvValue $envFile 'DB_DATABASE'
    $dbHost = Get-EnvValue $envFile 'DB_HOST'
    $dbPort = Get-EnvValue $envFile 'DB_PORT'
    $dbUser = Get-EnvValue $envFile 'DB_USERNAME'
    Write-Step "Database $dbName"
    if ($dbName -notmatch '^[A-Za-z0-9_]+$') { throw "DB_DATABASE '$dbName' must use only letters, numbers and underscores." }
    if ((Get-EnvValue $envFile 'DB_PASSWORD')) { throw 'DB_PASSWORD is set in .env. Laragon uses root with no password; the script only creates databases that way.' }

    try {
        Invoke-Checked 'mysqladmin ping' { mysqladmin -u $dbUser -h $dbHost -P $dbPort --connect-timeout=5 ping 2>$null | Out-Null }
    } catch {
        throw "MySQL is not answering on ${dbHost}:${dbPort}. Click Start All in Laragon, then run this again."
    }
    Invoke-Checked 'CREATE DATABASE' { mysql -u $dbUser -h $dbHost -P $dbPort -e "CREATE DATABASE IF NOT EXISTS ``$dbName`` CHARACTER SET $($p.database.charset) COLLATE $($p.database.collation);" }
    Write-Ok "$dbName is ready"

    Write-Step 'Migrations'
    Invoke-Checked 'migrate' { php artisan migrate --no-interaction }

    # 8. Demo data --------------------------------------------------------------
    Write-Step 'Demo data'
    $loadDemo = $SeedDemo.IsPresent
    if (-not $SeedDemo -and -not $NoDemo) {
        $answer = Read-Host '    Load the fake demo data now? [y/N]'
        $loadDemo = $answer -match '^(y|yes)$'
    }
    if ($loadDemo) {
        Invoke-Checked 'demo:seed' { php artisan demo:seed --no-interaction }
    } else {
        Write-Ok 'Skipped. Load it any time with: php artisan demo:seed'
    }
} finally {
    Pop-Location
}

Write-Step 'Done'
Write-Ok "Start Vite for hot reload: npm run dev (port $($p.viteDevServerPort))"
Write-Ok "Open $($p.appUrl), and $($p.appUrl)/qa to sign in as a fake account for any role."
