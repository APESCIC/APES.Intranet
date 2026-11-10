@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => null,
    'required' => false,
])

@php
    $fieldId = $id ?? $name;
@endphp

<div
    class="tiptap-field"
    data-tiptap-editor
    data-input-name="{{ $name }}"
>
    @if ($label)
        <label for="{{ $fieldId }}-toolbar" class="mb-2 block text-sm font-semibold text-sg-ink">
            {{ $label }}
            @if ($required)<span class="text-sg-danger" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div
        id="{{ $fieldId }}-toolbar"
        class="tiptap-toolbar"
        role="toolbar"
        aria-label="{{ $label ? $label.' formatting' : 'Formatting' }}"
        aria-controls="{{ $fieldId }}-surface"
    >
        <button type="button" class="tiptap-btn" data-tiptap-command="bold" aria-pressed="false">
            <span aria-hidden="true"><strong>B</strong></span>
            <span class="sr-only">Bold</span>
        </button>
        <button type="button" class="tiptap-btn" data-tiptap-command="italic" aria-pressed="false">
            <span aria-hidden="true"><em>I</em></span>
            <span class="sr-only">Italic</span>
        </button>
        <button type="button" class="tiptap-btn" data-tiptap-command="bulletList" aria-pressed="false">
            <span aria-hidden="true">• List</span>
            <span class="sr-only">Bullet list</span>
        </button>
        <button type="button" class="tiptap-btn" data-tiptap-command="orderedList" aria-pressed="false">
            <span aria-hidden="true">1. List</span>
            <span class="sr-only">Numbered list</span>
        </button>
        <button type="button" class="tiptap-btn" data-tiptap-command="heading" data-level="2" aria-pressed="false">
            <span aria-hidden="true">H2</span>
            <span class="sr-only">Heading level 2</span>
        </button>
        <button type="button" class="tiptap-btn" data-tiptap-command="link" aria-pressed="false">
            <span aria-hidden="true">Link</span>
            <span class="sr-only">Insert link</span>
        </button>
    </div>

    <div
        id="{{ $fieldId }}-surface"
        class="tiptap-surface"
        data-tiptap-surface
        data-label="{{ $label ?? 'Rich text' }}"
    >{!! $value !!}</div>

    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        class="sr-only"
        data-tiptap-input
        @if ($required) required @endif
        aria-hidden="true"
        tabindex="-1"
    >{{ $value }}</textarea>
</div>

@once
    @vite(['resources/js/editor/tiptap.js', 'resources/css/editor.css'])
@endonce
