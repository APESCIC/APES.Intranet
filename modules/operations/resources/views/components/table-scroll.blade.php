@props(['label'])
{{-- Focusable so keyboard users can scroll a wide table on a small screen. --}}
<div class="overflow-x-auto" tabindex="0" role="region" aria-label="{{ $label }}">
    {{ $slot }}
</div>
