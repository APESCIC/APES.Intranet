@php($manifest = $row['manifest'])
<form
    method="post"
    action="{{ route('admin.modules.settings', $manifest->slug) }}"
    class="space-y-3"
    aria-label="Settings for {{ $manifest->name }}"
>
    @csrf
    @if (! empty($returnToShow))
        <input type="hidden" name="return" value="show">
    @endif
    @foreach ($manifest->settings as $key => $definition)
        @php($value = $row['settings'][$key] ?? $definition->default)
        <div>
            <label for="setting-{{ $manifest->slug }}-{{ $key }}" class="block text-sm font-medium">
                {{ $definition->label }}
            </label>
            @if ($definition->type === \App\Core\Modules\SettingType::Bool)
                <label class="mt-1 inline-flex items-center gap-2 text-sm">
                    <input
                        id="setting-{{ $manifest->slug }}-{{ $key }}"
                        type="checkbox"
                        name="settings[{{ $key }}]"
                        value="1"
                        @checked((bool) $value)
                    >
                    On
                </label>
            @elseif ($definition->type === \App\Core\Modules\SettingType::Enum)
                <select
                    id="setting-{{ $manifest->slug }}-{{ $key }}"
                    name="settings[{{ $key }}]"
                    class="sg-input mt-1 w-full max-w-xl"
                >
                    @foreach ($definition->options as $option)
                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            @elseif ($definition->type === \App\Core\Modules\SettingType::SecretRef)
                @php($isSet = is_array($value) && filled($value['env'] ?? null) && filled($value['vault'] ?? null))
                <p class="mt-1 text-sm text-sg-muted" data-secret-ref="{{ $isSet ? 'set' : 'not-set' }}">
                    {{ $isSet ? 'Set' : 'Not set' }} (env key and Vault item name only — never a secret value).
                </p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <div>
                        <label for="setting-{{ $manifest->slug }}-{{ $key }}-env" class="block text-xs text-sg-muted">Env key</label>
                        <input
                            id="setting-{{ $manifest->slug }}-{{ $key }}-env"
                            name="settings[{{ $key }}][env]"
                            type="text"
                            value="{{ is_array($value) ? ($value['env'] ?? '') : '' }}"
                            class="sg-input mt-1 w-full"
                            autocomplete="off"
                        >
                    </div>
                    <div>
                        <label for="setting-{{ $manifest->slug }}-{{ $key }}-vault" class="block text-xs text-sg-muted">Vault item</label>
                        <input
                            id="setting-{{ $manifest->slug }}-{{ $key }}-vault"
                            name="settings[{{ $key }}][vault]"
                            type="text"
                            value="{{ is_array($value) ? ($value['vault'] ?? '') : '' }}"
                            class="sg-input mt-1 w-full"
                            autocomplete="off"
                        >
                    </div>
                </div>
            @else
                <input
                    id="setting-{{ $manifest->slug }}-{{ $key }}"
                    name="settings[{{ $key }}]"
                    type="{{ $definition->type === \App\Core\Modules\SettingType::Int ? 'number' : 'text' }}"
                    value="{{ is_scalar($value) ? $value : '' }}"
                    class="sg-input mt-1 w-full max-w-xl"
                >
            @endif
        </div>
    @endforeach
    <button type="submit" class="sg-btn sg-btn-secondary">Save settings</button>
</form>