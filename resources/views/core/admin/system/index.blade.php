@php
    $statusLabels = [
        'ok' => ['OK', 'sg-badge--ok'],
        'down' => ['Down', 'sg-badge--error'],
        'not_configured' => ['Not set up', ''],
        'not_checked' => ['Not checked', ''],
        'not_run' => ['Not run yet', 'sg-badge--warn'],
    ];
    $checksPassing = collect($addons)->where('status', 'ok')->count();
    $checksTotal = count($addons);
    $coreVersion = collect($versions)->firstWhere('slug', 'core')['version'] ?? 'unknown';
    $queueDriver = config('queue.default', 'sync');
@endphp
<x-layouts.app title="System status" subtitle="The Cloudron addons the intranet depends on. Checked when you open this page and every 5 minutes. Secret values are never shown.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

    <section class="mb-5" aria-label="Status summary">
        <ul class="sg-status-tiles">
            <li class="sg-card sg-status-tile" data-summary="checks">
                <p class="sg-status-tile__label">Checks passing</p>
                <p class="sg-status-tile__value">{{ $checksPassing }} / {{ $checksTotal }}</p>
            </li>
            <li class="sg-card sg-status-tile" data-summary="version">
                <p class="sg-status-tile__label">Running version</p>
                <p class="sg-status-tile__value"><code>{{ $coreVersion }}</code></p>
            </li>
            <li class="sg-card sg-status-tile" data-summary="backup">
                <p class="sg-status-tile__label">Last backup</p>
                <p class="sg-status-tile__value">Managed in Cloudron</p>
                <p class="sg-status-tile__hint">App backups are taken and listed in Cloudron, not here.</p>
            </li>
            <li class="sg-card sg-status-tile" data-summary="queue">
                <p class="sg-status-tile__label">Queue</p>
                <p class="sg-status-tile__value"><code>{{ $queueDriver }}</code></p>
            </li>
        </ul>
    </section>

    <section class="sg-card mb-4 p-5" aria-labelledby="versions-heading">
        <h2 id="versions-heading" class="font-serif text-lg font-semibold">Versions</h2>
        <p class="mt-2 text-sm text-sg-muted">Core is the release tag. Each module has its own SemVer in its manifest.</p>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm" data-versions>
            @foreach ($versions as $row)
                <dt class="text-sg-muted" data-version-slug="{{ $row['slug'] }}">{{ $row['name'] }}</dt>
                <dd><code>{{ $row['version'] }}</code></dd>
            @endforeach
        </dl>
    </section>

    <section class="sg-card mb-4 flex flex-wrap items-center justify-between gap-3 p-5" aria-labelledby="test-email-heading">
        <div>
            <h2 id="test-email-heading" class="font-serif text-lg font-semibold">Test email</h2>
            <p class="text-sm text-sg-muted">
                Sends a test email to you at {{ auth()->user()->email }}, to prove Sendmail works.
                Up to {{ \App\Core\System\Http\Controllers\SendTestEmailController::PER_HOUR }} an hour.
            </p>
        </div>
        <form method="post" action="{{ route('admin.system.test-email') }}">
            @csrf
            <button type="submit" class="sg-btn sg-btn-secondary">Send me a test email</button>
        </form>
    </section>

    <section class="sg-card mb-4 p-5" aria-labelledby="directory-sync-heading">
        <h2 id="directory-sync-heading" class="font-serif text-lg font-semibold">Directory sync</h2>
        <p class="mt-2 text-sm text-sg-muted">
            Nightly sync creates, updates and deactivates members from the Cloudron read-only API.
            Leavers lose their intranet sessions at the next run.
        </p>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm" data-directory-source>
            <dt class="text-sg-muted">Active source</dt>
            <dd><code>{{ $directorySource }}</code></dd>
        </dl>
        @if ($directorySync)
            <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                <dt class="text-sg-muted">Last run</dt>
                <dd>{{ $directorySync->occurred_at?->timezone('Europe/London')->format('j M Y, H:i:s') }}</dd>
                @foreach (($directorySync->context ?? []) as $key => $value)
                    <dt class="text-sg-muted">{{ ucfirst($key) }}</dt>
                    <dd>{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                @endforeach
            </dl>
        @else
            <p class="mt-3 text-sm text-sg-muted">No directory sync has been recorded yet.</p>
        @endif
    </section>

    <section class="sg-card p-5" aria-labelledby="checks-heading">
        <h2 id="checks-heading" class="font-serif text-lg font-semibold">Checks</h2>
        <p class="mt-2 text-sm text-sg-muted">Expand a row for host details and Cloudron variable names. Values are never shown.</p>

        <ul class="sg-status-checks mt-4" data-status-checks>
            @foreach ($addons as $addon)
                @php([$statusText, $badgeClass] = $statusLabels[$addon['status']] ?? [$addon['status'], ''])
                <li class="sg-status-check" data-addon="{{ $addon['key'] }}">
                    <div class="sg-status-check__summary">
                        <h3 class="sg-status-check__title" id="addon-{{ $addon['key'] }}">{{ $addon['label'] }}</h3>
                        <span class="sg-badge {{ $badgeClass }}" data-status="{{ $addon['status'] }}">{{ $statusText }}</span>
                    </div>

                    <dl class="sg-status-check__meta">
                        @if ($addon['present'] !== null)
                            <div>
                                <dt>Present</dt>
                                <dd>{{ $addon['present'] ? 'Yes' : 'No' }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt>Last check</dt>
                            <dd>{{ $addon['checkedAt']?->timezone('Europe/London')->format('j M Y, H:i:s') ?? 'Never' }}</dd>
                        </div>
                    </dl>

                    @if ($addon['details']['host'] || $addon['details']['port'] || $addon['details']['database'] || $addon['variables'] !== [])
                        <details class="sg-status-check__details">
                            <summary>Details and Cloudron variables</summary>
                            <dl class="sg-status-check__detail-list">
                                @if ($addon['details']['host'])
                                    <div>
                                        <dt>Host</dt>
                                        <dd><code>{{ $addon['details']['host'] }}</code></dd>
                                    </div>
                                @endif
                                @if ($addon['details']['port'])
                                    <div>
                                        <dt>Port</dt>
                                        <dd><code>{{ $addon['details']['port'] }}</code></dd>
                                    </div>
                                @endif
                                @if ($addon['details']['database'])
                                    <div>
                                        <dt>Database</dt>
                                        <dd><code>{{ $addon['details']['database'] }}</code></dd>
                                    </div>
                                @endif
                            </dl>
                            @if ($addon['variables'] !== [])
                                <ul class="sg-status-check__variables">
                                    @foreach ($addon['variables'] as $name => $set)
                                        <li>
                                            <code>{{ $name }}</code>
                                            <span class="text-sg-muted">{{ $set ? '•••••• (set)' : 'not set' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </details>
                    @endif

                    @if ($addon['key'] === 'scheduler')
                        <p class="mt-3 text-sm text-sg-muted">
                            The scheduler runs the checks every 5 minutes. "Down" means it has not run for
                            {{ \App\Core\System\SystemStatus::SCHEDULER_STALE_MINUTES }} minutes.
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
