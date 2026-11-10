<x-layouts.app title="Operations sites" subtitle="Sites that rotas, clock-ins and checklists belong to.">
    @include('operations::partials.flash')

    <datalist id="team-options">
        @foreach ($teams as $team)
            <option value="{{ $team }}"></option>
        @endforeach
    </datalist>

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="add-site">
        <h2 id="add-site" class="mb-3 font-serif text-lg font-semibold">Add a site</h2>
        <form method="post" action="{{ route('admin.operations.sites.store') }}" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="sg-label" for="name">Name</label>
                <input id="name" name="name" class="sg-input w-full" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="sg-label" for="team">Team</label>
                <input id="team" name="team" class="sg-input w-full" list="team-options" value="{{ old('team') }}">
            </div>
            <div>
                <label class="sg-label" for="latitude">Geofence latitude (optional)</label>
                <input id="latitude" name="latitude" inputmode="decimal" class="sg-input w-full" value="{{ old('latitude') }}">
            </div>
            <div>
                <label class="sg-label" for="longitude">Geofence longitude (optional)</label>
                <input id="longitude" name="longitude" inputmode="decimal" class="sg-input w-full" value="{{ old('longitude') }}">
            </div>
            <div>
                <label class="sg-label" for="radius_metres">Geofence radius in metres (optional)</label>
                <input id="radius_metres" name="radius_metres" type="number" min="25" max="5000" class="sg-input w-full" value="{{ old('radius_metres') }}">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add site</button>
            </div>
        </form>
        <p class="mt-3 text-sm text-sg-muted">The geofence area is used only once the clock-in DPIA is approved and the geofence is switched on.</p>
    </section>

    <section class="sg-card max-w-4xl p-6" aria-labelledby="sites-heading">
        <h2 id="sites-heading" class="mb-3 font-serif text-lg font-semibold">Sites</h2>
        @forelse ($sites as $site)
            <form method="post" action="{{ route('admin.operations.sites.update', $site) }}" class="mb-4 grid gap-3 rounded-sg-field border border-sg-info-mist p-3 sm:grid-cols-3" data-site="{{ $site->id }}">
                @csrf
                @method('put')
                <div>
                    <label class="sg-label" for="name-{{ $site->id }}">Name</label>
                    <input id="name-{{ $site->id }}" name="name" class="sg-input w-full" value="{{ $site->name }}" required>
                </div>
                <div>
                    <label class="sg-label" for="team-{{ $site->id }}">Team</label>
                    <input id="team-{{ $site->id }}" name="team" class="sg-input w-full" list="team-options" value="{{ $site->team }}">
                </div>
                <div>
                    <label class="sg-label" for="radius-{{ $site->id }}">Radius (m)</label>
                    <input id="radius-{{ $site->id }}" name="radius_metres" type="number" min="25" max="5000" class="sg-input w-full" value="{{ $site->radius_metres }}">
                </div>
                <div>
                    <label class="sg-label" for="lat-{{ $site->id }}">Latitude</label>
                    <input id="lat-{{ $site->id }}" name="latitude" inputmode="decimal" class="sg-input w-full" value="{{ $site->latitude }}">
                </div>
                <div>
                    <label class="sg-label" for="lng-{{ $site->id }}">Longitude</label>
                    <input id="lng-{{ $site->id }}" name="longitude" inputmode="decimal" class="sg-input w-full" value="{{ $site->longitude }}">
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="active" value="1" @checked($site->active)> Active
                    </label>
                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save</button>
                </div>
            </form>
        @empty
            <p class="text-sg-muted">No sites yet.</p>
        @endforelse
    </section>
</x-layouts.app>
