@if ($rows->isEmpty())
    <p class="text-sg-muted">{{ $empty }}</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-sg-border text-sg-muted">
                <tr>
                    <th class="py-2 pr-3 font-medium" scope="col">Person</th>
                    <th class="py-2 pr-3 font-medium" scope="col">Type</th>
                    <th class="py-2 pr-3 font-medium" scope="col">Reference</th>
                    <th class="py-2 pr-3 font-medium" scope="col">Expires</th>
                    <th class="py-2 font-medium" scope="col">Evidence</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $check)
                    <tr class="border-b border-sg-border/60" data-check="{{ $check->id }}">
                        <td class="py-2 pr-3">
                            <a class="underline" href="{{ route('hr.records.show', $check->user) }}">{{ $check->user->name }}</a>
                        </td>
                        <td class="py-2 pr-3">{{ $check->typeLabel() }}</td>
                        <td class="py-2 pr-3">{{ $check->reference ?: '—' }}</td>
                        <td class="py-2 pr-3">{{ optional($check->expires_on)->format('d M Y') ?: '—' }}</td>
                        <td class="py-2">
                            @if ($check->document_url)
                                <a class="underline" href="{{ $check->document_url }}" target="_blank" rel="noopener">Link<span class="sr-only"> (opens in a new tab)</span></a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
