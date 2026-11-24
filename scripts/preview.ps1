<#
.SYNOPSIS
    Live preview gate, steps 1 and 2: load the latest code, then check the
    Herd preview serves it through FastCGI. Run before any development work.

.DESCRIPTION
    1. Stops if tracked files have uncommitted changes. Fetches origin, fast-
       forwards the branch, and merges origin/main into it (a merge commit, never
       a rebase; a conflict is aborted and reported). Nothing is pushed.
    2. Runs composer install when composer.lock is newer than the installed
       packages, and npm ci when package-lock.json's content hash differs from
       the hash stored after the last install (not the lock file's date). Before
       npm ci it stops this site's Vite on the profile port, then starts it
       again afterwards. Runs any pending migrations with php artisan migrate
       (local APP_ENV only; never demo:reset). Then runs the deploy-safe
       seeders that deploy/cloudron/activate-release.sh runs (roles,
       permissions, module defaults and legal starter pages, no personal data),
       so local grants and legal/footer rows match Cloudron.
    3. Checks Herd and its MySQL service are running, the site answers, and /healthz shows the
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
    [string]$HerdRoot = (Join-Path $env:USERPROFILE '.config\herd')
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

function Test-LocalPortOpen([int]$Port) {
    # Prefer Get-NetTCPConnection, but without elevation Windows often leaves
    # State blank even for listeners, so -State Listen alone is a false stop.
    $conns = @(Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue)
    if ($conns.Count -gt 0) { return $true }
    try {
        $client = [System.Net.Sockets.TcpClient]::new()
        $client.Connect('127.0.0.1', $Port)
        $ok = $client.Connected
        $client.Close()
        return $ok
    } catch {
        return $false
    }
}

function Get-LocalPortConnections([int]$Port) {
    # Without admin rights State may be empty; treat blank State as a listener.
    return @(Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue |
        Where-Object { $_.State -eq 'Listen' -or [string]::IsNullOrWhiteSpace([string]$_.State) })
}

function Stop-SiteVite([string]$Repo, [int]$Port) {
    # This profile's Vite port is reserved for apes-intranet. Stop listeners on
    # that port when they are node/cmd (Vite), preferring processes whose
    # command line names this repo so another project's accidental bind is not
    # killed unless it is the only listener on our dedicated port.
    $stopped = $false
    $listeners = @(Get-LocalPortConnections -Port $Port)
    foreach ($listener in $listeners) {
        $proc = Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue
        if ($null -eq $proc) { continue }
        if ($proc.ProcessName -notmatch '^(node|cmd)$') { continue }
        $cmd = $null
        try {
            $cmd = (Get-CimInstance Win32_Process -Filter "ProcessId = $($proc.Id)").CommandLine
        } catch { }
        $namesThisRepo = $cmd -and ($cmd -like "*$Repo*")
        $looksLikeVite = $cmd -and ($cmd -match 'vite|npm run dev')
        if ($namesThisRepo -or $looksLikeVite -or $listeners.Count -eq 1) {
            Write-Ok "Stopping this site's Vite on port $Port (PID $($proc.Id)) before npm ci"
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
            $stopped = $true
        }
    }
    if ($stopped) { Start-Sleep -Seconds 1 }
    return $stopped
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
$p = Get-Content (Join-Path $repo 'docs\herd\profile.json') -Raw | ConvertFrom-Json
$herdBin = Join-Path $HerdRoot 'bin'
$phpDir = Join-Path $herdBin "php$($p.php -replace '\.', '')"
if (-not (Test-Path (Join-Path $phpDir 'php.exe'))) { Stop-Preview 1 "Herd has no PHP $($p.php) in $phpDir. Run scripts\herd-setup.ps1." }
$composerPhar = Join-Path $herdBin 'composer.phar'
$nodeDir = Get-ChildItem (Join-Path $herdBin 'nvm') -Directory -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -match "^v$($p.node)\.\d+\.\d+$" } |
    Sort-Object { [version]($_.Name.TrimStart('v')) } -Descending |
    Select-Object -First 1
if (-not $nodeDir) { Stop-Preview 1 "Herd's nvm has no Node $($p.node). Turn on Node.js in Herd > Settings, then run: herd node:install $($p.node)" }
$env:Path = "$phpDir;$herdBin;$($nodeDir.FullName);" + $env:Path
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
        $run = Invoke-Native { php $composerPhar install --no-interaction --no-progress }
        if ($run.Code -ne 0) { Stop-Preview 1 "composer install failed: $($run.Output)" }
        Write-Ok 'Ran composer install'
    } else { Write-Ok 'Composer dependencies are current' }
    # Compare package-lock by content hash, not LastWriteTime: switching branches
    # rewrites the lock file's date and used to force npm ci while Vite still held
    # native modules open on Windows (EPERM / half-deleted node_modules). See #270.
    $lockHashPath = Join-Path $repo 'node_modules\.apes-package-lock.sha256'
    $lockHash = (Get-FileHash -Algorithm SHA256 -Path (Join-Path $repo 'package-lock.json')).Hash.ToLowerInvariant()
    $storedHash = if (Test-Path $lockHashPath) { (Get-Content $lockHashPath -Raw).Trim().ToLowerInvariant() } else { '' }
    $npmMarker = Join-Path $repo 'node_modules\.package-lock.json'
    $needsNpmCi = (-not (Test-Path $npmMarker)) -or ($storedHash -ne $lockHash)
    if ($needsNpmCi) {
        $vitePort = [int]$p.viteDevServerPort
        $stoppedVite = Stop-SiteVite -Repo $repo -Port $vitePort
        $run = Invoke-Native { npm ci --no-audit --no-fund }
        if ($run.Code -ne 0) { Stop-Preview 1 "npm ci failed: $($run.Output)" }
        if (-not (Test-Path (Join-Path $repo 'node_modules'))) {
            Stop-Preview 1 'npm ci finished but node_modules is missing.'
        }
        Set-Content -Path $lockHashPath -Value $lockHash -Encoding ascii -NoNewline
        Write-Ok 'Ran npm ci (lockfile content changed)'
        if ($stoppedVite -and -not $NoVite) {
            Start-Process -FilePath 'cmd.exe' -ArgumentList '/c', 'npm run dev' -WorkingDirectory $repo -WindowStyle Minimized
            Write-Ok "Restarted npm run dev on port $vitePort"
        }
    } else { Write-Ok 'npm dependencies are current' }
    $pending = Invoke-Native { php artisan migrate:status --pending --no-ansi }
    $hasPending = $pending.Code -eq 0 -and $pending.Output -match '\bPending\b' -and $pending.Output -notmatch 'No pending migrations'
    if ($hasPending) {
        $appEnv = Invoke-Native { php artisan env --no-ansi }
        if ($appEnv.Output -notmatch '\[local\]') {
            Stop-Preview 1 "Pending migrations exist but APP_ENV is not local (got: $($appEnv.Output)). Refusing to migrate."
        }
        Write-Step 'Pending migrations'
        $migrate = Invoke-Native { php artisan migrate --force --no-interaction }
        if ($migrate.Code -ne 0) { Stop-Preview 1 "php artisan migrate failed: $($migrate.Output)" }
        Write-Ok 'Ran pending migrations'
    } else {
        Write-Ok 'No pending migrations'
    }
    $seeders = @([regex]::Matches((Get-Content 'deploy\cloudron\activate-release.sh' -Raw), "db:seed --class='([^']+)'") | ForEach-Object { $_.Groups[1].Value })
    if ($seeders.Count -eq 0) { Stop-Preview 1 'Found no db:seed --class lines in deploy\cloudron\activate-release.sh.' }
    foreach ($seeder in $seeders) {
        $seed = Invoke-Native { php artisan db:seed "--class=$seeder" --force --no-interaction }
        if ($seed.Code -ne 0) { Stop-Preview 1 "php artisan db:seed --class=$seeder failed: $($seed.Output)" }
    }
    $reset = Invoke-Native { php artisan permission:cache-reset }
    if ($reset.Code -ne 0) { Stop-Preview 1 "php artisan permission:cache-reset failed: $($reset.Output)" }
    Write-Ok "Synced roles and permissions ($($seeders.Count) deploy seeders, as on Cloudron)"

    # 3. Herd and the preview ----------------------------------------------------
    Write-Step "Herd preview $($p.appUrl)"
    $missing = @('Herd', 'nginx' | Where-Object { -not (Get-Process $_ -ErrorAction SilentlyContinue) })
    if ($missing.Count -gt 0) { Stop-Preview 1 "Not running: $($missing -join ', '). Open Herd." }
    if (-not (Test-LocalPortOpen -Port ([int]$p.database.port))) { Stop-Preview 1 "Nothing listens on MySQL's port $($p.database.port). Start Herd's MySQL service (Herd > Services), or: herd services:start <mysql-id>." }
    $health = Get-Health $p.appUrl
    if ($null -eq $health -or $null -eq $health.local) { Stop-Preview 1 "$($p.appUrl)/healthz did not answer with the local block. Is APP_ENV=local in .env, and is the site linked (herd links)?" }
    if ($health.local.git_revision -ne $head.Substring(0, 12)) {
        Invoke-Native { php artisan optimize:clear } | Out-Null
        $health = Get-Health $p.appUrl
    }
    if ($health.local.git_revision -ne $head.Substring(0, 12)) { Stop-Preview 1 "The preview serves $($health.local.git_revision), not $($head.Substring(0, 12)). Check 'herd links' shows $($p.herdSite) pointing at this clone." }
    if ($health.local.php_sapi -ne 'cgi-fcgi') { Stop-Preview 1 "The preview runs PHP as $($health.local.php_sapi), not FastCGI (cgi-fcgi)." }
    if ($health.local.php -notlike "$($p.php).*") { Stop-Preview 1 "The preview runs PHP $($health.local.php), not $($p.php). Pin the site with: herd isolate $($p.php) --site=$($p.herdSite)" }
    Write-Ok "Serves $($head.Substring(0, 12)) on PHP $($health.local.php) through FastCGI"
    if ($health.status -ne 'ok') {
        $failed = ($health.checks.PSObject.Properties | Where-Object { $_.Value -ne 'ok' } | ForEach-Object { $_.Name }) -join ', '
        Write-Warn "/healthz reports $($health.status) ($failed). The preview still works; add that Herd service if the task needs it."
    }

    # 4. Vite ------------------------------------------------------------------
    Write-Step "Vite on port $($p.viteDevServerPort)"
    if (Test-LocalPortOpen -Port ([int]$p.viteDevServerPort)) {
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
