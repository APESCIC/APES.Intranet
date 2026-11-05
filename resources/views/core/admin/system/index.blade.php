@php
    $statusLabels = [
        'ok' => ['OK', 'bg-sg-ok-mist text-sg-ok'],
        'down' => ['Down', 'bg-sg-error-mist text-sg-error'],
        'not_configured' => ['Not set up', 'bg-sg-info-mist text-sg-muted'],
        'not_checked' => ['Not checked', 'bg-sg-info-mist text-sg-muted'],
        'not_run' => ['Not run yet', 'bg-sg-warn-mist text-sg-warn'],
    ];
@endphp
<x-layouts.app title="System status" subtitle="The Cloudron addons the intranet depends on. Checked when you open this page and every 5 minutes. Secret values are never shown.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

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

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($addons as $addon)
            @php([$statusText, $statusClass] = $statusLabels[$addon['status']] ?? [$addon['status'], 'bg-sg-info-mist text-sg-muted'])
            <section class="sg-card p-5" aria-labelledby="addon-{{ $addon['key'] }}" data-addon="{{ $addon['key'] }}">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="addon-{{ $addon['key'] }}" class="font-serif text-lg font-semibold">{{ $addon['label'] }}</h2>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $statusClass }}" data-status="{{ $addon['status'] }}">{{ $statusText }}</span>
                </div>

                <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                    @if ($addon['present'] !== null)
                        <dt class="text-sg-muted">Present</dt>
                        <dd>{{ $addon['present'] ? 'Yes' : 'No' }}</dd>
                    @endif
                    @if ($addon['details']['host'])
                        <dt class="text-sg-muted">Host</dt>
                        <dd><code>{{ $addon['details']['host'] }}</code></dd>
                    @endif
                    @if ($addon['details']['port'])
                        <dt class="text-sg-muted">Port</dt>
                        <dd><code>{{ $addon['details']['port'] }}</code></dd>
                    @endif
                    @if ($addon['details']['database'])
                        <dt class="text-sg-muted">Database</dt>
                        <dd><code>{{ $addon['details']['database'] }}</code></dd>
                    @endif
                    <dt class="text-sg-muted">Last check</dt>
                    <dd>{{ $addon['checkedAt']?->timezone('Europe/London')->format('j M Y, H:i:s') ?? 'Never' }}</dd>
                </dl>

                @if ($addon['variables'] !== [])
                    <h3 class="mt-4 text-sm font-semibold">Variables</h3>
                    <ul class="mt-1 space-y-1 text-sm">
                        @foreach ($addon['variables'] as $name => $set)
                            <li>
                                <code>{{ $name }}</code>
                                <span class="text-sg-muted">{{ $set ? '•••••• (set)' : 'not set' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($addon['key'] === 'scheduler')
                    <p class="mt-3 text-sm text-sg-muted">
                        The scheduler runs the checks every 5 minutes. "Down" means it has not run for
                        {{ \App\Core\System\SystemStatus::SCHEDULER_STALE_MINUTES }} minutes.
                    </p>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.app>
