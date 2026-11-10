<div class="overflow-x-auto">
    <table class="sg-table">
        <thead>
            <tr>
                <th scope="col">Checklist</th>
                <th scope="col">Shift</th>
                @if ($showMember)<th scope="col">Member</th>@endif
                <th scope="col">Due by</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($runs as $run)
                <tr data-checklist-run="{{ $run->id }}">
                    <td><a href="{{ route('operations.checklists.runs.show', $run) }}">{{ $run->template->name }}</a></td>
                    <td>{{ $run->shift->label() }}</td>
                    @if ($showMember)<td>{{ $run->shift->member?->name ?? 'Open shift' }}</td>@endif
                    <td>{{ $run->due_at->format('D j M H:i') }}</td>
                    <td>
                        @if ($run->isComplete())
                            <span class="sg-badge sg-badge--ok">Done {{ $run->completed_at->format('H:i') }}</span>
                        @elseif ($run->isMissed())
                            <span class="sg-badge sg-badge--error">Missed</span>
                        @else
                            <span class="sg-badge">To do</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $showMember ? 5 : 4 }}" class="text-sg-muted">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
