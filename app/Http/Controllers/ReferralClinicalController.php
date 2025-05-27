<?php

namespace App\Http\Controllers;

use App\Models\ReferralClinicalModel;
use Illuminate\Http\Request;

class ReferralClinicalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ReferralClinicalModel $referralClinicalModel,$LogID)
    {
        $clinical_data=ReferralClinicalModel::findOrFail(base64_decode($LogID));
        $data = [
        'LogID'=>$clinical_data->LogID ?? 'N/A',
        'diagnosis'=>$clinical_data->clinicalDiagnosis ?? 'N/A',
        'history'=>$clinical_data->clinicalHistory  ?? 'N/A' ,
        'physical_examination'=>$clinical_data->physicalExamination  ?? 'N/A',
        'chief_complaint'=>$clinical_data->chiefComplaint  ?? 'N/A',
        'findings'=>$clinical_data->findings  ?? 'N/A',
        'vitals'=>json_decode($clinical_data->vitals),
        ];
            return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReferralClinicalModel $referralClinicalModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReferralClinicalModel $referralClinicalModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReferralClinicalModel $referralClinicalModel)
    {
        //
    }
}
