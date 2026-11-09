@php
    $headingId = 'release-'.str_replace('.', '-', $release->version);
    $types = $only ? [$only] : $release->changeTypes();
@endphp
<article class="sg-card changelog-release p-5" aria-labelledby="{{ $headingId }}">
    <header class="changelog-release__header">
        <h2 id="{{ $headingId }}" class="font-serif text-xl font-semibold">
            @if ($linked)
                <a href="{{ route('change-log.show', $release->version) }}">{{ $release->version }} · {{ $release->title }}</a>
            @else
                {{ $release->version }} · {{ $release->title }}
            @endif
        </h2>
        <p class="changelog-release__meta">
            <span>Released <time datetime="{{ $release->released_at->toDateString() }}">{{ $release->released_at->format('j F Y') }}</time></span>
            @if ($release->version === $current)
                <span class="changelog-badge changelog-badge--current">Current version</span>
            @endif
            @if ($seesDrafts && ! $release->is_published)
                <span class="changelog-badge changelog-badge--draft">Draft, admins only</span>
            @endif
        </p>
    </header>

    <p class="mt-2">{{ $release->summary }}</p>

    @foreach ($types as $type)
        <h3 class="changelog-release__type">{{ \App\Core\Changelog\Release::typeLabel($type) }}</h3>
        <ul class="changelog-release__list">
            @foreach ($release->changesOfType($type) as $change)
                <li>{{ $change }}</li>
            @endforeach
        </ul>
    @endforeach

    @if ($seesDrafts && ! $linked && ! empty($release->internal_notes))
        <section class="changelog-release__internal" aria-labelledby="{{ $headingId }}-internal">
            <h3 id="{{ $headingId }}-internal" class="changelog-release__type">Internal notes, admins only</h3>
            <ul class="changelog-release__list">
                @foreach ($release->internal_notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </section>
    @endif
</article>
