<x-layouts.app title="Microsoft 365 links" subtitle="Admin registry for Microsoft app links.">
    <ul class="grid gap-4">
        @foreach ($links as $link)
            <li class="sg-card p-5">
                <form method="post" action="{{ route('admin.m365-links.update', $link) }}" class="grid gap-2 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <input class="sg-input" name="key" value="{{ $link->key }}" required>
                    <input class="sg-input" name="name" value="{{ $link->name }}" required>
                    <input class="sg-input md:col-span-2" name="url" value="{{ $link->url }}" required>
                    <input class="sg-input" name="icon" value="{{ $link->icon }}" required>
                    <select class="sg-input" name="audience">
                        <option value="all" @selected($link->audience === 'all')>All members</option>
                        <option value="staff" @selected($link->audience === 'staff')>Staff only</option>
                    </select>
                    <input class="sg-input" type="number" name="sort_order" value="{{ $link->sort_order }}">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked($link->active)> Active</label>
                    <button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Save</button>
                </form>
            </li>
        @endforeach
    </ul>
    <form method="post" action="{{ route('admin.m365-links.store') }}" class="sg-card mt-8 grid max-w-3xl gap-3 p-5">
        @csrf
        <h2 class="font-serif text-xl font-semibold">Add link</h2>
        <input class="sg-input" name="key" placeholder="key" required>
        <input class="sg-input" name="name" placeholder="Name" required>
        <input class="sg-input" name="url" placeholder="https://…" required>
        <input class="sg-input" name="icon" value="app-window" required>
        <select class="sg-input" name="audience">
            <option value="all">All members</option>
            <option value="staff">Staff only</option>
        </select>
        <input class="sg-input" type="number" name="sort_order" value="0">
        <button class="sg-btn sg-btn-primary sg-btn-sm" type="submit">Add</button>
    </form>
</x-layouts.app>
