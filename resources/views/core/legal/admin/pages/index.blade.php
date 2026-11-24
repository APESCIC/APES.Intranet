<x-layouts.app title="Legal pages" subtitle="Draft and preview privacy, cookies, terms and related pages. Publishing is a separate step.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <x-admin.table-card>
        <table class="sg-table w-full text-left text-sm">
            <caption class="sr-only">Legal pages</caption>
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Status</th>
                    <th scope="col">Version</th>
                    <th scope="col">In effect</th>
                    <th scope="col">Who can see it</th>
                    <th scope="col">Acceptance</th>
                    <th scope="col">Last edited</th>
                    <th scope="col">Review due</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <th scope="row" class="font-medium text-sg-ink">
                            {{ $row['page']->title }}
                            @if ($row['has_draft'])
                                <span class="ml-1 text-xs font-normal text-sg-muted">(draft open)</span>
                            @endif
                        </th>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['version_label'] }}</td>
                        <td class="text-sg-muted">{{ $row['in_effect'] ?? '—' }}</td>
                        <td>{{ $row['visibility_label'] }}</td>
                        <td>{{ $row['acceptance'] }}</td>
                        <td class="text-sg-muted">{{ $row['last_edited'] ?? '—' }}</td>
                        <td class="text-sg-muted">{{ $row['review_due'] ?? '—' }}</td>
                        <td>
                            <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('admin.legal.pages.edit', $row['page']) }}">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-sg-muted">No legal pages yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.table-card>
</x-layouts.app>