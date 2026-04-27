<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function resolvePermissions(Request $request): ?array
    {
        if ($request->boolean('unrestricted', true)) {
            return null; // null = full access
        }
        $raw      = json_decode($request->input('permissions_json', '[]'), true);
        $allKeys  = array_keys(User::ALL_PERMISSIONS);
        $filtered = array_values(array_intersect($raw ?? [], $allKeys));
        return $filtered; // [] = no access, ['x',...] = specific access
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'       => 'required|string|max:80',
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => 'nullable|string|max:200',
        ]);

        // Generate unique slug from label
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($data['label'])));
        $name = $base;
        $i    = 2;
        while (Role::where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }

        Role::create([
            'name'        => $name,
            'label'       => $data['label'],
            'color'       => $data['color'],
            'description' => $data['description'] ?? null,
            'is_system'   => false,
            'permissions' => $this->resolvePermissions($request),
        ]);

        AuditLogger::log(
            'role.created',
            null,
            'Role "' . $data['label'] . '" created',
            ['role_name' => $name, 'label' => $data['label']]
        );

        return back()->with('role_success', "Role \"{$data['label']}\" created successfully.");
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'label'       => 'required|string|max:80',
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => 'nullable|string|max:200',
        ]);

        $role->update([
            'label'       => $data['label'],
            'color'       => $data['color'],
            'description' => $data['description'] ?? null,
            'permissions' => $this->resolvePermissions($request),
        ]);

        AuditLogger::log(
            'role.updated',
            null,
            'Role "' . $role->label . '" updated',
            ['role_name' => $role->name, 'label' => $role->label]
        );

        return back()->with('role_success', "Role \"{$role->label}\" updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('role_error', 'System roles cannot be deleted.');
        }

        // Reassign users of this role to "user"
        $role->users()->update(['role' => 'user']);

        $label = $role->label;
        AuditLogger::log(
            'role.deleted',
            null,
            'Role "' . $label . '" deleted',
            ['role_name' => $role->name, 'label' => $label]
        );
        $role->delete();

        return back()->with('role_success', "Role \"{$label}\" deleted. Affected users moved to User role.");
    }
}
