<x-layouts.app title="Terms acceptance" subtitle="Who has accepted the current published Terms of use version.">
    @php
        $versionLabel = $version !== null ? 'Version '.$version->number : null;
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-sg-muted">
                @if ($page !== null && $versionLabel !== null)
                    Current Terms of use: <span class="font-medium text-sg-ink">{{ $versionLabel }}</span>
                @else
                    No Terms of use page requires acceptance yet.
                @endif
            </p>
            <dl class="mt-3 flex flex-wrap gap-6 text-sm">
                <div>
                    <dt class="text-sg-muted">Accepted</dt>
                    <dd class="text-lg font-semibold text-sg-ink" data-accepted-count>{{ $accepted_count }}</dd>
                </div>
                <div>
                    <dt class="text-sg-muted">Not yet</dt>
                    <dd class="text-lg font-semibold text-sg-ink" data-pending-count>{{ $pending_count }}</dd>
                </div>
            </dl>
        </div>
        <a
            href="{{ route('admin.legal.acceptance.export', $filter === 'all' ? [] : ['status' => $filter]) }}"
            class="sg-btn sg-btn-secondary"
        >Export CSV</a>
    </div>

    <nav class="mb-4 flex flex-wrap gap-2 text-sm" aria-label="Filter by acceptance status">
        @foreach (['all' => 'All', 'accepted' => 'Accepted', 'pending' => 'Not yet'] as $key => $label)
            <a
                href="{{ $key === 'all' ? route('admin.legal.acceptance.index') : route('admin.legal.acceptance.index', ['status' => $key]) }}"
                @class([
                    'sg-btn sg-btn-sm',
                    $filter === $key ? 'sg-btn-primary' : 'sg-btn-ghost',
                ])
                @if ($filter === $key) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </nav>

    <x-admin.table-card>
        <table class="sg-table w-full text-left text-sm">
            <caption class="sr-only">Terms of use acceptance by person</caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Status</th>
                    <th scope="col">Accepted at</th>
                    <th scope="col">Version</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <th scope="row" class="font-medium text-sg-ink">{{ $row['user']->name }}</th>
                        <td class="text-sg-muted">{{ $row['user']->email }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td class="text-sg-muted">
                            {{ $row['accepted_at']?->timezone(config('app.timezone'))->format('j M Y, H:i') ?? '—' }}
                        </td>
                        <td class="text-sg-muted">{{ $row['version_number'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-sg-muted">No people match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.table-card>
</x-layouts.app>