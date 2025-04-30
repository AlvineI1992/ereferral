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
            /*'ref_province.provname',
            'ref_city.cityname',
            'ref_barangay.bgyname', */
            'ref_facilities.fhudaddress',
        ])
             ->leftJoin('ref_region', 'ref_facilities.region_code', '=', 'ref_region.regcode');
            /*->leftJoin('ref_province', 'ref_facilities.province_code', '=', 'ref_province.provcode')
            ->leftJoin('ref_city', 'ref_facilities.city_code', '=', 'ref_city.citycode')
            ->leftJoin('ref_barangay', 'ref_facilities.bgycode', '=', 'ref_barangay.bgycode'); */

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('ref_facilities.facility_name', 'LIKE', "%{$search}%")
                      ->orWhere('ref_facilities.hfhudcode', 'LIKE', "%{$search}%")
                      ->orWhere('ref_region.regname', '=', $search); // exact match
                });
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
            'regcode' => 'required|integer',
            'regname' => 'nullable|string|max:50',
            'regabbrev' => 'nullable|string|max:10',
            'nscb_reg_code' => 'nullable|string|max:2',
            'nscb_reg_name' => 'nullable|string|max:50',
            'UserLevelID' => 'nullable|integer',
            'addedby' => 'nullable|string|max:50',
            'dateupdated' => 'nullable|date',
            'status' => 'nullable|string|max:1',
        ]);

        $region = RefFacilitiesModel::create($request->all());

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
