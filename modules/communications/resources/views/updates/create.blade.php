<x-layouts.app title="Write an update">
    <form method="post" action="{{ route('communications.updates.store') }}" class="grid max-w-3xl gap-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-semibold" for="title">Title</label>
            <input id="title" name="title" class="sg-input w-full" required value="{{ old('title') }}">
            @error('title')<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
        </div>
        <x-editor.tiptap name="body" label="Body" :value="old('body', '')" required />
        @error('body')<p class="text-sm text-sg-danger">{{ $message }}</p>@enderror
        <fieldset>
            <legend class="mb-2 text-sm font-semibold">Audience (leave blank for everyone)</legend>
            @foreach (['staff','volunteer','student','supervisor','management','hr','legal','admin'] as $role)
                <label class="mr-3 inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="audiences[]" value="{{ $role }}" @checked(in_array($role, old('audiences', []), true))>
                    {{ $role }}
                </label>
            @endforeach
        </fieldset>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="pinned" value="1"> Pin to top</label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="comments_enabled" value="1" checked> Allow comments</label>
        <button class="sg-btn sg-btn-primary" type="submit">Publish</button>
    </form>
</x-layouts.app>
