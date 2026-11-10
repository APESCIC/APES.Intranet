<x-layouts.app :title="$form->title" :subtitle="$form->description">
    @include('operations::partials.flash')

    <form method="post" action="{{ route('operations.forms.submit', $form) }}" enctype="multipart/form-data" class="sg-card grid max-w-3xl gap-5 p-6" novalidate>
        @csrf
        @foreach ($form->fields as $field)
            @php($name = $field->inputName())
            <div>
                @switch($field->type)
                    @case('checkbox')
                        <label class="flex items-center gap-2 font-semibold">
                            <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" @checked(old($name)) @if ($field->required) required aria-required="true" @endif>
                            {{ $field->label }}@if ($field->required)<span aria-hidden="true"> *</span>@endif
                        </label>
                        @break
                    @case('signature')
                        <fieldset class="grid gap-2" data-signature-field>
                            <legend class="sg-label">{{ $field->label }}@if ($field->required)<span aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif</legend>
                            <p class="text-sm text-sg-muted" id="{{ $name }}-help">Draw your signature with a finger, stylus or mouse, or type your full name below.</p>
                            <canvas class="w-full max-w-md touch-none rounded-sg-field border border-sg-info-mist bg-white" width="600" height="200" data-signature-pad aria-hidden="true"></canvas>
                            <input type="hidden" name="{{ $name }}" data-signature-input>
                            <div><button type="button" class="sg-btn sg-btn-ghost sg-btn-sm" data-signature-clear>Clear signature</button></div>
                            <label class="sg-label" for="{{ $name }}_typed">Or type your full name</label>
                            <input id="{{ $name }}_typed" name="{{ $name }}_typed" maxlength="200" class="sg-input w-full" value="{{ old($name.'_typed') }}" aria-describedby="{{ $name }}-help">
                        </fieldset>
                        @break
                    @default
                        <label class="sg-label" for="{{ $name }}">{{ $field->label }}@if ($field->required)<span aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif</label>
                        @switch($field->type)
                            @case('textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="sg-input w-full" @if ($field->required) required @endif>{{ old($name) }}</textarea>
                                @break
                            @case('select')
                                <select id="{{ $name }}" name="{{ $name }}" class="sg-input w-full" @if ($field->required) required @endif>
                                    <option value="">Choose one</option>
                                    @foreach ($field->options ?? [] as $option)
                                        <option value="{{ $option }}" @selected(old($name) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @break
                            @case('photo')
                                <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/*" capture="environment" class="sg-input w-full" @if ($field->required) required @endif>
                                @break
                            @case('number')
                                <input id="{{ $name }}" type="number" step="any" name="{{ $name }}" class="sg-input w-full" value="{{ old($name) }}" @if ($field->required) required @endif>
                                @break
                            @case('date')
                                <input id="{{ $name }}" type="date" name="{{ $name }}" class="sg-input w-full" value="{{ old($name) }}" @if ($field->required) required @endif>
                                @break
                            @default
                                <input id="{{ $name }}" name="{{ $name }}" maxlength="1000" class="sg-input w-full" value="{{ old($name) }}" @if ($field->required) required @endif>
                        @endswitch
                @endswitch
                @error($name)<p class="mt-1 text-sm text-sg-error">{{ $message }}</p>@enderror
                @error($name.'_typed')<p class="mt-1 text-sm text-sg-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <div><button type="submit" class="sg-btn sg-btn-primary">Send</button></div>
    </form>
</x-layouts.app>
