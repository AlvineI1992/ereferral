<?php

namespace App\Http\Controllers;

use App\Models\RefEmrModel;
use App\Models\RefFacilitiesModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Str;

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

    public function list(Request $request)
    {
        $query = RefEmrModel::query();

        if ($search = $request->input('search')) {
            $query->where('emr_name', 'LIKE', "%{$search}%")
                  ->orderBy('created_at', 'asc');
        }
        $list = $query->where('status',1)->get(); // Paginate results
       return response()->json($list);
    }

    // Show the form for creating a new resource
    public function create()
    {
        // Show a form (if applicable) or return a response
    }


  public function store(Request $request, $emr_id = null)
    {
        $emr = $emr_id
            ? RefEmrModel::findOrFail($emr_id)
            : new RefEmrModel();

    $validated = $request->validate([
                'emr_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ref_emr', 'emr_name')->ignore($emr->emr_id, 'emr_id'),
            ],
            'status'  => ['required', 'boolean'], 
            'remarks' => 'nullable|string|max:20',
        ],
        [
        'emr_name.required' => 'Provider name is required.',       
        'emr_name.unique'   => 'This provider name is already taken.', 
        'emr_name.max'      => 'Provider name cannot exceed 50 characters.']);
    
        $validated['status'] = isset($validated['status']) && $validated['status'] ? 1 : 0;

        if (!$emr->exists) {
            $validated['uuid'] = base64_encode((string) Str::uuid());
        }
        
        $emr->fill($validated)->save();
        return redirect()->route('emr.index')->with('success', 'Data saved!');
    }

   
    public function show($id)
    {
        $data = RefEmrModel::findOrFail($id);
        return response()->json($data);
    }

    
    public function edit($LogID)
    {
       
    }

   
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
        $data = RefEmrModel::findOrFail($id);
        $data->delete();
        return response()->json(['message' => 'Record deleted successfully.']);
    }

    public function assign(Request $request)
    {
        $codes = $request->input('facilities', []); 
        $id = $request->input('emr_id'); // e.g. ['fac123','fac456']

        $updatedCount = $this->_assignFacilities($id, $codes);
      
        return response()->json([
            'message'       => "Assigned $updatedCount facilities to EMR $id.",
            'assignedCount' => $updatedCount,
        ]);
    }

    public function revoke(Request $request)
    {
        $codes = $request->input('facilities', []); 
        $id = $request->input('emr_id'); // e.g. ['fac123','fac456']

        $updatedCount = $this->_revokeFacilities($id, $codes);
      
        return response()->json([
            'message'       => "Assigned $updatedCount facilities to EMR $id.",
            'assignedCount' => $updatedCount,
        ]);
    }


    public function _assignFacilities(string $emrId, array $hfhudcodes)
    {
        return RefFacilitiesModel::whereIn('hfhudcode', $hfhudcodes)
                        ->update(['emr_id' => $emrId]);
    }

    public function _revokeFacilities(string $emrId, array $hfhudcodes)
    {
        return RefFacilitiesModel::whereIn('hfhudcode', $hfhudcodes)
                        ->update(['emr_id' =>null]);
    }

    function uuidToBase64(string $uuid): string
{
    return rtrim(
        strtr(
            base64_encode(hex2bin(str_replace('-', '', $uuid))),
            '+/',
            '-_'
        ),
        '='
    );
}
}
