<?php

namespace App\Http\Controllers;

use App\Models\ReferralInformationModel;
use App\Models\ReferralPatientInfoModel;
use App\Models\ReferralPatientDemoModel;
use Illuminate\Http\Request;

class ReferralPatientInfoController extends Controller
{
    // Display a listing of the resource
    public function index(Request $request)
    {
        $user = auth()->user();
  
        $role = $user?->getRoleNames()->first() ?? 'guest'; 
        $query = ReferralPatientInfoModel::query();
    
        if ($search = $request->input('search')) {
            $query->where('patientLastname', 'LIKE', "%{$search}%")
                  ->orWhere('patientFirstName', 'LIKE', "%{$search}%");
        }
    
        // Handle role-based query adjustments
        $emr_id = $user->access_id ?? null;
        $hfhudcode = $user->hfhudcode ?? null;
    
        if ($role === 'EMR') {  
            // Use whereHas to filter by the destination relationship
            $query->whereHas('facility_to', function($query) use ($emr_id) {
                $query->where('emr_id', $emr_id); // This will filter based on emr_id in the related RefFacilitiesModel
            });
        } elseif ($role === 'Region') {
            // Use whereHas to filter by the destination relationship
            $query->whereHas('facility_to', function($query) use ($emr_id) {
                $query->where('region_code', $emr_id); // This will filter based on emr_id in the related RefFacilitiesModel
            });
            // Define region-specific logic here if applicable
        } elseif ($role === 'Hospital') {
            // Use whereHas to filter by the destination relationship
            $query->whereHas('facility_to', function($query) use ($emr_id) {
                $query->where('hfhudcode', $emr_id); // This will filter based on hfhudcode in the related RefFacilitiesModel
            });
        }
      
        $query->orderBy('LogID', 'desc');
    
        $paginated = $query->paginate(10);
    
        $transformedList = $paginated->getCollection()->map(function ($patient) {
            return [
                'LogID' => $patient->LogID,
                'patient_name' => $patient->patientFirstName.' '.$patient->patientMiddlename.' '.$patient->patientLastname,
                'sex' => ($patient->patientSex === 'M')? 'Male' : 'Female',
                'birthdate' => date('m/d/Y',strtotime($patient->patientBirthDate)),
                'civil_status' => $patient->patientCivilStatus,
            ];
        });
    
        return response()->json([
            'data' =>$transformedList,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
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
       /*  try { */
            $decodedID = base64_decode($LogID);
    
            // Retrieve the patient with related demographics
            $patient = ReferralPatientInfoModel::with('demographics')
                ->where('LogID', $decodedID)
                ->firstOrFail();

             $demographics = ReferralPatientDemoModel::with('region','province','city','barangay')
             ->where('LogID', $decodedID)
             ->firstOrFail();

            // Return the response
            return response()->json([
                'profile' => [
                    'fname' => $patient->patientFirstName,
                    'mname' => $patient->patientMiddlename,
                    'lname' => $patient->patientLastName,
                    'dob' => $patient->patientBirthDate,
                    'sex' => $patient->patientSex === 'M'?'Male':'Female',
                    'age' => $this->calculateAge($patient->patientBirthDate),
                    'avatar' => null, 
                ],
                'demographics' =>[
                    'street'=>$patient->patientStreetAddress ?? '',
                    'region'=>$demographics->region->regname ?? '',
                    'province'=>$demographics->province->provname,
                    'city'=>$demographics->city->cityname ?? '',
                    'barangay'=>$demographics->barangay->bgyname  ?? ''
                ]
            ]);
        /* } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid ID or data not found.'], 404);
        } */
    }
    // Optional helper method for age calculation
    protected function calculateAge($dob)
    {
        return \Carbon\Carbon::parse($dob)->age;
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
