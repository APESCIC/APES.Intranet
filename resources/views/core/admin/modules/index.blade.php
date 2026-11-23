<x-layouts.app title="Modules" subtitle="Switch modules on or off and open each module for settings. Core sign-in, audit and health cannot be disabled. Secret values are never shown.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

    <div class="overflow-x-auto rounded-sg-card bg-sg-white shadow-sg-card" data-workspace-modules-index>
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Modules and integrations. Core is always on. Other rows can be opened for details or switched with a reason.</caption>
            <thead class="border-b border-sg-info-mist text-sg-muted">
                <tr>
                    <th scope="col" class="p-4">Module</th>
                    <th scope="col" class="p-4">Kind</th>
                    <th scope="col" class="p-4">Version</th>
                    <th scope="col" class="p-4">Needs</th>
                    <th scope="col" class="p-4">Addon status</th>
                    <th scope="col" class="p-4">Status</th>
                    <th scope="col" class="p-4"><span class="sr-only">Switch</span></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    id="module-core"
                    class="border-b border-sg-info-mist align-top"
                    data-module="core"
                    data-enabled="true"
                >
                    <th scope="row" class="p-4 font-semibold text-sg-ink">
                        <span id="module-heading-core">Core</span>
                    </th>
                    <td class="p-4">Core</td>
                    <td class="p-4">
                        <code data-version="{{ $coreVersion }}">{{ $coreVersion }}</code>
                    </td>
                    <td class="p-4 text-sg-muted">—</td>
                    <td class="p-4 text-sg-muted">—</td>
                    <td class="p-4">
                        <span class="rounded-full bg-sg-ok-mist px-3 py-1 text-xs font-semibold text-sg-ok" data-status="enabled">
                            Always on
                        </span>
                    </td>
                    <td class="p-4 text-sg-muted">
                        <span class="text-xs" aria-label="Core cannot be switched off">Locked</span>
                    </td>
                </tr>
                @forelse ($rows as $row)
                    @php($manifest = $row['manifest'])
                    @php($blockDisable = $row['enabled'] && $row['enabled_dependents']->isNotEmpty())
                    <tr
                        id="module-{{ $manifest->slug }}"
                        class="border-b border-sg-info-mist last:border-0 align-top"
                        data-module="{{ $manifest->slug }}"
                        data-enabled="{{ $row['enabled'] ? 'true' : 'false' }}"
                    >
                        <th scope="row" class="p-4 font-medium">
                            <a
                                href="{{ route('admin.modules.show', $manifest->slug) }}"
                                class="text-brand underline"
                                id="module-heading-{{ $manifest->slug }}"
                            >
                                {{ $manifest->name }}
                            </a>
                            @if ($manifest->description !== '')
                                <p class="mt-1 text-xs font-normal text-sg-muted">{{ $manifest->description }}</p>
                            @endif
                        </th>
                        <td class="p-4">{{ ucfirst($manifest->kind->value) }}</td>
                        <td class="p-4"><code data-version="{{ $manifest->version }}">{{ $manifest->version }}</code></td>
                        <td class="p-4">
                            @if ($row['dependencies']->isEmpty())
                                <span class="text-sg-muted">None</span>
                            @else
                                <ul class="list-none space-y-1">
                                    @foreach ($row['dependencies'] as $dependency)
                                        <li>
                                            <a href="{{ route('admin.modules.show', $dependency->slug) }}" class="text-brand underline">
                                                {{ $dependency->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="p-4">
                            @if ($row['missing_addon_labels'] === [])
                                <span class="text-sg-ok">OK</span>
                            @else
                                <span class="text-sg-warn" role="status">
                                    Missing: {{ implode(', ', $row['missing_addon_labels']) }}
                                </span>
                            @endif
                        </td>
                        <td class="p-4">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold {{ $row['enabled'] ? 'bg-sg-ok-mist text-sg-ok' : 'bg-sg-warn-mist text-sg-warn' }}"
                                data-status="{{ $row['enabled'] ? 'enabled' : 'disabled' }}"
                            >
                                {{ $row['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                            @if ($blockDisable)
                                <p class="mt-2 text-xs text-sg-muted">
                                    Disable {{ $row['enabled_dependents']->pluck('name')->implode(', ') }} first.
                                </p>
                            @endif
                        </td>
                        <td class="p-4">
                            <button
                                type="button"
                                class="sg-btn {{ $row['enabled'] ? 'sg-btn-secondary' : 'sg-btn-primary' }} sg-btn-sm"
                                data-module-state-open
                                data-module-slug="{{ $manifest->slug }}"
                                data-module-name="{{ $manifest->name }}"
                                data-module-enabled="{{ $row['enabled'] ? 'true' : 'false' }}"
                                data-module-next-enabled="{{ $row['enabled'] ? '0' : '1' }}"
                                aria-haspopup="dialog"
                                aria-controls="module-state-dialog"
                                @disabled($blockDisable)
                            >
                                {{ $row['enabled'] ? 'Disable' : 'Enable' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-sg-muted">No module manifests were found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <dialog
        id="module-state-dialog"
        class="sg-card max-w-lg w-[calc(100%-2rem)] p-0 backdrop:bg-sg-ink/40"
        aria-labelledby="module-state-dialog-title"
        aria-describedby="module-state-dialog-desc"
        data-module-state-dialog
    >
        <form
            method="post"
            action="#"
            class="flex flex-col gap-4 p-5"
            data-module-state-form
        >
            @csrf
            <input type="hidden" name="enabled" value="" data-module-state-enabled>
            <div>
                <h2 id="module-state-dialog-title" class="font-serif text-lg font-semibold" data-module-state-title></h2>
                <p id="module-state-dialog-desc" class="mt-2 text-sm text-sg-muted" data-module-state-desc></p>
            </div>
            <div>
                <label for="module-state-reason" class="block text-sm font-medium">Reason</label>
                <input
                    id="module-state-reason"
                    name="reason"
                    type="text"
                    required
                    maxlength="500"
                    class="sg-input mt-1 w-full"
                    data-module-state-reason
                >
            </div>
            <div class="flex flex-wrap gap-2 justify-end">
                <button type="button" class="sg-btn sg-btn-secondary" data-module-state-cancel>Cancel</button>
                <button type="submit" class="sg-btn sg-btn-primary" data-module-state-submit>Confirm</button>
            </div>
        </form>
    </dialog>
</x-layouts.app>