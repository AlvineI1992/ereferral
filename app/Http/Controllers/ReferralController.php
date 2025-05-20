<?php

namespace App\Http\Controllers;

use App\Models\ReferralInformationModel;
use Illuminate\Http\Request;
use App\Helpers\ReferralHelper;

class ReferralController extends Controller
{
    
    public function index(Request $request)
    {
        $user = auth()->user();
  
        $role = $user?->getRoleNames()->first() ?? 'guest'; 
    
        $perPage = $request->input('per_page', 5); 
        $page = $request->input('page', 1); // default to page 1
    
        $query = ReferralInformationModel::with(['patientinformation', 'facility_from', 'facility_to', 'destination', 'track'])
            ->whereDoesntHave('track'); // This ensures you get referrals without a track
    
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
    
        // Handle search functionality
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patientinformation', function ($subQuery) use ($search) {
                    $subQuery->where('patientFirstName', 'LIKE', "%{$search}%")
                             ->orWhere('patientMiddlename', 'LIKE', "%{$search}%")
                             ->orWhere('patientLastname', 'LIKE', "%{$search}%");
                });
            });
        }
    
        // Perform pagination (role filters should already be applied here)
        $paginated = $query->orderBy('refferalDate', 'desc')->paginate($perPage, ['*'], 'page', $page);
    
        // Transform the data for response
        $transformedList = $paginated->getCollection()->map(function ($referral) {
            return [
                'LogID' => $referral->LogID,
                'patient_name' => $referral->patientinformation->patientFirstName.' '.$referral->patientinformation->patientMiddlename.' '.$referral->patientinformation->patientLastname,
                'patient_sex' => $referral->patientinformation->patientSex === 'M' ? 'Male' : 'Female',
                'patient_civilstatus' => $referral->patientinformation->patientCivilStatus,
                'referral_origin_code' => $referral->fhudFrom,
                'referral_origin_name' => optional($referral->facility_from)->facility_name,
                'referral_destination_code' => $referral->fhudTo,
                'referral_destination_name' => optional($referral->facility_to)->facility_name,
                'referral_reason_code' => $referral->referralReason,
                'referral_reason_description' => ReferralHelper::getReferralReasonbyCode($referral->referralReason)['description'],
                'referral_type_code' => $referral->typeOfReferral,
                'referral_type_description' => ReferralHelper::getReferralTypebyCode($referral->typeOfReferral)['description'],
                'referral_date' => \Carbon\Carbon::parse($referral->refferalDate)->format('m/d/Y'),
                'referral_time' => \Carbon\Carbon::parse($referral->refferalTime)->format('h:i a'),
                'referral_category' => $referral->referralCategory == 'ER' ? 'Emergency' : 'Outpatient',
                'referring_provider' => $referral->referringProvider,
                'contact_number' => $referral->referringProviderContactNumber,
            ];
        });
    
        // Return paginated response
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
        $decodedID = base64_decode($LogID);
    
        $referral = ReferralInformationModel::with(['patientinformation', 'facility_from', 'facility_to'])
            ->whereHas('patientinformation', function ($q) use ($decodedID) {
                $q->where('LogID', $decodedID); 
            })
            ->first(); 
    
        if (!$referral) {
            return response()->json(['message' => 'Referral not found.'], 404);
        }
    
        $data = [
            'patient' => $referral->patientinformation,
            'origin' => $referral->facility_from,
            'destination' => $referral->facility_to,
            'referral_info' => [
                'LogID' => $referral->LogID,
                'date' => $referral->refferalDate,
                'category' => $referral->referralCategory,
                'reason' =>  ReferralHelper::getReferralReasonbyCode($referral->referralReason)['description'],
                'type' =>  ReferralHelper::getReferralTypebyCode($referral->typeOfReferral)['description'],
            ],
        ];
    
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
