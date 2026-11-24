<x-layouts.app :title="$page->title" subtitle="Draft preview">
    <p class="mb-4 text-sm">
        <a href="{{ route('admin.legal.pages.index') }}" class="underline">Legal pages</a>
        ·
        <a href="{{ route('admin.legal.pages.edit', $page) }}" class="underline">Back to editor</a>
    </p>

    <nav class="mb-6 flex flex-wrap gap-2" role="tablist" aria-label="Editor views">
        <a
            href="{{ route('admin.legal.pages.edit', $page) }}"
            class="sg-btn sg-btn-ghost sg-btn-sm"
            role="tab"
            aria-selected="false"
        >Write</a>
        <span class="sg-btn sg-btn-secondary sg-btn-sm" role="tab" aria-selected="true">Preview</span>
    </nav>

    <p class="mb-4 rounded-sg-field bg-sg-warn-mist p-3 text-sm text-sg-warn" role="status">
        This is a draft preview. Visitors only see the published version.
    </p>

    <article class="legal-page sg-card max-w-3xl space-y-6 p-6">
        <header class="legal-page__meta space-y-2 border-b border-sg-line pb-4">
            <p class="text-sm text-sg-muted">
                Draft version {{ $version->number }}
                @if ($version->in_effect_at)
                    · Planned in effect from {{ $version->in_effect_at->timezone(config('app.timezone'))->format('j F Y') }}
                @endif
            </p>
            @if ($version->change_summary)
                <p class="text-sm">{{ $version->change_summary }}</p>
            @endif
        </header>

        @if ($contents !== [])
            <nav class="legal-page__toc rounded-sg-field bg-sg-mist p-4" aria-label="On this page">
                <h2 class="font-serif text-lg font-semibold">On this page</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($contents as $item)
                        <li><a href="#{{ $item['id'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($body !== '')
            <div class="legal-page__body prose-sg max-w-none space-y-4">
                {!! $body !!}
            </div>
        @endif

        @if ($cookieRegister)
            @include('core.legal._cookie-register')
        @endif
    </article>

    <style>
        @media print {
            .legal-page__toc {
                break-inside: avoid;
            }
        }
    </style>
</x-layouts.app>