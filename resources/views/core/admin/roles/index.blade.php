<x-layouts.app title="Roles" subtitle="Roles come from the {{ $groupCount }} approved Cloudron directory groups. Groups are changed in Cloudron, so this page is read-only.">
    <div class="overflow-x-auto rounded-sg-card bg-sg-white shadow-sg-card">
        <table class="w-full text-left">
            <caption class="sr-only">Cloudron groups, the role each gives, how many people have it, and its permissions</caption>
            <thead class="border-b border-sg-info-mist text-sm text-sg-muted">
                <tr>
                    <th scope="col" class="p-4">Cloudron group</th>
                    <th scope="col" class="p-4">Role</th>
                    <th scope="col" class="p-4">People</th>
                    <th scope="col" class="p-4">Permissions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr class="border-b border-sg-info-mist last:border-0 align-top" data-role="{{ $role->name }}">
                        <td class="p-4">
                            @isset($groupFor[$role->name])
                                <code class="text-sm">{{ $groupFor[$role->name] }}</code>
                            @else
                                <span class="text-sg-muted">No group</span>
                            @endisset
                        </td>
                        <th scope="row" class="p-4 font-semibold">
                            {{ \App\Core\Access\RoleName::tryFrom($role->name)?->label() ?? $role->name }}
                        </th>
                        <td class="p-4">{{ $role->users_count }}</td>
                        <td class="p-4">
                            <ul class="space-y-1">
                                @foreach ($role->permissions->sortBy('name') as $permission)
                                    <li>
                                        {{ $descriptions[$permission->name] ?? $permission->name }}
                                        <code class="text-sm text-sg-muted">{{ $permission->name }}</code>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
