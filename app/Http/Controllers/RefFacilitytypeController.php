<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RefFacilitytypeModel;
use Illuminate\Http\Request;

class RefFacilitytypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $query = RefFacilitytypeModel::query();

        if ($search = $request->input('search')) {
            $query->where('factype_code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orderBy('factype_code', 'desc');
        }
    
        $roles = $query->paginate(10); // Paginate results
    
       return response()->json([
            'data' => $roles->items(),
            'total' => $roles->total(),
        ]); 
    }

    public function list(Request $request)
    {
        $query = RefFacilitytypeModel::select('factype_code', 'description');
    
        if ($search = $request->input('search')) {
            $query->where('factype_code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orderBy('factype_code', 'desc');
        }
    
        $list = $query->get();
    
        return response()->json($list);
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
        $request->validate(['name' => 'required|string|max:255|unique:'.config('permission.table_names.permissions', 'permissions').',name']);
       
        PermissionModel::create(['name' => $request->name , 'guard_name'=> 'web' ]);
        
        return redirect()->route('permission')->with('message','Permission created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(PermissionModel $permission)
    {
        return view('admin.permission.show',compact('permission'));
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
    public function update(Request $request, Permission $permission)
    {
        $request->validate(['name' => 'required|string|max:255|unique:'.config('permission.table_names.permissions', 'permissions').',name,'.$permission->id,]);
        $permission->update(['name' => $request->name , 'guard_name'=> 'web' ]);
        return redirect()->route('permission.index')->with('message','Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permission.index')->with('message','Permission deleted successfully');
    }
}
