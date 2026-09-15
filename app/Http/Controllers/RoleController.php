<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoleModel;
/* use App\Models\PermissionModel; */
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request, \App\Services\AccessDirectoryService $directory)
    {
        return response()->json($directory->list($request, true));
    }

    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('roles/Create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|max:50',
        ]);
     
        $role = RoleModel::create([
            'name' => ucfirst($request->input('name')),
            'guard_name' => $request->input('guard_name'),
        ]);
        
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(RoleModel $role)
    {
        $permissions = RoleModel::all();
        return Inertia::render('Roles/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, RoleModel $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'guard_name' => 'required|string|max:50',
            'permissions' => 'array'
        ]);

        $role->update(
        ['name' => $request->name,
         'guard_name' => $request->guard_name,
        ]);
        //$role->syncPermissions($request->permissions);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function show($id)
    {
        $data = RoleModel::findOrFail($id);
        return response()->json($data);
    }

    public function assignPermissions(Request $request, $id)
    {
        $validated = $request->validate([
            'permissionids' => 'required|array|min:1',
            'permissionids.*' => 'integer|exists:permissions,id',
        ]);
    
        $permissionIds = $validated['permissionids'];
        $role = Role::findOrFail($id);
    
        // Match permissions with correct guard
        $permissions = PermissionModel::whereIn('id', $permissionIds)
            ->where('guard_name', $role->guard_name)
            ->get();
    
        // Filter out already assigned permissions
        $newPermissions = $permissions->filter(fn($permission) => !$role->hasPermissionTo($permission));
    
        if ($newPermissions->isNotEmpty()) {
            $role->givePermissionTo($newPermissions);
    
            // Add flag for frontend
            $newPermissions->each(fn ($p) => $p->is_in_role = true);
    
            return response()->json([
                'success' => true,
                'permissions' => $newPermissions,
                'message' => $newPermissions->count() . ' permission(s) assigned to role successfully!',
            ]);
        }
    
        return response()->json([
            'success' => false,
            'message' => 'No new permissions to assign.',
        ]);
    }


    public function revokePermissions($id)
    {
        $validated = request()->validate([
            'permissionsids' => 'required|array|min:1',
            'permissionsids.*' => 'integer|exists:permissions,id',
        ]);
        $role = Role::findOrFail($id);
        $permissions = PermissionModel::whereIn('id', $validated['permissionsids'])
            ->whereIn('id', $role->permissions()->pluck('id'))
            ->get();
    
        $count = 0;
    
        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission);
            $count++;
        }
    
        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'message' => $count === 0
                ? 'No permission to revoke.'
                : "{$count} permission(s) revoked from role successfully!",
        ]);
    }
    
    
    public function destroy(RoleModel $role)
    {
        $role->delete();
        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
