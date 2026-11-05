<x-layouts.app title="QA role switcher" subtitle="Local only. Signs you in as a fake @example.test account in one approved group, or a common stack of groups.">
    @if ($current)
        <p>
            Signed in as <strong>{{ $current->name }}</strong> ({{ $current->email }}).
        </p>
    @endif

    @foreach (['Approved groups' => $groups, 'Common stacks' => $stacks] as $heading => $profiles)
        <section class="mt-6" aria-labelledby="qa-{{ Str::slug($heading) }}">
            <h2 id="qa-{{ Str::slug($heading) }}" class="font-serif text-lg font-semibold">{{ $heading }}</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($profiles as $profile)
                    <li>
                        <form method="post" action="{{ route('qa.login') }}">
                            @csrf
                            <input type="hidden" name="profile" value="{{ $profile['key'] }}">
                            <button
                                type="submit"
                                class="rounded-full bg-sg-teal px-4 py-2 font-semibold text-sg-white hover:bg-sg-teal-dark"
                                title="{{ implode(' + ', $profile['groups']) }}"
                            >{{ $profile['label'] }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if ($current)
        <form method="post" action="{{ route('qa.logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="font-semibold text-sg-teal underline">Sign out</button>
        </form>
    @endif
</x-layouts.app>
