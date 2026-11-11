<#
.SYNOPSIS
    Live preview gate, steps 1 and 2: load the latest code, then check the
    Laragon preview serves it through FastCGI. Run before any development work.

.DESCRIPTION
    1. Stops if tracked files have uncommitted changes. Fetches origin, fast-
       forwards the branch, and merges origin/main into it (a merge commit, never
       a rebase; a conflict is aborted and reported). Nothing is pushed.
    2. Runs composer install or npm ci when a lock file is newer than the
       installed dependencies, and reports migrations that have not run (it
       never runs them). When no migrations are pending, it runs the deploy-
       safe seeders that deploy/cloudron/activate-release.sh runs (roles,
       permissions and module defaults, no personal data), so local grants
       match Cloudron.
    3. Checks Laragon is running, the site answers, and /healthz shows the
       expected PHP version through FastCGI and the checked-out revision.
       Clears Laravel's caches once if the revision is stale.
    4. Starts Vite in its own window if nothing listens on the profile's port.

    Opening the page in Cursor's browser is the agent's step 3.

    Exit codes: 0 ready, 1 blocked (the message says why), 3 needs a decision.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1
#>
[CmdletBinding()]
param(
    [switch]$NoSync,
    [switch]$NoMergeMain,
    [switch]$NoVite,
    [string]$LaragonRoot = 'C:\laragon'
)

$ErrorActionPreference = 'Stop'

function Write-Step([string]$Message) { Write-Host "==> $Message" -ForegroundColor Cyan }
function Write-Ok([string]$Message) { Write-Host "    $Message" -ForegroundColor Green }
function Write-Warn([string]$Message) { Write-Host "    WARNING: $Message" -ForegroundColor Yellow }
function Stop-Preview([int]$Code, [string]$Message) { Write-Host "    STOP: $Message" -ForegroundColor Red; exit $Code }

function Invoke-Native([scriptblock]$Command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { $output = & $Command 2>&1 } finally { $ErrorActionPreference = $previous }
    return [pscustomobject]@{ Code = $LASTEXITCODE; Output = ($output | Out-String).Trim() }
}

function Get-Health([string]$Url) {
    try {
        $response = Invoke-WebRequest "$Url/healthz" -UseBasicParsing -TimeoutSec 60
        return $response.Content | ConvertFrom-Json
    } catch {
        $stream = $_.Exception.Response.GetResponseStream()
        if ($null -eq $stream) { return $null }
        return (New-Object IO.StreamReader $stream).ReadToEnd() | ConvertFrom-Json
    }
}

$repo = Split-Path $PSScriptRoot -Parent
$p = Get-Content (Join-Path $repo 'docs\laragon\profile.json') -Raw | ConvertFrom-Json
$phpPattern = "^php-$([regex]::Escape($p.php))\.\d+(-\d+)?-" + $(if ($p.phpThreadSafe) { '' } else { 'nts-' }) + 'Win32'
$phpDir = Get-ChildItem "$LaragonRoot\bin\php" -Directory | Where-Object { $_.Name -match $phpPattern } | Sort-Object Name -Descending | Select-Object -First 1
if (-not $phpDir) { Stop-Preview 1 "Laragon has no PHP $($p.php) build matching $phpPattern. Run scripts\laragon-setup.ps1." }
$nodeDir = Get-ChildItem "$LaragonRoot\bin\nodejs" -Directory | Where-Object { $_.Name -match "^node-v?$($p.node)(\D|$)" } | Select-Object -First 1
$env:Path = "$($phpDir.FullName);$LaragonRoot\bin\composer;$(if ($nodeDir) { $nodeDir.FullName });" + $env:Path
if (-not $env:XDEBUG_MODE) { $env:XDEBUG_MODE = 'off' }

Push-Location $repo
try {
    # 1. Latest code ------------------------------------------------------------
    Write-Step 'Latest code'
    $before = (git rev-parse HEAD).Trim()
    $branch = (git branch --show-current).Trim()
    if ($NoSync) {
        Write-Warn 'Skipped the sync (-NoSync)'
    } else {
        $dirty = @(git status --porcelain --untracked-files=no)
        if ($dirty.Count -gt 0) { Stop-Preview 3 "$($dirty.Count) tracked files have uncommitted changes. Commit them or decide what to do before syncing." }
        $fetch = Invoke-Native { git fetch origin --prune --tags }
        if ($fetch.Code -ne 0) { Stop-Preview 1 "git fetch failed: $($fetch.Output)" }
        $upstream = Invoke-Native { git rev-parse --abbrev-ref '@{u}' }
        if ($upstream.Code -eq 0) {
            $ff = Invoke-Native { git merge --ff-only '@{u}' }
            if ($ff.Code -ne 0) { Stop-Preview 3 "$branch and $($upstream.Output) have both moved on. Merge them (no rebase), then run this again." }
        }
        if ($branch -ne 'main' -and -not $NoMergeMain) {
            $behind = [int](git rev-list --count 'HEAD..origin/main').Trim()
            if ($behind -gt 0) {
                $merge = Invoke-Native { git merge --no-edit origin/main }
                if ($merge.Code -ne 0) {
                    Invoke-Native { git merge --abort } | Out-Null
                    Stop-Preview 3 "Merging origin/main into $branch conflicts. The merge was aborted; resolve it before starting work."
                }
                Write-Ok "Merged $behind commits from origin/main into $branch (not pushed)"
            }
        }
    }
    $head = (git rev-parse HEAD).Trim()
    Write-Ok "$branch at $($head.Substring(0, 12))"

    # 2. Dependencies and migrations --------------------------------------------
    Write-Step 'Dependencies'
    $installed = Join-Path $repo 'vendor\composer\installed.json'
    if (-not (Test-Path $installed) -or (Get-Item 'composer.lock').LastWriteTime -gt (Get-Item $installed).LastWriteTime) {
        $run = Invoke-Native { php "$LaragonRoot\bin\composer\composer.phar" install --no-interaction --no-progress }
        if ($run.Code -ne 0) { Stop-Preview 1 "composer install failed: $($run.Output)" }
        Write-Ok 'Ran composer install'
    } else { Write-Ok 'Composer dependencies are current' }
    $npmMarker = Join-Path $repo 'node_modules\.package-lock.json'
    if (-not (Test-Path $npmMarker) -or (Get-Item 'package-lock.json').LastWriteTime -gt (Get-Item $npmMarker).LastWriteTime) {
        $run = Invoke-Native { npm ci --no-audit --no-fund }
        if ($run.Code -ne 0) { Stop-Preview 1 "npm ci failed: $($run.Output)" }
        Write-Ok 'Ran npm ci (restart Vite if it was running)'
    } else { Write-Ok 'npm dependencies are current' }
    $pending = Invoke-Native { php artisan migrate:status --pending --no-ansi }
    if ($pending.Code -eq 0 -and $pending.Output -match '\bPending\b' -and $pending.Output -notmatch 'No pending migrations') {
        Write-Warn 'There are migrations that have not run. Ask before running php artisan migrate, then run this again to sync roles and permissions.'
    } else {
        Write-Ok 'No pending migrations'
        $seeders = @([regex]::Matches((Get-Content 'deploy\cloudron\activate-release.sh' -Raw), "db:seed --class='([^']+)'") | ForEach-Object { $_.Groups[1].Value })
        if ($seeders.Count -eq 0) { Stop-Preview 1 'Found no db:seed --class lines in deploy\cloudron\activate-release.sh.' }
        foreach ($seeder in $seeders) {
            $seed = Invoke-Native { php artisan db:seed "--class=$seeder" --force --no-interaction }
            if ($seed.Code -ne 0) { Stop-Preview 1 "php artisan db:seed --class=$seeder failed: $($seed.Output)" }
        }
        $reset = Invoke-Native { php artisan permission:cache-reset }
        if ($reset.Code -ne 0) { Stop-Preview 1 "php artisan permission:cache-reset failed: $($reset.Output)" }
        Write-Ok "Synced roles and permissions ($($seeders.Count) deploy seeders, as on Cloudron)"
    }

    # 3. Laragon and the preview -------------------------------------------------
    Write-Step "Laragon preview $($p.appUrl)"
    $missing = @('laragon', 'httpd', 'mysqld' | Where-Object { -not (Get-Process $_ -ErrorAction SilentlyContinue) })
    if ($missing.Count -gt 0) { Stop-Preview 1 "Not running: $($missing -join ', '). Click Start All in Laragon." }
    $health = Get-Health $p.appUrl
    if ($null -eq $health -or $null -eq $health.local) { Stop-Preview 1 "$($p.appUrl)/healthz did not answer with the local block. Is APP_ENV=local in .env, and has Laragon been reloaded?" }
    if ($health.local.git_revision -ne $head.Substring(0, 12)) {
        Invoke-Native { php artisan optimize:clear } | Out-Null
        $health = Get-Health $p.appUrl
    }
    if ($health.local.git_revision -ne $head.Substring(0, 12)) { Stop-Preview 1 "The preview serves $($health.local.git_revision), not $($head.Substring(0, 12)). Check the junction $($p.folder) points at this clone." }
    if ($health.local.php_sapi -ne 'cgi-fcgi') { Stop-Preview 1 "The preview runs PHP as $($health.local.php_sapi), not FastCGI (cgi-fcgi). Switch Laragon to the NTS PHP $($p.php) build." }
    if ($health.local.php -notlike "$($p.php).*") { Stop-Preview 1 "The preview runs PHP $($health.local.php), not $($p.php)." }
    Write-Ok "Serves $($head.Substring(0, 12)) on PHP $($health.local.php) through FastCGI"
    if ($health.status -ne 'ok') {
        $failed = ($health.checks.PSObject.Properties | Where-Object { $_.Value -ne 'ok' } | ForEach-Object { $_.Name }) -join ', '
        Write-Warn "/healthz reports $($health.status) ($failed). The preview still works; start that Laragon service if the task needs it."
    }

    # 4. Vite ------------------------------------------------------------------
    Write-Step "Vite on port $($p.viteDevServerPort)"
    if (Get-NetTCPConnection -State Listen -LocalPort $p.viteDevServerPort -ErrorAction SilentlyContinue) {
        Write-Ok 'Already running'
    } elseif ($NoVite) {
        Write-Warn 'Not running (-NoVite). Start it with npm run dev.'
    } else {
        Start-Process -FilePath 'cmd.exe' -ArgumentList '/c', 'npm run dev' -WorkingDirectory $repo -WindowStyle Minimized
        Write-Ok 'Started npm run dev in its own window'
    }

    Write-Step 'Ready'
    Write-Ok "Open $($p.appUrl) in Cursor's browser (Vite hot reload on port $($p.viteDevServerPort))."
    if ($before -ne $head) { Write-Ok "Code moved from $($before.Substring(0, 12)) to $($head.Substring(0, 12)) during the sync." }
} finally {
    Pop-Location
}
