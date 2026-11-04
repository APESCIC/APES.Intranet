<x-layouts.app title="QA role switcher">
    <h1 class="font-serif text-3xl font-semibold">QA role switcher</h1>
    <p class="mt-2 text-sg-muted">
        Local only. Signs you in as a fake <code>@example.test</code> account with one role.
    </p>

    @if ($current)
        <p class="mt-4">
            Signed in as <strong>{{ $current->name }}</strong> ({{ $current->email }}).
        </p>
    @endif

    <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Sign in as a role">
        @foreach ($roles as $role)
            <form method="post" action="{{ route('qa.login') }}">
                @csrf
                <input type="hidden" name="role" value="{{ $role->value }}">
                <button
                    type="submit"
                    class="rounded-full bg-sg-teal px-4 py-2 font-semibold text-sg-white hover:bg-sg-teal-dark"
                >{{ $role->label() }}</button>
            </form>
        @endforeach
    </div>

    @if ($current)
        <form method="post" action="{{ route('qa.logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="font-semibold text-sg-teal underline">Sign out</button>
        </form>
    @endif
</x-layouts.app>
