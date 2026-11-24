@props(['footerVersion' => null])

@php
    use App\Core\Changelog\FooterVersion as FooterVersionService;
    use App\Core\Legal\FooterLinkGroup;
    use App\Core\Legal\FooterLinks;
    use App\Core\Legal\FooterLinkPresenter;
    use App\Models\User;

    $shellUser = auth()->user();
    $shellUser = $shellUser instanceof User ? $shellUser : null;

    if ($footerVersion === null) {
        $footerVersion = app(FooterVersionService::class)->for($shellUser);
    }

    $footerLinks = app(FooterLinks::class);
    $presenter = app(FooterLinkPresenter::class);
    $linksByGroup = $footerLinks->forVisitor($shellUser)->groupBy('group');
@endphp

<footer class="site-footer">
    <div class="site-footer__bar">
        <p class="site-footer__copyright">&copy; {{ now()->year }} Association of Protecting Exotic Species CIC</p>
        @if ($footerVersion)
            @if ($footerVersion['url'])
                <a class="site-footer__version" href="{{ $footerVersion['url'] }}" data-app-version><span class="sr-only">{{ $footerVersion['hiddenBefore'] }}</span>{{ $footerVersion['label'] }}@if ($footerVersion['hiddenAfter'])<span class="sr-only">{{ $footerVersion['hiddenAfter'] }}</span>@endif</a>
            @else
                <span class="site-footer__version" data-app-version><span class="sr-only">{{ $footerVersion['hiddenBefore'] }}</span>{{ $footerVersion['label'] }}@if ($footerVersion['hiddenAfter'])<span class="sr-only">{{ $footerVersion['hiddenAfter'] }}</span>@endif</span>
            @endif
        @endif
    </div>

    <div class="site-footer__groups">
        @foreach (FooterLinkGroup::all() as $group)
            @php($groupLinks = $linksByGroup->get($group, collect()))
            @if ($groupLinks->isNotEmpty())
                <nav class="site-footer__group" aria-labelledby="site-footer-heading-{{ $group }}">
                    <h2 id="site-footer-heading-{{ $group }}" class="site-footer__group-heading">
                        {{ FooterLinkGroup::label($group) }}
                    </h2>
                    <ul class="site-footer__group-links">
                        @foreach ($groupLinks as $link)
                            <li class="site-footer__group-link">
                                {!! $presenter->anchor($link, $presenter->ariaCurrent($link)) !!}
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        @endforeach
    </div>
</footer>