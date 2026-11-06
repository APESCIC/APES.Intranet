<x-layouts.app title="Policies" subtitle="Index of policy documents held in SharePoint.">
    @if ($canManage && $reviewDue->isNotEmpty())
        <section class="sg-card mb-6 p-5" aria-labelledby="review-due">
            <h2 id="review-due" class="font-serif text-xl font-semibold">Review due soon</h2>
            <ul class="mt-3 list-disc pl-5">
                @foreach ($reviewDue as $item)
                    <li>{{ $item->title }} — due {{ $item->review_due_on?->format('j M Y') }}</li>
                @endforeach
            </ul>
        </section>
    @endif
    <ul class="grid gap-4">
        @foreach ($policies as $policy)
            <li class="sg-card p-5">
                <h2 class="font-serif text-xl font-semibold">{{ $policy->title }}</h2>
                <p class="mt-1 text-sm text-sg-muted">Owner: {{ $policy->owner_name ?: 'Not set' }}@if($policy->review_due_on) · Review by {{ $policy->review_due_on->format('j M Y') }}@endif</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ $policy->sharepoint_url }}" target="_blank" rel="noopener">
                        Open in SharePoint<span class="sr-only"> (opens in a new tab)</span>
                        <i data-lucide="external-link"></i>
                    </a>
                    <form method="post" action="{{ route('communications.policies.read', $policy) }}">
                        @csrf
                        <button class="sg-btn sg-btn-ghost sg-btn-sm" type="submit">Mark as read</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
    @if ($canManage)
        <form method="post" action="{{ route('communications.policies.store') }}" class="sg-card mt-8 grid max-w-3xl gap-3 p-5">
            @csrf
            <h2 class="font-serif text-xl font-semibold">Add policy index entry</h2>
            <input class="sg-input" name="title" placeholder="Title" required>
            <input class="sg-input" name="sharepoint_url" placeholder="https://….sharepoint.com/…" required>
            <input class="sg-input" name="owner_name" placeholder="Owner">
            <input class="sg-input" type="date" name="review_due_on">
            <button class="sg-btn sg-btn-primary sg-btn-sm" type="submit">Add</button>
        </form>
    @endif
</x-layouts.app>
