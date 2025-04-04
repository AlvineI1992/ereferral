<?php

namespace App\Http\Controllers;

use App\Models\RefEmrModel;
use Illuminate\Http\Request;

class RefEmrController extends Controller
{
    // Display a listing of the resource
    public function index()
    {
        $patients = RefEmrModel::all();
        return response()->json($patients);
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
            'LogID' => 'required|string|max:50|unique:referral_patientinfo,LogID',
            'FamilyID' => 'required|string|max:50',
            'phicNum' => 'nullable|string|max:20',
            'caseNum' => 'nullable|string|max:20',
            'patientLastName' => 'required|string|max:255',
            'patientFirstName' => 'required|string|max:255',
            'patientSuffix' => 'nullable|string|max:10',
            'patientMiddlename' => 'nullable|string|max:255',
            'patientBirthDate' => 'nullable|date',
            'patientSex' => 'nullable|string|max:10',
            'patientContactNumber' => 'nullable|string|max:20',
            'patientReligion' => 'nullable|string|max:100',
            'patientBloodType' => 'nullable|string|max:3',
            'patientBloodTypeRH' => 'nullable|string|max:3',
            'patientCivilStatus' => 'nullable|string|max:50',
        ]);

        $patient = ReferralPatientInfoModel::create($validated);

        return response()->json($patient, 201);
    }

    // Display the specified resource
    public function show($LogID)
    {
        $patient = ReferralPatientInfoModel::findOrFail($LogID);
        return response()->json($patient);
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
            'FamilyID' => 'required|string|max:50',
            'phicNum' => 'nullable|string|max:20',
            'caseNum' => 'nullable|string|max:20',
            'patientLastName' => 'required|string|max:255',
            'patientFirstName' => 'required|string|max:255',
            'patientSuffix' => 'nullable|string|max:10',
            'patientMiddlename' => 'nullable|string|max:255',
            'patientBirthDate' => 'nullable|date',
            'patientSex' => 'nullable|string|max:10',
            'patientContactNumber' => 'nullable|string|max:20',
            'patientReligion' => 'nullable|string|max:100',
            'patientBloodType' => 'nullable|string|max:3',
            'patientBloodTypeRH' => 'nullable|string|max:3',
            'patientCivilStatus' => 'nullable|string|max:50',
        ]);

        $patient = ReferralPatientInfoModel::findOrFail($LogID);
        $patient->update($validated);

        return response()->json($patient);
    }

    // Remove the specified resource from storage
    public function destroy($LogID)
    {
        $patient = ReferralPatientInfoModel::findOrFail($LogID);
        $patient->delete();

        return response()->json(['message' => 'Patient record deleted successfully.']);
    }
}
