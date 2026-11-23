@php($manifest = $row['manifest'])
<x-layouts.app :title="$manifest->name" subtitle="Module details, dependencies and settings.">
  <nav class="mb-4 text-sm" aria-label="Back to modules">
    <a href="{{ route('admin.modules.index') }}" class="text-brand underline">← Modules</a>
  </nav>

  @if (session('status'))
    <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
  @endif
  @if (session('error'))
    <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
  @endif

  <div class="space-y-6">
    <section class="sg-card p-5" aria-labelledby="module-detail-heading" data-module="{{ $manifest->slug }}" data-enabled="{{ $row['enabled'] ? 'true' : 'false' }}">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 id="module-detail-heading" class="font-serif text-xl font-semibold">{{ $manifest->name }}</h1>
          <p class="mt-1 text-sm text-sg-muted">
            <span class="font-medium text-sg-ink">{{ ucfirst($manifest->kind->value) }}</span>
            · {{ $manifest->description !== '' ? $manifest->description : 'No description.' }}
          </p>
          <p class="mt-2 text-sm">
            Version <code data-version="{{ $manifest->version }}">{{ $manifest->version }}</code>
          </p>
        </div>
        <span
          class="rounded-full px-3 py-1 text-sm font-semibold {{ $row['enabled'] ? 'bg-sg-ok-mist text-sg-ok' : 'bg-sg-warn-mist text-sg-warn' }}"
          data-status="{{ $row['enabled'] ? 'enabled' : 'disabled' }}"
        >
          {{ $row['enabled'] ? 'Enabled' : 'Disabled' }}
        </span>
      </div>

      @if ($row['missing_addon_labels'] !== [])
        <p class="mt-4 rounded-sg-field bg-sg-warn-mist p-3 text-sm text-sg-warn" role="status">
          Cloudron addon warning: {{ implode(', ', $row['missing_addon_labels']) }} look missing in this environment.
        </p>
      @endif
    </section>

    <section class="sg-card p-5" aria-labelledby="module-dependencies-heading">
      <h2 id="module-dependencies-heading" class="font-serif text-lg font-semibold">Dependencies</h2>
      <dl class="mt-3 grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-sm font-medium text-sg-muted">Needs (depends on)</dt>
          <dd class="mt-1 text-sm">
            @if ($row['dependencies']->isEmpty())
              None
            @else
              <ul class="list-disc pl-5 space-y-1">
                @foreach ($row['dependencies'] as $dependency)
                  <li>
                    <a href="{{ route('admin.modules.show', $dependency->slug) }}" class="text-brand underline">{{ $dependency->name }}</a>
                    <span class="text-sg-muted">({{ $dependency->slug }})</span>
                  </li>
                @endforeach
              </ul>
            @endif
          </dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-sg-muted">Depended on by</dt>
          <dd class="mt-1 text-sm">
            @if ($row['dependents']->isEmpty())
              None
            @else
              <ul class="list-disc pl-5 space-y-1">
                @foreach ($row['dependents'] as $dependent)
                  <li>
                    <a href="{{ route('admin.modules.show', $dependent->slug) }}" class="text-brand underline">{{ $dependent->name }}</a>
                  </li>
                @endforeach
              </ul>
            @endif
          </dd>
        </div>
      </dl>
    </section>

    <section class="sg-card p-5" aria-labelledby="module-changelog-heading">
      <h2 id="module-changelog-heading" class="font-serif text-lg font-semibold">Change log</h2>
      <p class="mt-2 text-sm text-sg-muted">
        Platform releases are documented in the intranet
        <a href="{{ route('change-log.index') }}" class="text-brand underline">change log</a>.
        Core version <code>{{ $coreVersion }}</code> tracks the app release; this module is at <code>{{ $manifest->version }}</code>.
      </p>
      @if ($deployedRelease !== null && is_array($deployedRelease->module_versions))
        @php($notedVersion = $deployedRelease->module_versions[$manifest->slug] ?? null)
        @if ($notedVersion !== null)
          <p class="mt-3 text-sm">
            Recorded in
            <a href="{{ route('change-log.show', $deployedRelease->version) }}" class="text-brand underline">{{ $deployedRelease->version }}</a>:
            <code>{{ $notedVersion }}</code>
            @if ($notedVersion !== $manifest->version)
              <span class="text-sg-warn">(manifest is now {{ $manifest->version }})</span>
            @endif
          </p>
        @endif
      @endif
    </section>

    @if ($manifest->settings !== [])
      <section class="sg-card p-5" aria-labelledby="module-settings-heading">
        <h2 id="module-settings-heading" class="font-serif text-lg font-semibold">Settings</h2>
        <div class="mt-4">
          @include('core.admin.modules._settings-form', ['row' => $row, 'returnToShow' => true])
        </div>
      </section>
    @endif

    @php($blockDisable = $row['enabled'] && $row['enabled_dependents']->isNotEmpty())
    <section class="sg-card p-5" aria-labelledby="module-state-heading">
      <h2 id="module-state-heading" class="font-serif text-lg font-semibold">Enable or disable</h2>
      <p class="mt-2 text-sm text-sg-muted">
        A reason is required and is recorded in the audit log with your account and the time.
      </p>
      @if ($blockDisable)
        <p class="mt-3 text-sm text-sg-warn" role="status">
          Cannot disable while {{ $row['enabled_dependents']->pluck('name')->implode(', ') }} still need this module enabled.
        </p>
      @endif
      <form
        method="post"
        action="{{ route('admin.modules.state', $manifest->slug) }}"
        class="mt-4 space-y-3"
        aria-label="Change state for {{ $manifest->name }}"
      >
        @csrf
        <input type="hidden" name="return" value="show">
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
            @disabled($blockDisable)
          >
        </div>
        <button
          type="submit"
          class="sg-btn {{ $row['enabled'] ? 'sg-btn-secondary' : 'sg-btn-primary' }}"
          @disabled($blockDisable)
        >
          {{ $row['enabled'] ? 'Disable module' : 'Enable module' }}
        </button>
      </form>
    </section>
  </div>
</x-layouts.app>