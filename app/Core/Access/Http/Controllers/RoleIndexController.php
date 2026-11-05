<?php

namespace App\Core\Access\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Audit\AuditLogger;
use App\Core\Auth\GroupRoleMapper;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleIndexController
{
    public function __invoke(AuditLogger $audit, GroupRoleMapper $mapper): View
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

        return view('core.admin.roles.index', [
            'roles' => $roles,
            'groupFor' => array_flip($groupRoles),
            'groupCount' => count($mapper->approvedGroups()),
            'descriptions' => AccessMatrix::permissions(),
        ]);
    }
}
