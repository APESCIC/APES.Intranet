<x-layouts.app :title="$page->title" :subtitle="$version->change_summary">
    <article class="legal-page sg-card max-w-3xl space-y-6 p-6">
        <header class="legal-page__meta space-y-2 border-b border-sg-line pb-4">
            <p class="text-sm text-sg-muted">
                Version {{ $version->number }}
                @if ($version->in_effect_at)
                    · In effect from {{ $version->in_effect_at->timezone(config('app.timezone'))->format('j F Y') }}
                @else
                    · In effect now
                @endif
            </p>
            @if ($version->change_summary)
                <p class="text-sm">{{ $version->change_summary }}</p>
            @endif
            <p class="text-sm">
                <a href="{{ route('legal.versions', $page->slug) }}">Previous versions</a>
            </p>
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

        <div class="legal-page__body prose-sg max-w-none space-y-4">
            {!! $body !!}
        </div>
    </article>

    <style>
        @media print {
            .legal-page__toc {
                break-inside: avoid;
            }

            .legal-page__meta a {
                display: none;
            }
        }
    </style>
</x-layouts.app>