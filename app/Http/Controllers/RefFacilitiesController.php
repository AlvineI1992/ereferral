<?php

namespace App\Http\Controllers;

use App\Models\RefFacilitiesModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefFacilitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 10);
        $perPage = max(10, min($perPage, 100));

        $query = RefFacilitiesModel::select([
            'ref_facilities.hfhudcode',
            'ref_facilities.facility_name',
            'ref_facilities.status',
            'ref_region.regname',
            'ref_facilitytype.description',
            'ref_facilities.fhudaddress',
        ])
             ->leftJoin('ref_region', 'ref_facilities.region_code', '=', 'ref_region.regcode')
             ->leftJoin('ref_facilitytype', 'ref_facilitytype.factype_code', '=', 'ref_facilities.facility_type')
             ->orderBy('ref_facilities.fhud_seq','desc');

            $search = trim((string) $request->input('search', ''));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('ref_facilities.facility_name', 'LIKE', "%{$search}%")
                      ->orWhere('ref_facilities.hfhudcode', 'LIKE', "%{$search}%")
                      ->orWhere('ref_facilitytype.description', 'LIKE', "%{$search}%")
                      ->orWhere('ref_region.regname', 'LIKE', "%{$search}%");
                });
            }
        
            if ($request->filled('status') && in_array($request->status, ['A', 'I'], true)) {
                $query->where('ref_facilities.status', $request->status);
            }

            if ($request->boolean('not_assigned')) {
                $query->whereNull('ref_facilities.emr_id')
                      ->where('ref_facilities.status', 'A');
            }

            if ($request->filled('emr_id')) {
                $query->where('ref_facilities.emr_id',"{$request->emr_id}");
            }
        
            if ($request->filled('id')) {
                $query->where('ref_facilities.hfhudcode', 'like', "%{$request->id}%");
            }
        
            if ($request->filled('name')) {
                $query->where('ref_facilities.facility_name', 'like', "%{$request->name}%");
            }
        
            if ($request->filled('region')) {
                $query->where('ref_region.regname', 'like', "%{$request->region}%");
            }

        $facilities = $query->paginate($perPage); 

        return response()->json([
            'data' => $facilities->items(),
            'total' => $facilities->total(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
        ]);
    }

    public function facility_list()
    {
        $query = RefFacilitiesModel::select([
            'ref_facilities.hfhudcode',
            'ref_facilities.facility_name'
        ])->get();
        return response()->json([
            'data' => $query
        ]);
    }

    


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Return a view for creating a new region
        return view('ref_facilities.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'hfhudcode' => 'required|unique:ref_facilities,hfhudcode',
            'facility_name' => 'required|string|unique:ref_facilities,facility_name',
            'factype_code' => 'required|string',
            'region' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
            'barangay' => 'required|string',
        ]);
        $data = [
            'hfhudcode'     => $request->hfhudcode,
            'facility_name' => strtoupper($request->facility_name),
            'facility_type'  => $request->factype_code,
            'region_code'   => $request->region,
            'fhudaddress'=>$request->fhudaddress,
            'province_code' => $request->province,
            'city_code'     => $request->city,
            'bgycode' => $request->barangay,
            'status' => $request->status ? 'A':'I', 
        ];

        $region = RefFacilitiesModel::create($data);

        return redirect()->route('facilities')->with('message','Created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
    public function show($hfhudcode)
    {
        $facility = RefFacilitiesModel::where('hfhudcode', $hfhudcode)->first();

        if (!$facility) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($facility);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
    public function edit(RefFacilitiesModel $RefFacilitiesModel)
    {
        // Return a view for editing the region
        return view('ref_facilities.edit', compact('RefFacilitiesModel'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
   public function update(Request $request, $id = null)
{

      $facility = $id
            ? RefFacilitiesModel::findOrFail($id)
            : new RefFacilitiesModel();
    // Validate input
    $validated = $request->validate([
        'hfhudcode'     => ['required', 'string', 'max:50', Rule::unique('ref_facilities', 'hfhudcode')->ignore($facility->hfhudcode,'hfhudcode')],
        'facility_name' => ['required', 'string', 'max:50', Rule::unique('ref_facilities', 'facility_name')->ignore($facility->facility_name,'facility_name')],
        'fhudaddress'   => 'nullable|string|max:100',
        'factype_code'  => 'required|string',
        'region'        => 'required|string|max:10',
        'province'      => 'required|string|max:10',
        'city'          => 'required|string|max:10',
        'barangay'      => 'required|string|max:10',
        'status'        => 'required|boolean',
    ]);

    // Map fields
    $facility->update([
        'hfhudcode'     => $validated['hfhudcode'],
        'facility_name' => strtoupper($validated['facility_name']),
        'facility_type' => $validated['factype_code'],
        'region_code'   => $validated['region'],
        'province_code' => $validated['province'],
        'city_code'     => $validated['city'],
        'bgycode'       => $validated['barangay'],
        'fhudaddress'   => $validated['fhudaddress'] ?? null,
        'status'        => $validated['status'] ? 'A' : 'I',
    ]);

    return redirect()->route('facilities')->with('message','Updated successfully.');
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        $facility = RefFacilitiesModel::findOrFail($id);
        $facility->delete();

        return response()->json([
            'message' => 'Facility deleted successfully.',
        ]);
    }
}
