<?php

namespace App\Http\Controllers;

use App\Models\RefRegionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\Facades\DataTables;

class RefRegionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
 

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $regions = RefRegionModel::select([ 'regcode', 'regname']);
    
            return DataTables::of($regions)
                ->addColumn('actions', function ($row) {
                    return '<div class="btn-group btn-sm">
                             <button class="btn btn-info btn-sm edit-btn" data-id="'.$row->id.'"><i class="fas fa-edit"></i>&nbsp;Edit</button> 
                             <button class="btn btn-success btn-sm province-btn" data-id="'.$row->id.'"><i class="fas fa-arrow-right"></i>&nbsp;Next</button>
                             </div>
                           ';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
    
        return inertia('RefRegion/Index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Return a view for creating a new region
        return view('ref_region.create');
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

        $region = RefRegionModel::create($request->all());

        return response()->json($region, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RefRegionModel  $RefRegionModel
     * @return \Illuminate\Http\Response
     */
    public function show(RefRegionModel $RefRegionModel)
    {
        return response()->json($RefRegionModel);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RefRegionModel  $RefRegionModel
     * @return \Illuminate\Http\Response
     */
    public function edit(RefRegionModel $RefRegionModel)
    {
        // Return a view for editing the region
        return view('ref_region.edit', compact('RefRegionModel'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RefRegionModel  $RefRegionModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RefRegionModel $RefRegionModel)
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

        $RefRegionModel->update($request->all());

        return response()->json($RefRegionModel);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RefRegionModel  $RefRegionModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(RefRegionModel $RefRegionModel)
    {
        $RefRegionModel->delete();
        return response()->json(null, 204);
    }
}
