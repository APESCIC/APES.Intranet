@props(['group'])
@php
    /** @var \App\Core\Navigation\NavGroup $group */
@endphp
@if (count($group->items) > 1)
    <nav {{ $attributes->class('sg-admin-subnav') }} aria-label="{{ $group->heading }}">
        <ul class="sg-admin-subnav__list">
            @foreach ($group->items as $item)
                @continue($item->isSoon() || $item->url() === null)
                <li>
                    <a
                        href="{{ $item->url() }}"
                        class="sg-admin-subnav__link"
                        data-admin-subnav-key="{{ $item->key }}"
                        @if ($item->isActive()) aria-current="page" @endif
                    >
                        <i data-lucide="{{ $item->icon }}" aria-hidden="true"></i>
                        <span>{{ $item->label }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
