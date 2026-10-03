<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Roles & Permissions screen (capstone SCOPE-11, NFR-REQ011).
 * The Super Admin ticks which permissions each role has; every page checks them (Phase 4).
 */
class RoleController extends Controller
{
    // Permissions the Super Admin role must always keep, so nobody can lock the system
    private const PROTECTED_SUPER_ADMIN = ['users.manage', 'roles.manage'];

    public function index(): View
    {
        return view('superadmin.roles.index', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('id')->get(),
            'permissionsByModule' => Permission::orderBy('module')->orderBy('id')->get()->groupBy('module'),
            'protected' => self::PROTECTED_SUPER_ADMIN,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $ids = collect($data['permissions'] ?? [])->map(fn ($id) => (int) $id);

        if ($role->slug === Role::SUPER_ADMIN) {
            $ids = $ids->merge(Permission::whereIn('slug', self::PROTECTED_SUPER_ADMIN)->pluck('id'))->unique();
        }

        $before = $role->permissions()->pluck('slug');
        $role->permissions()->sync($ids->all());
        $after = $role->permissions()->pluck('slug');

        $added = $after->diff($before)->values();
        $removed = $before->diff($after)->values();
        $summary = 'Changed permissions of ' . $role->name . '.'
            . ($added->isNotEmpty() ? ' Added: ' . $added->implode(', ') . '.' : '')
            . ($removed->isNotEmpty() ? ' Removed: ' . $removed->implode(', ') . '.' : '');
        ActivityLog::record('updated', 'Roles & Permissions', $summary, $role);

        return redirect()->route('superadmin.roles.index')->with('status', 'Permissions of ' . $role->name . ' were saved.');
    }
}
