<?php

namespace App\Http\Controllers;

use App\Models\RefFacilitiesModel;
use Illuminate\Http\Request;

class RefFacilitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('ref_facilities.facility_name', 'LIKE', "%{$search}%")
                      ->orWhere('ref_facilities.hfhudcode', 'LIKE', "%{$search}%")
                      ->orWhere('ref_facilitytype.description', '=', $search)
                      ->orWhere('ref_region.regname', '=', $search); // exact match
                });
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
    
        

        $facilities = $query->paginate(10); // This returns a LengthAwarePaginator

        return response()->json([
            'data' => $facilities->items(),
            'total' => $facilities->total(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
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
            'hfhudcode' => 'required|exists:ref_facilities,hfhudcode',
            'facility_name' => 'required|string',
            'factype_code' => 'required|string',
            'region' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
            'barangay' => 'required|string',
        ]);
        $data = [
            'hfhudcode'     => $request->hfhudcode,
            'facility_name' => $request->facility_name,
            'facility_type'  => $request->factype_code,
            'region_code'   => $request->region,
            'province_code' => $request->province,
            'city_code'     => $request->city,
            'bgycode' => $request->barangay
        ];

        $region = RefFacilitiesModel::create($data);

        return response()->json($region, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
    public function show(RefFacilitiesModel $RefFacilitiesModel)
    {
        return response()->json($RefFacilitiesModel);
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
    public function update(Request $request, RefFacilitiesModel $RefFacilitiesModel)
    {
        $request->validate([
            'hfhudcode' => 'required|string|max:50',
            'facility_name' => 'required|string|max:50',
            'fhud_address' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:1',
        ]);

        $RefFacilitiesModel->update($request->all());

        return response()->json($RefFacilitiesModel);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RefFacilitiesModel  $RefFacilitiesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(RefFacilitiesModel $RefFacilitiesModel)
    {
        $RefFacilitiesModel->delete();
        return response()->json(null, 204);
    }
}
