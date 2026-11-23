@php
    $selectedName = $selectedRole?->name;
@endphp
<x-layouts.app title="Roles" subtitle="Roles come from the {{ $groupCount }} approved Cloudron directory groups. Groups are changed in Cloudron, so this page is read-only.">
    <div class="sg-roles-layout">
        <div class="sg-card overflow-x-auto">
            <table class="sg-table w-full text-left">
                <caption class="sr-only">Cloudron groups, the role each gives, how many people have it, and its permission count</caption>
                <thead>
                    <tr>
                        <th scope="col">Cloudron group</th>
                        <th scope="col">Role</th>
                        <th scope="col">People</th>
                        <th scope="col">Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        @php
                            $label = \App\Core\Access\RoleName::tryFrom($role->name)?->label() ?? $role->name;
                            $isSelected = $selectedName === $role->name;
                        @endphp
                        <tr class="align-top {{ $isSelected ? 'sg-roles-row--selected' : '' }}" data-role="{{ $role->name }}">
                            <td>
                                @isset($groupFor[$role->name])
                                    <code class="text-sm">{{ $groupFor[$role->name] }}</code>
                                @else
                                    <span class="text-sg-muted">No group</span>
                                @endisset
                            </td>
                            <th scope="row">
                                <a
                                    href="{{ route('admin.roles.index', ['role' => $role->name]) }}"
                                    class="sg-roles-select"
                                    @if ($isSelected) aria-current="true" @endif
                                >
                                    {{ $label }}
                                </a>
                            </th>
                            <td class="p-4">{{ $role->users_count }}</td>
                            <td>{{ $role->permissions->count() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($selectedRole)
            @php
                $selectedLabel = \App\Core\Access\RoleName::tryFrom($selectedRole->name)?->label() ?? $selectedRole->name;
                $permissionGroups = $selectedRole->permissions
                    ->sortBy('name')
                    ->groupBy(function ($permission) {
                        $parts = explode('.', $permission->name, 2);

                        return $parts[0] !== '' ? $parts[0] : 'other';
                    });
            @endphp
            <aside class="sg-card p-5" data-role-panel="{{ $selectedRole->name }}" aria-labelledby="role-panel-heading">
                <h2 id="role-panel-heading" class="font-serif text-lg font-semibold">{{ $selectedLabel }}</h2>
                <p class="mt-1 text-sm text-sg-muted">
                    @isset($groupFor[$selectedRole->name])
                        Cloudron group <code>{{ $groupFor[$selectedRole->name] }}</code> ·
                    @endisset
                    {{ $selectedRole->users_count }} {{ $selectedRole->users_count === 1 ? 'person' : 'people' }} ·
                    {{ $selectedRole->permissions->count() }} permissions
                </p>

                @forelse ($permissionGroups as $module => $permissions)
                    <section class="mt-4" aria-labelledby="perm-group-{{ $module }}">
                        <h3 id="perm-group-{{ $module }}" class="text-sm font-bold uppercase tracking-wide text-sg-muted">{{ $module }}</h3>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($permissions as $permission)
                                <li class="sg-roles-permission">
                                    <span>{{ $descriptions[$permission->name] ?? $permission->name }}</span>
                                    <code class="sg-roles-permission__code">{{ $permission->name }}</code>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @empty
                    <p class="mt-4 text-sm text-sg-muted">This role has no permissions yet.</p>
                @endforelse
            </aside>
        @endif
    </div>
</x-layouts.app>
