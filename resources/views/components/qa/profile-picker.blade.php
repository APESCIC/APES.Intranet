@props(['profiles', 'current' => null, 'returnTo' => null, 'idPrefix' => 'qa'])
<div {{ $attributes->class('sg-qa-picker') }}>
    @foreach (['Approved groups' => $profiles['groups'], 'Common stacks' => $profiles['stacks']] as $heading => $list)
        <section class="sg-qa-picker__group" aria-labelledby="{{ $idPrefix }}-{{ Str::slug($heading) }}">
            <h3 id="{{ $idPrefix }}-{{ Str::slug($heading) }}" class="sg-qa-picker__heading">{{ $heading }}</h3>
            <ul class="sg-qa-picker__list">
                @foreach ($list as $profile)
                    <li>
                        <form method="post" action="{{ route('qa.login') }}">
                            @csrf
                            <input type="hidden" name="profile" value="{{ $profile['key'] }}">
                            @if ($returnTo)
                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            @endif
                            <button
                                type="submit"
                                class="sg-qa-btn"
                                title="{{ implode(' + ', $profile['groups']) }}"
                                @if ($current === $profile['key']) aria-current="true" @endif
                            >
                                {{ $profile['label'] }}
                                @if ($current === $profile['key'])
                                    <span class="sg-qa-btn__current">(current)</span>
                                @endif
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
