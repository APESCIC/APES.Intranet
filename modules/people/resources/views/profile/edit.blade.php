<x-layouts.app title="Edit profile" subtitle="Keep your details up to date.">
    <form method="post" action="{{ route('people.profile.update') }}" enctype="multipart/form-data" class="sg-card max-w-2xl space-y-4 p-6">
        @csrf
        @method('PUT')

        @foreach ([
            'job_title' => 'Job title',
            'team' => 'Team',
            'phone' => 'Phone',
            'mobile' => 'Mobile',
            'location' => 'Location',
        ] as $field => $label)
            <div>
                <label class="mb-1 block text-sm font-semibold" for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $person->{$field}) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
                @error($field)<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div>
            <label class="mb-1 block text-sm font-semibold" for="skills">Skills</label>
            <input id="skills" name="skills" value="{{ old('skills', implode(', ', $person->skills ?? [])) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2" aria-describedby="skills-help">
            <p id="skills-help" class="mt-1 text-sm text-sg-muted">Separate skills with commas.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold" for="bio">About</label>
            <textarea id="bio" name="bio" rows="4" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">{{ old('bio', $person->bio) }}</textarea>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold" for="photo">Photo</label>
            <input id="photo" name="photo" type="file" accept="image/*" class="w-full" aria-describedby="photo-help">
            <p id="photo-help" class="mt-1 text-sm text-sg-muted">Images only, up to 5 MB.</p>
            @error('photo')<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold" for="emergency_contact_name">Emergency contact name</label>
            <input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $person->emergency_contact_name) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="emergency_contact_phone">Emergency contact phone</label>
            <input id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $person->emergency_contact_phone) }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
        </div>

        <button type="submit" class="sg-btn sg-btn-primary">Save profile</button>
    </form>
</x-layouts.app>
