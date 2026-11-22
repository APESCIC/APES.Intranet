<x-layouts.app :title="$title">
    <div class="mx-auto max-w-xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">{{ $title }}</h1>
        <p class="mt-3 text-ink-muted">
            Check a certificate verify code. This page never shows grades.
        </p>

        @if ($certificate === null)
            <p class="mt-8 rounded border border-border bg-panel p-4 text-ink" role="status">
                No certificate matches code <strong>{{ $code }}</strong>.
            </p>
        @elseif ($certificate->isRevoked())
            <p class="mt-8 rounded border border-border bg-panel p-4 text-ink" role="status">
                Certificate <strong>{{ $certificate->verify_code }}</strong> has been revoked
                (usually after expiry and retraining).
            </p>
        @elseif ($certificate->isExpired())
            <p class="mt-8 rounded border border-border bg-panel p-4 text-ink" role="status">
                Certificate <strong>{{ $certificate->verify_code }}</strong> for
                {{ $certificate->course->title }} expired on
                {{ $certificate->expires_on->format('j F Y') }}.
            </p>
        @else
            <div class="mt-8 rounded border border-border bg-panel p-4 text-ink" role="status">
                <p class="font-display text-xl">Valid certificate</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div>
                        <dt class="text-ink-muted">Learner</dt>
                        <dd>{{ $certificate->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Course</dt>
                        <dd>{{ $certificate->course->title }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Issued</dt>
                        <dd>{{ $certificate->issued_at?->timezone(config('app.timezone'))->format('j F Y') }}</dd>
                    </div>
                    @if ($certificate->cpd_hours !== null)
                        <div>
                            <dt class="text-ink-muted">CPD hours</dt>
                            <dd>{{ $certificate->cpd_hours }}</dd>
                        </div>
                    @endif
                    @if ($certificate->expires_on !== null)
                        <div>
                            <dt class="text-ink-muted">Expires</dt>
                            <dd>{{ $certificate->expires_on->format('j F Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-ink-muted">Verify code</dt>
                        <dd class="font-mono tracking-wide">{{ $certificate->verify_code }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>
</x-layouts.app>
