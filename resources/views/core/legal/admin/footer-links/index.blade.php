<x-layouts.app title="Footer links" subtitle="Choose and order the links that appear in the site footer. Locked links stay visible to everyone.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">{{ session('error') }}</p>
    @endif

    @foreach ($groups as $group)
        @php
            $links = $grouped->get($group, collect());
        @endphp
        <section class="mb-8" aria-labelledby="footer-group-{{ $group }}">
            <h2 id="footer-group-{{ $group }}" class="font-serif text-lg font-semibold text-sg-ink">
                {{ \App\Core\Legal\FooterLinkGroup::label($group) }}
            </h2>

            @if ($links->isEmpty())
                <p class="mt-2 text-sm text-sg-muted">No links in this group yet.</p>
            @else
                <x-admin.table-card class="mt-3">
                    <table class="sg-table w-full text-left text-sm">
                        <caption class="sr-only">Footer links in {{ \App\Core\Legal\FooterLinkGroup::label($group) }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Label</th>
                                <th scope="col">Target</th>
                                <th scope="col">Visibility</th>
                                <th scope="col">Module</th>
                                <th scope="col"><span class="sr-only">Order</span></th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($links as $index => $link)
                                <tr class="align-top" data-footer-link-id="{{ $link->id }}">
                                    <th scope="row" class="font-medium text-sg-ink">
                                        {{ $link->label }}
                                        @if ($link->locked)
                                            <span class="ml-1 text-xs font-normal text-sg-muted">(locked)</span>
                                        @endif
                                    </th>
                                    <td class="text-sg-muted">
                                        <code class="text-xs">{{ $link->target_type }}: {{ $link->target_value }}</code>
                                    </td>
                                    <td>{{ $link->visibility === 'everyone' ? 'Everyone' : 'Signed in' }}</td>
                                    <td class="text-sg-muted">{{ $link->module_slug ?? '—' }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            <form method="post" action="{{ route('admin.legal.footer-links.reorder', $link) }}">
                                                @csrf
                                                <input type="hidden" name="direction" value="up">
                                                <button
                                                    type="submit"
                                                    class="sg-btn sg-btn-ghost sg-btn-sm"
                                                    @disabled($index === 0)
                                                    data-move-up="{{ $link->id }}"
                                                >Move up</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.legal.footer-links.reorder', $link) }}">
                                                @csrf
                                                <input type="hidden" name="direction" value="down">
                                                <button
                                                    type="submit"
                                                    class="sg-btn sg-btn-ghost sg-btn-sm"
                                                    @disabled($index === $links->count() - 1)
                                                    data-move-down="{{ $link->id }}"
                                                >Move down</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        @unless ($link->locked)
                                            <form
                                                method="post"
                                                action="{{ route('admin.legal.footer-links.destroy', $link) }}"
                                                class="inline"
                                                onsubmit="return confirm('Remove this footer link?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm text-sg-error">Remove</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-sg-muted" aria-label="Locked links cannot be removed">Locked</span>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-admin.table-card>
            @endif
        </section>
    @endforeach

    <section class="mt-10 border-t border-sg-border pt-8" aria-labelledby="footer-link-add">
        <h2 id="footer-link-add" class="font-serif text-lg font-semibold text-sg-ink">Add a link</h2>
        <form method="post" action="{{ route('admin.legal.footer-links.store') }}" class="mt-4 grid max-w-2xl gap-4">
            @csrf
            <div>
                <label for="footer-group" class="sg-label">Group</label>
                <select id="footer-group" name="group" class="sg-input w-full" required>
                    @foreach ($groups as $group)
                        <option value="{{ $group }}" @selected(old('group') === $group)>{{ \App\Core\Legal\FooterLinkGroup::label($group) }}</option>
                    @endforeach
                </select>
                @error('group')
                    <p class="mt-1 text-sm text-sg-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="footer-label" class="sg-label">Label</label>
                <input id="footer-label" type="text" name="label" class="sg-input w-full" value="{{ old('label') }}" required maxlength="120">
                @error('label')
                    <p class="mt-1 text-sm text-sg-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="footer-target-type" class="sg-label">Target type</label>
                <select id="footer-target-type" name="target_type" class="sg-input w-full" required>
                    @foreach (\App\Core\Legal\FooterLinkTargetType::all() as $type)
                        <option value="{{ $type }}" @selected(old('target_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="footer-target-value" class="sg-label">Target value</label>
                <input id="footer-target-value" type="text" name="target_value" class="sg-input w-full" value="{{ old('target_value') }}" required maxlength="500">
            </div>
            <div>
                <label for="footer-visibility" class="sg-label">Visibility</label>
                <select id="footer-visibility" name="visibility" class="sg-input w-full" required>
                    <option value="everyone" @selected(old('visibility', 'everyone') === 'everyone')>Everyone</option>
                    <option value="signed_in" @selected(old('visibility') === 'signed_in')>Signed in</option>
                </select>
            </div>
            <div>
                <label for="footer-module" class="sg-label">Module slug (optional)</label>
                <input id="footer-module" type="text" name="module_slug" class="sg-input w-full" value="{{ old('module_slug') }}" maxlength="80">
                <p class="mt-1 text-xs text-sg-muted">When that module is off, the link is hidden from the footer.</p>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="sg-btn sg-btn-primary">Add link</button>
            </div>
        </form>
    </section>
</x-layouts.app>