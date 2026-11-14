<x-layouts.app title="Give recognition" subtitle="Thank a colleague. Posting to the updates feed is optional.">
    @if ($errors->any())
        <div class="mb-4 rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="sg-card max-w-xl space-y-3 p-6">
        <form method="post" action="{{ route('hr.recognition.store') }}" class="grid gap-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium" for="recipient_id">Colleague</label>
                <select id="recipient_id" name="recipient_id" class="sg-input" required>
                    <option value="">Choose…</option>
                    @foreach ($members as $member)
                        @if ($member->id !== auth()->id())
                            <option value="{{ $member->id }}" @selected((string) old('recipient_id') === (string) $member->id)>{{ $member->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="message">Message</label>
                <textarea id="message" name="message" class="sg-input min-h-24" required maxlength="1000">{{ old('message') }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="post_to_feed" value="1" @checked(old('post_to_feed'))>
                Also post this to the updates feed
            </label>
            <button class="sg-btn sg-btn-primary" type="submit">Give recognition</button>
        </form>
    </section>
</x-layouts.app>
