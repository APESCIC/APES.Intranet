<x-layouts.app title="Change log" subtitle="What changed in each release of the intranet.">
    <p class="mb-4 text-sm text-sg-muted">
        You are using the intranet <strong>{{ $current === \App\Core\Version\AppVersion::DEV ? 'development version' : $current }}</strong>.
    </p>

    <nav class="changelog-filter mb-6" aria-label="Filter by type of change">
        <ul>
            <li><a href="{{ route('change-log.index') }}" @if ($type === null) aria-current="page" @endif>All changes</a></li>
            @foreach ($types as $option)
                <li>
                    <a href="{{ route('change-log.index', ['type' => $option]) }}" @if ($type === $option) aria-current="page" @endif>{{ \App\Core\Changelog\Release::typeLabel($option) }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    @if ($releases === null)
        <p class="sg-card p-5" role="status">The change log is not available right now. Please try again later.</p>
    @elseif ($releases->isEmpty())
        <p class="sg-card p-5" role="status">
            {{ $type ? 'No releases have '.strtolower(\App\Core\Changelog\Release::typeLabel($type)).' changes for you yet.' : 'There are no release notes for you yet.' }}
        </p>
    @else
        <ul class="grid gap-4" aria-label="Releases, newest first">
            @foreach ($releases as $release)
                <li>
                    @include('core.change-log.partials.release', ['release' => $release, 'current' => $current, 'seesDrafts' => $seesDrafts, 'only' => $type, 'linked' => true])
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
