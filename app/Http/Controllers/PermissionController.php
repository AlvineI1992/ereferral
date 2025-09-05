<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PermissionModel;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = PermissionModel::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('guard_name', 'LIKE', "%{$search}%")
                  ->orderBy('id', 'desc');
        }
    
        $data = $query->paginate(10); // Paginate results
    
       return response()->json([
            'data' => $data->items(),
            'total' => $data->total(),
        ]); 
    }

    public function permission_has_role(Request $request)
    {
        $query = PermissionModel::query();
    
        // Apply search filter if provided
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('guard_name', 'LIKE', "%{$search}%");
            });
        }
    
        // ✅ Get assigned permission IDs if role_id is provided
        $assignedPermissions = [];
        if ($roleId = $request->input('role_id')) {
            $assignedPermissions = \DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();
            
                $isInclude = filter_var($request->input('is_include'), FILTER_VALIDATE_BOOLEAN);

                if ($isInclude) {
                    // Get permissions that are NOT assigned to the role
                    $query->whereNotIn('id', $assignedPermissions);
                } else {
                    // Get permissions that ARE assigned to the role
                    $query->whereIn('id', $assignedPermissions);
                }
            
        }
    
        $query->orderBy('id', 'desc');
        $permissions = $query->paginate(10);
    
        // ✅ Add `is_assigned` flag
        $permissions->getCollection()->transform(function ($item) use ($assignedPermissions) {
            $item->is_assigned = in_array($item->id, $assignedPermissions);
            return $item;
        });
    
        return response()->json([
            'data' => $permissions->items(),
            'total' => $permissions->total(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(
            [
            'name' => 'required|string|max:255|unique:'.config('permission.table_names.permissions', 
            'permissions').',name'
          ]);
       
        PermissionModel::create(['name' => $request->name , 'guard_name'=> 'web' ]);
        
        return redirect()->route('permission.index')->with('message','Permission created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = PermissionModel::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        return view('admin.permission.edit',compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response    
     */
    public function update(Request $request, $id)
    {
        $permission = PermissionModel::findOrFail($id); // This will throw 404 if not found
    
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'guard_name' => 'required'
        ]);
    
        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);
        return redirect()->route('permission.index')->with('message', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */

    public function destroy(PermissionModel $permission)
    {
        $permission = PermissionModel::findOrFail($id);
        $permission->delete();
    
        return response()->json(['message' => 'Permission deleted successfully']);
    }
    
    
}
