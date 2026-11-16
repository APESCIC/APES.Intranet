<x-layouts.app title="My grades">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">My grades</h1>
        <p class="mt-2 text-ink-muted">Released grades and results only. Unreleased marks stay hidden.</p>

        <div class="mt-8 overflow-x-auto">
            <table class="w-full text-left text-sm" aria-label="Released grades">
                <thead>
                    <tr class="border-b border-border text-ink-muted">
                        <th scope="col" class="py-2 pr-4 font-semibold">Course</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">Item</th>
                        <th scope="col" class="py-2 pr-4 font-semibold">Score</th>
                        <th scope="col" class="py-2 font-semibold">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b border-border">
                            <td class="py-3 pr-4 text-ink">{{ $row['course'] }}</td>
                            <td class="py-3 pr-4 text-ink">{{ $row['item'] }}</td>
                            <td class="py-3 pr-4 text-ink">{{ $row['score'] === null ? '—' : $row['score'] }}</td>
                            <td class="py-3 text-ink">
                                @if ($row['passed'] === null)
                                    —
                                @elseif ($row['passed'])
                                    Pass
                                @else
                                    Fail
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-ink-muted">No released grades yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
