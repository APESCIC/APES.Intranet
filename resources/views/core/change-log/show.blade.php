<x-layouts.app :title="'Change log: '.$release->version" subtitle="{{ $release->title }}">
    <p class="mb-4"><a href="{{ route('change-log.index') }}">Back to all releases</a></p>

    @include('core.change-log.partials.release', ['release' => $release, 'current' => $current, 'seesDrafts' => $seesDrafts, 'only' => null, 'linked' => false])
</x-layouts.app>
