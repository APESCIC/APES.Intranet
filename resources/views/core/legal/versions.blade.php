<x-layouts.app :title="$page->title.' — versions'" subtitle="Published versions of this page.">
    <article class="legal-versions sg-card max-w-3xl space-y-4 p-6">
        <p class="text-sm">
            <a href="{{ route($page->showRouteName()) }}">Back to current {{ $page->title }}</a>
        </p>

        @if ($versions === [])
            <p class="text-sg-muted">No published versions are in effect yet.</p>
        @else
            <ul class="divide-y divide-sg-line">
                @foreach ($versions as $version)
                    <li class="py-3">
                        <p class="font-medium">Version {{ $version->number }}</p>
                        <p class="text-sm text-sg-muted">
                            @if ($version->in_effect_at)
                                In effect from {{ $version->in_effect_at->timezone(config('app.timezone'))->format('j F Y, H:i') }}
                            @else
                                In effect from publication
                            @endif
                            @if ($version->change_summary)
                                · {{ $version->change_summary }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
</x-layouts.app>