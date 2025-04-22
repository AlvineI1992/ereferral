<?php

namespace App\Http\Controllers;

use App\Models\RefEmrModel;
use Illuminate\Http\Request;

class RefEmrController extends Controller
{
    // Display a listing of the resource
    public function index()
    {
        $data = RefEmrModel::all();
        return response()->json($data);
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
        return response()->json($patient, 201);
    }

    // Display the specified resource
    public function show($LogID)
    {
        $data = RefEmrModel::findOrFail($LogID);
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
