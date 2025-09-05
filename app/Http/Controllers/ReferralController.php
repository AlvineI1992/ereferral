<?php

namespace App\Http\Controllers;

use App\Models\ReferralInformationModel;
use Illuminate\Http\Request;
use App\Helpers\ReferralHelper;
use Illuminate\Support\Facades\Crypt;
use App\Services\ReferralService;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }
    
    public function index(Request $request)
    {
        $user = auth()->user();
  
        $role = 'guest';

        if ($user && method_exists($user, 'getRoleNames')) {
            $roleName = $user->getRoleNames()->first();
            $role = $roleName ?: 'guest';
        }
    
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

           $referral_reason_desc  =  ReferralHelper::getReferralReasonbyCode($referral->referralReason);
           $referral_type_desc  =  ReferralHelper::getReferralTypebyCode($referral->typeOfReferral);
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
                'referral_reason_description' => ($referral_reason_desc)?$referral_reason_desc['description']: $referral->otherReason,
                'referral_type_code' => $referral->typeOfReferral,
                'referral_type_description' => ($referral_type_desc)?$referral_type_desc['description']:  $referral->otherReason,
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
            'patientFirstName' => 'required|string|max:50',
            'patientLastName' => 'required|string|max:50',
            'patientMiddleName' => 'required|string|max:50',
            'patientBirthDate' => 'required',
            'patientSuffix' => 'required',
            'patientSex' => 'required',
            'patientCivilStatus'=>'required',
            'region' => 'required',
            'province' => 'required',
            'city' => 'required',
            'barangay' => 'required',
            'typeOfReferral'=>'required',
            'calledDate'=>'required',
            'refferalDate'=>'required'
        ]);
        return response()->json($validated, 201);
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
    
        $reasonData = ReferralHelper::getReferralReasonbyCode($referral->referralReason);
        $typeData   = ReferralHelper::getReferralTypebyCode($referral->typeOfReferral);
        
        $reason = !empty($reasonData['description']) 
            ? $reasonData['description'] 
            : 'Unknown Reason';
        
        $type = !empty($typeData['description']) 
            ? $typeData['description'] 
            : 'Unknown Type';

            
        $data = [
            'patient' => $referral->patientinformation,
            'origin' => $referral->facility_from,
            'destination' => $referral->facility_to,
            'referral_info' => [
                'LogID' => $referral->LogID,
                'date' => $referral->refferalDate,
                'category' => $referral->referralCategory,
                'reason' => $reason,
                'type' => $type,
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

    public function generate_hfhudcode(Request $request)
    {
        try {
            // Get hfhudcode from query string (?hfhudcode=XXXX), default to empty string if not provided
            $hfhudcode = $request->query('hfhudcode', '');
    
            // Generate the transaction code via service
            $transaction_code = $this->referralService->generate_code($hfhudcode);
    
            return response()->json([
                'hfhudcode' => $transaction_code,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate HFHUD code.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function test()
    {
        $plaintext = '1';
        $encrypted = Crypt::encryptString($plaintext);
        echo "Encrypted: " . $encrypted;

    $decrypted = Crypt::decryptString($encrypted);
    //echo "Decrypted: " . $decrypted;

    }

}
