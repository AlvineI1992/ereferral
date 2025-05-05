<?php

namespace App\Http\Controllers;

use App\Models\ReferralInformationModel;
use Illuminate\Http\Request;
use App\Helpers\ReferralHelper;

class ReferralController extends Controller
{
    // Display a listing of the resource
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 5); // default to 10
        $page = $request->input('page', 1); // default to page 1
        $query = ReferralInformationModel::with(['patientinformation', 'facility_from', 'facility_to','track'])->whereDoesntHave('track');
 
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patientinformation', function ($subQuery) use ($search) {
                    $subQuery->where('patientFirstName', 'LIKE', "%{$search}%")
                             ->orWhere('patientMiddlename', 'LIKE', "%{$search}%")
                             ->orWhere('patientLastname', 'LIKE', "%{$search}%");
                });
            });
        }
    
      /*   $paginated = $query->orderBy('refferalDate', 'desc')->paginate(10); // Now execute the query */

        $paginated = $query->orderBy('refferalDate', 'desc')->paginate($perPage, ['*'], 'page', $page);
    
        $transformedList = $paginated->getCollection()->map(function ($referral) {
            return [
                'LogID' => $referral->LogID,
                'patient_name'=>$referral->patientinformation->patientFirstName.' '.$referral->patientinformation->patientMiddlename.' '.$referral->patientinformation->patientLastname,
                'patient_sex' => $referral->patientinformation->patientSex ==='M'? 'Male':'Female',
                'patient_birthdate' => date('m/d/Y',strtotime($referral->patientinformation->patientBirthDate)),
                'patient_civilstatus' => $referral->patientinformation->patientCivilStatus,
                'referral_origin_code' => $referral->fhudFrom,
                'referral_origin_name' => optional($referral->facility_from)->facility_name,
                'referral_destination_code' => $referral->fhudTo,
                'referral_destination_name' => optional($referral->facility_to)->facility_name,
                'referral_reason_code' =>$referral->referralReason,
                'referral_reason_description' => ReferralHelper::getReferralReasonbyCode($referral->referralReason)['description'],
                'referral_type_code' =>$referral->typeOfReferral,
                'referral_type_description'=>ReferralHelper::getReferralTypebyCode($referral->typeOfReferral)['description'],
                'referral_date' => date('m/d/Y', strtotime($referral->refferalDate)),
                'referral_time' => date('h:i A', strtotime($referral->refferalTime)),
                'referral_category' => $referral->referralCategory == 'ER'?'Emergency':'Outpatient',
                'referring_provider' => $referral->referringProvider,
                'contact_number' => $referral->referringProviderContactNumber,
            ];
        });
    
        return response()->json([
            'data' => $transformedList,
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
          
        ]);

        $patient = ReferralInformationModel::create($validated);

        return response()->json($patient, 201);
    }

    // Display the specified resource
    public function show($LogID)
    {
        $patient = ReferralInformationModel::findOrFail($LogID);
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
         
        ]);

        $patient = ReferralInformationModel::findOrFail($LogID);
        $patient->update($validated);

        return response()->json($patient);
    }

    // Remove the specified resource from storage
    public function destroy($LogID)
    {
        $patient = ReferralInformationModel::findOrFail($LogID);
        $patient->delete();

        return response()->json(['message' => 'Patient record deleted successfully.']);
    }
}
