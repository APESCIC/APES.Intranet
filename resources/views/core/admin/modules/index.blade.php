<x-layouts.app title="Modules" subtitle="Switch modules on or off and edit their settings. Core sign-in, audit and health cannot be disabled. Secret values are never shown.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

    <div class="space-y-6">
        @forelse ($rows as $row)
            @php($manifest = $row['manifest'])
            <section
                id="module-{{ $manifest->slug }}"
                class="sg-card p-5"
                aria-labelledby="module-heading-{{ $manifest->slug }}"
                data-module="{{ $manifest->slug }}"
                data-enabled="{{ $row['enabled'] ? 'true' : 'false' }}"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="module-heading-{{ $manifest->slug }}" class="font-serif text-lg font-semibold">
                            {{ $manifest->name }}
                        </h2>
                        <p class="mt-1 text-sm text-sg-muted">
                            <span class="font-medium text-sg-ink">{{ ucfirst($manifest->kind->value) }}</span>
                            · {{ $manifest->description !== '' ? $manifest->description : 'No description.' }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-sm font-semibold {{ $row['enabled'] ? 'bg-sg-ok-mist text-sg-ok' : 'bg-sg-warn-mist text-sg-warn' }}"
                        data-status="{{ $row['enabled'] ? 'enabled' : 'disabled' }}"
                    >
                        {{ $row['enabled'] ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>

                @if ($row['missing_addons'] !== [])
                    <p class="mt-3 rounded-sg-field bg-sg-warn-mist p-3 text-sm text-sg-warn" role="status">
                        Cloudron addon warning: {{ implode(', ', $row['missing_addons']) }} look missing in this environment.
                    </p>
                @endif

                @if ($row['enabled_dependents']->isNotEmpty())
                    <p class="mt-3 text-sm text-sg-muted">
                        Still depended on by:
                        {{ $row['enabled_dependents']->pluck('name')->implode(', ') }}.
                        Disable those first.
                    </p>
                @endif

                <form
                    method="post"
                    action="{{ route('admin.modules.state', $manifest->slug) }}"
                    class="mt-4 space-y-3 border-t border-sg-line pt-4"
                    aria-label="Change state for {{ $manifest->name }}"
                >
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $row['enabled'] ? '0' : '1' }}">
                    <div>
                        <label for="reason-{{ $manifest->slug }}" class="block text-sm font-medium">Reason</label>
                        <input
                            id="reason-{{ $manifest->slug }}"
                            name="reason"
                            type="text"
                            required
                            maxlength="500"
                            class="sg-input mt-1 w-full max-w-xl"
                            @disabled($row['enabled'] && $row['enabled_dependents']->isNotEmpty())
                        >
                    </div>
                    <button
                        type="submit"
                        class="sg-btn {{ $row['enabled'] ? 'sg-btn-secondary' : 'sg-btn-primary' }}"
                        @disabled($row['enabled'] && $row['enabled_dependents']->isNotEmpty())
                    >
                        {{ $row['enabled'] ? 'Disable module' : 'Enable module' }}
                    </button>
                </form>

                @if ($manifest->settings !== [])
                    <form
                        method="post"
                        action="{{ route('admin.modules.settings', $manifest->slug) }}"
                        class="mt-4 space-y-3 border-t border-sg-line pt-4"
                        aria-label="Settings for {{ $manifest->name }}"
                    >
                        @csrf
                        <h3 class="text-sm font-semibold">Settings</h3>
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
                @endif
            </section>
        @empty
            <p class="text-sg-muted">No module manifests were found.</p>
        @endforelse
    </div>
</x-layouts.app>
