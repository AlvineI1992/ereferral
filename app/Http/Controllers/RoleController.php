<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoleModel;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = RoleModel::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('guard_name', 'LIKE', "%{$search}%")
                  ->orderBy('id', 'desc');
        }
    
        $roles = $query->paginate(10); // Paginate results
    
       return response()->json([
            'data' => $roles->items(),
            'total' => $roles->total(),
        ]); 
        
    }

   
    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('Roles/Create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
        ]);
        
        $role = RoleModel::create([
            'name' => ucfirst($request->input('name')),
            'guard_name' => $request->input('guard_name'),
        ]);
        
        return redirect()->route('roles')->with('success', 'Role created successfully.');
    }

    public function edit(RoleModel $role)
    {
        $permissions = Permission::all();
        return Inertia::render('Roles/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, RoleModel $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        //$role->syncPermissions($request->permissions);

        return redirect()->route('roles')->with('success', 'Role updated successfully.');
    }

    public function show($id)
    {
        $data = RoleModel::findOrFail($id);
        return response()->json($data);
    }


    public function destroy(RoleModel $role)
    {
        $role->delete();
        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
