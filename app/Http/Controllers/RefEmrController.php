<?php

namespace App\Http\Controllers;

use App\Models\RefEmrModel;
use Illuminate\Http\Request;

class RefEmrController extends Controller
{

    public function index(Request $request)
    {
        $query = RefEmrModel::query();

        if ($search = $request->input('search')) {
            $query->where('emr_name', 'LIKE', "%{$search}%")
                  ->orderBy('created_at', 'asc');
        }
    
        $roles = $query->paginate(10); // Paginate results
    
       return response()->json([
            'data' => $roles->items(),
            'total' => $roles->total(),
        ]); 
        
    }

    // Show the form for creating a new resource
    public function create()
    {
        // Show a form (if applicable) or return a response
    }

    // Store a newly created resource in storage
    public function store(Request $request)
    {
        $validated = $request->validate([
            'emr_name' => 'required|string|max:50|unique:ref_emr,emr_name',
            'status' => 'required',
            'remarks' => 'nullable|string|max:20',
        ]);

        $patient = RefEmrModel::create($validated);
        return redirect()->route('emr')->with('success', 'Data saved!');
    }

    // Display the specified resource
    public function show($id)
    {
        $data = RefEmrModel::findOrFail($id);
        return response()->json($data);
    }

    // Show the form for editing the specified resource
    public function edit($LogID)
    {
        // Show an edit form (if applicable) or return a response
    }

    // Update the specified resource in storage
    public function update(Request $request, $LogID)
    {
        $validated = $request->validate([
            'emr_name' => 'required|string|max:50|unique:ref_emr,emr_name',
            'status' => 'required',
            'remarks' => 'nullable|string|max:20',
        ]);

        $data = RefEmrModel::findOrFail($validated);
        $data->update($validated);
        return response()->json($data);
    }

    // Remove the specified resource from storage
    public function destroy($id)
    {
        $data = RefEmrModel::findOrFail($LogID);
        $data->delete();

        return response()->json(['message' => 'Record deleted successfully.']);
    }
}
