@props([
    'items' => [],
])
@php
    /** @var list<array{label: string, url?: string|null, current?: bool}> $items */
@endphp
@if ($items !== [])
    <nav {{ $attributes->class('sg-breadcrumbs') }} aria-label="Breadcrumb">
        <ol class="sg-breadcrumbs__list">
            @foreach ($items as $crumb)
                <li class="sg-breadcrumbs__item">
                    @if (! empty($crumb['current']))
                        <span class="sg-breadcrumbs__current" aria-current="page">{{ $crumb['label'] }}</span>
                    @elseif (! empty($crumb['url']))
                        <a class="sg-breadcrumbs__link" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span class="sg-breadcrumbs__text">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
