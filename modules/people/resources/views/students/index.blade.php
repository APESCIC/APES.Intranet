<x-layouts.app title="Student placements" subtitle="Placement details and hours.">
    <ul class="space-y-3">
        @forelse ($placements as $placement)
            <li>
                <a href="{{ route('people.students.show', $placement) }}" class="sg-card block p-4">
                    <p class="font-semibold">{{ $placement->title }}</p>
                    <p class="text-sm text-sg-muted">{{ $placement->student->name }}@if($placement->organisation) · {{ $placement->organisation }}@endif</p>
                </a>
            </li>
        @empty
            <li class="sg-card p-6 text-sg-muted">No placements to show.</li>
        @endforelse
    </ul>
</x-layouts.app>
