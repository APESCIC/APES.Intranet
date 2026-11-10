<x-layouts.app title="Form submissions sent to you" subtitle="Submissions routed to you or to a role you hold.">
    @include('operations::partials.flash')

    <section class="sg-card p-6" aria-labelledby="inbox-heading">
        <h2 id="inbox-heading" class="sr-only">Submissions</h2>
        <div class="overflow-x-auto">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Form</th><th scope="col">From</th><th scope="col">Sent</th></tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td><a href="{{ route('operations.forms.submissions.show', $submission) }}">{{ $submission->form->title }}</a></td>
                            <td>{{ $submission->submitter?->name ?? 'Former member' }}</td>
                            <td>{{ $submission->created_at->format('D j M H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-sg-muted">Nothing has been sent to you.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $submissions->links() }}</div>
    </section>
</x-layouts.app>
