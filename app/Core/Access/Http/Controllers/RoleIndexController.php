<?php

namespace App\Core\Access\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleIndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, array_column(RoleName::cases(), 'value'), true))
            ->values();

        return view('core.admin.roles.index', [
            'roles' => $roles,
            'descriptions' => AccessMatrix::permissions(),
        ]);
    }
}
