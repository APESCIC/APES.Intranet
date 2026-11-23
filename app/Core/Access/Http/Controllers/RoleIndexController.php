<?php

namespace App\Core\Access\Http\Controllers;

use App\Core\Access\CoreAccessCatalog;
use App\Core\Audit\AuditLogger;
use App\Core\Auth\GroupRoleMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleIndexController
{
    public function __invoke(Request $request, AuditLogger $audit, GroupRoleMapper $mapper): View
    {
        Gate::authorize('viewAny', Role::class);
        $audit->record('admin.roles.viewed');

        /** @var array<string, string> $groupRoles */
        $groupRoles = config('intranet.group_roles', []);
        $order = array_values($groupRoles);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $order, true) ?: (in_array($role->name, $order, true) ? 0 : PHP_INT_MAX))
            ->values();

        $requested = (string) $request->query('role', '');
        $selectedRole = $roles->firstWhere('name', $requested) ?? $roles->first();

        return view('core.admin.roles.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'groupFor' => array_flip($groupRoles),
            'groupCount' => count($mapper->approvedGroups()),
            'descriptions' => CoreAccessCatalog::permissions(),
        ]);
    }
}
