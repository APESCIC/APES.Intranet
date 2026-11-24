<x-layouts.app :title="$page->title.' — history'" subtitle="All versions of this legal page.">
    <p class="mb-4 text-sm">
        <a href="{{ route('admin.legal.pages.edit', $page) }}" class="underline">Back to edit</a>
        ·
        <a href="{{ route('admin.legal.pages.index') }}" class="underline">Legal pages</a>
    </p>

    <x-admin.table-card>
        <table class="sg-table w-full text-left text-sm">
            <caption class="sr-only">Version history for {{ $page->title }}</caption>
            <thead>
                <tr>
                    <th scope="col">Version</th>
                    <th scope="col">Status</th>
                    <th scope="col">Summary</th>
                    <th scope="col">In effect</th>
                    <th scope="col">Published</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($versions as $version)
                    <tr>
                        <th scope="row" class="font-medium text-sg-ink">{{ $version->number }}</th>
                        <td class="capitalize">{{ $version->status }}</td>
                        <td class="text-sg-muted">{{ $version->change_summary ?? '—' }}</td>
                        <td class="text-sg-muted">
                            @if ($version->in_effect_at)
                                {{ $version->in_effect_at->timezone(config('app.timezone'))->format('j M Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-sg-muted">
                            @if ($version->published_at)
                                {{ $version->published_at->timezone(config('app.timezone'))->format('j M Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="space-x-2">
                            @if ($draft && $version->id !== $draft->id)
                                <a
                                    class="text-sm underline"
                                    href="{{ route('admin.legal.pages.compare', ['legalPage' => $page, 'from' => $version->id, 'to' => $draft->id]) }}"
                                >Compare to draft</a>
                            @endif
                            @if ($version->status !== \App\Core\Legal\LegalPageVersionStatus::Draft)
                                <form
                                    method="post"
                                    action="{{ route('admin.legal.pages.restore', [$page, $version]) }}"
                                    class="inline"
                                    onsubmit="return confirm('Restore version {{ $version->number }} into the open draft? Published history will not change.');"
                                >
                                    @csrf
                                    <button type="submit" class="text-sm underline">Restore as draft</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.table-card>
</x-layouts.app>