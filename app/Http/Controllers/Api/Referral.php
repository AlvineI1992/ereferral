<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\ReferralService;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientReferralRequest;
use Illuminate\Support\Facades\Auth;


use Illuminate\Auth\AuthenticationException;
use App\Models\RefRegionModel;
use App\Models\RefProvinceModel;
use App\Models\RefCityModel;
use App\Models\RefBarangayModel;

use App\Models\RefFacilitiesModel;

use App\Models\ReferralInformationModel as ReferralModel;
use App\Models\ReferralTrackModel;
use App\Models\RefFacilityModel;
use App\Models\ReferralPatientInfoModel;
use App\Helpers\ReferralHelper;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(title="Referral Api Documentation", version="1.0")
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Use a Sanctum Bearer token to access secured endpoints",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="sanctum"
 * )
 */
class Referral extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * User login to obtain a Sanctum Bearer token.
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="User Login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login with token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="ACCESS_TOKEN")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function login(Request $request)
    {
        // Validate login credentials (email and password)
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Authenticate user and create Sanctum token
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Create Sanctum token
            $token = $user->createToken('SanctumApp')->plainTextToken;

            // Return token as response
            return response()->json([
                'token' => $token
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
 * Referral a patient to another facility.
 *
 * @OA\Post(
 *     path="/api/refer_patient",
 *     tags={"Transactions"},
 *     summary="Referral a patient to another facility",
 *     security={{ "sanctum": {} }},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="referral", type="object",
 *                 @OA\Property(property="facility_from", type="string", example="DOH000000000007520"),
 *                 @OA\Property(property="facility_to", type="string", example="DOH000000000005280"),
 *                 @OA\Property(property="contact_no", type="string", example="1234567810"),
 *                 @OA\Property(property="type_referral", type="string", example="TRANS"),
 *                 @OA\Property(property="category", type="string", example="ER"),
 *                 @OA\Property(property="reason", type="string", example="SEFTA"),
 *                 @OA\Property(property="other_reason", type="string", example=""),
 *                 @OA\Property(property="remarks", type="string", example=""),
 *                 @OA\Property(property="contact_person", type="string", example="RECEIVING PERSONNEL"),
 *                 @OA\Property(property="designation", type="string", example=""),
 *                 @OA\Property(property="refer_date", type="string", example="12-12-2012"),
 *                 @OA\Property(property="refer_time", type="string", example="13:00")
 *             ),
 *             @OA\Property(property="patient", type="object",
 *                 @OA\Property(property="family_number", type="string", example="0001"),
 *                 @OA\Property(property="phic_number", type="string", example="123123123"),
 *                 @OA\Property(property="case_no", type="string", example="2022-000001"),
 *                 @OA\Property(property="last_name", type="string", example="REFERRAL"),
 *                 @OA\Property(property="first_name", type="string", example="PATIENT"),
 *                 @OA\Property(property="suffix", type="string", example="N/A"),
 *                 @OA\Property(property="middle_name", type="string", example="TEST"),
 *                 @OA\Property(property="birthdate", type="string", example="12-12-2012"),
 *                 @OA\Property(property="sex", type="string", example="M"),
 *                 @OA\Property(property="civil_status", type="string", example="D"),
 *                 @OA\Property(property="religion", type="string", example="CATHO"),
 *                 @OA\Property(property="blood_type", type="string", example="A"),
 *                 @OA\Property(property="blood_rh", type="string", example="+"),
 *                 @OA\Property(property="contact_no", type="string", example="")
 *             ),
 *             @OA\Property(property="demographics", type="object",
 *                 @OA\Property(property="street", type="string", example="#4"),
 *                 @OA\Property(property="brgy_code", type="string", example="043405061"),
 *                 @OA\Property(property="city_code", type="string", example="043405"),
 *                 @OA\Property(property="prov_code", type="string", example="0434"),
 *                 @OA\Property(property="reg_code", type="string", example="04"),
 *                 @OA\Property(property="zipcode", type="string", example="4027")
 *             ),
 *             @OA\Property(property="clinical", type="object",
 *                 @OA\Property(property="diagnosis", type="string", example="INJURY"),
 *                 @OA\Property(property="history", type="string", example=""),
 *                 @OA\Property(property="physical_examination", type="string", example=""),
 *                 @OA\Property(property="chief_complaint", type="string", example="CHIEF COMPLAINT"),
 *                 @OA\Property(property="findings", type="string", example="INJURY")
 *             ),
 *             @OA\Property(property="ICD", type="array",
 *                 @OA\Items(type="string", example="S91.0")
 *             ),
 *             @OA\Property(property="vital_signs", type="object",
 *                 @OA\Property(property="BP", type="string", example=""),
 *                 @OA\Property(property="temp", type="string", example=""),
 *                 @OA\Property(property="HR", type="string", example=""),
 *                 @OA\Property(property="RR", type="string", example=""),
 *                 @OA\Property(property="O2_sats", type="string", example=""),
 *                 @OA\Property(property="weight", type="string", example=""),
 *                 @OA\Property(property="height", type="string", example="")
 *             ),
 *             @OA\Property(property="patient_providers", type="array",
 *                 @OA\Items(type="object",
 *                     @OA\Property(property="provider_last_name", type="string", example="DOCTOR"),
 *                     @OA\Property(property="provider_first_name", type="string", example="DOCTOR"),
 *                     @OA\Property(property="provider_middle_name", type="string", example="DOCTOR"),
 *                     @OA\Property(property="provider_suffix", type="string", example=""),
 *                     @OA\Property(property="provider_contact_no", type="string", example="12345678910"),
 *                     @OA\Property(property="provider_type", type="string", example="REFER|CONSU")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Patient referred successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Patient referred successfully!"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input data",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Invalid data format")
 *         )
 *     )
 * )
 */

 public function patient_referral(PatientReferralRequest $request)
 {
     // You can get both raw and validated data easily
 
      $validatedData = $request->validated();
     
     $rawData = $request->all(); // Already parsed JSON
 
     // Optional: Merge if needed
     $mergedData = array_merge($rawData, $validatedData);
 
     // Check referring facility
     $check_from = RefFacilityModel::where('hfhudcode', $mergedData['referral']['facility_from'])->first();
 
     if (!$check_from || empty($check_from->emr_id)) {
         return response()->json([
             'error' => 'Referring facility not registered to any EMR provider!'
         ], 400);
     }
 
     // Check referred-to facility
     $check_to = RefFacilityModel::where('hfhudcode', $mergedData['referral']['facility_to'])->first();
 
     if (!$check_to || empty($check_to->emr_id)) {
         return response()->json([
             'error' => 'Referral facility not registered to any EMR provider!'
         ], 400);
     }
 
     // Send to referral service
     $output = $this->referralService->refer_patient($mergedData);
 
     return $output;
 }
 

    /**
     * Generate a reference code.
     *
     * @OA\Get(
     *     path="/api/generate_code/{fhudcode}",
     *     tags={"References"},
     *     summary="Generate reference code",
     *     security={{ "sanctum": {} }},
     *     @OA\Parameter(
     *         name="fhudcode",
     *         in="path",
     *         required=true,
     *         description="The FHUD code for the referral",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with generated code",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reference", type="string", example="HOSP-6050225100146")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facility not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Facility not found")
     *         )
     *     )
     * )
     */
    public function generate_reference($fhudcode)
    {
        $code = $this->referralService->generate_code($fhudcode);
        if ($code) {
            return response()->json(['code' => $code]);
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Generate a demographic reference.
     *
     * @OA\Get(
     *     path="/api/demographics",
     *     tags={"References"},
     *     summary="Generate demographic library",
     *     security={{ "sanctum": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with demographic data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="regions", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="provinces", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="code", type="string"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="cities", type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="code", type="string"),
     *                                     @OA\Property(property="name", type="string"),
     *                                     @OA\Property(property="barangays", type="array",
     *                                         @OA\Items(
     *                                             type="object",
     *                                             @OA\Property(property="code", type="string"),
     *                                             @OA\Property(property="name", type="string")
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No Found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function demographic_reference(Request $request)
    {
    

        if (!Auth::check()) {
            // If not authenticated, this will trigger the unauthenticated handler
            return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
        }


        // Retrieve the regions, provinces, cities, and barangays data
        $regions = RefRegionModel::with([
            'provinces.cities.barangays'
        ])->get();

        // Format the data into the required structure
        $result = $regions->map(function ($region) {
            return [
                'code' => $region->regcode,
                'name' => $region->regname,
                'provinces' => $region->provinces->map(function ($province) {
                    return [
                        'code' => $province->provcode,
                        'name' => $province->provname,
                        'cities' => $province->cities->map(function ($city) {
                            return [
                                'code' => $city->citycode,
                                'name' => $city->cityname,
                                'barangays' => $city->barangays->map(function ($barangay) {
                                    return [
                                        'code' => $barangay->bgycode,
                                        'name' => $barangay->bgyname,
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });

        // Return the formatted data as JSON response
        return response()->json([
            'regions' => $result
        ])->header('Content-Type', 'application/json');
    }

  /**
 * Get a specific region by ID.
 *
 * @OA\Get(
 *     path="/api/region/{id}",
 *     tags={"References"},
 *     summary="Get a specific region by ID",
 *     description="Returns a region with the given ID",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The ID of the region",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with region data",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="region", type="object",
 *                 @OA\Property(property="regcode", type="string", example="01"),
 *                 @OA\Property(property="regname", type="string", example="Ilocos Region")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Region not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Region not found")
 *         )
 *     )
 * )
 */
public function region($id)
{
    if (!Auth::check()) {
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }


    $region = RefRegionModel::select('regcode', 'regname')->where('regcode', $id)->first();

    if (!$region) {
        return response()->json(['error' => 'Region not found'], 404);
    }

    return response()->json([
        'region' => $region
    ])->header('Content-Type', 'application/json');
}

/**
 * Get a specific province by name.
 *
 * @OA\Get(
 *     path="/api/province/{id}",
 *     tags={"References"},
 *     summary="Get a specific province by name",
 *     description="Returns a province with the given name",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="name",
 *         in="path",
 *         required=true,
 *         description="The name of the province",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with province data",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="province", type="object",
 *                 @OA\Property(property="provcode", type="string", example="0128"),
 *                 @OA\Property(property="provname", type="string", example="Ilocos Norte")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Province not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Province not found")
 *         )
 *     )
 * )
 */
public function province($id)
{

    if (!Auth::check()) {
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }


    $province = RefProvinceModel::select('regcode','provcode', 'provname')->where('provcode', $id)->first();

    if (!$province) {
        return response()->json(['error' => 'Province not found'], 404);
    }

    return response()->json([
        'province' => $province
    ])->header('Content-Type', 'application/json');
}

/**
 * Get a specific city by code.
 *
 * @OA\Get(
 *     path="/api/city/{id}",
 *     tags={"References"},
 *     summary="Get a specific city by code",
 *     description="Returns a city with the given citycode",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="City code",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with city data",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="city", type="object",
 *                 @OA\Property(property="citycode", type="string", example="012801"),
 *                 @OA\Property(property="cityname", type="string", example="Laoag City")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="City not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="City not found")
 *         )
 *     )
 * )
 */


public function city($id)
{

    if (!Auth::check()) {
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }


    $province = RefCityModel::select('provcode','citycode', 'cityname')->where('citycode', $id)->first();

    if (!$province) {
        return response()->json(['error' => 'City not found'], 404);
    }

    return response()->json([
        'province' => $province
    ])->header('Content-Type', 'application/json');
}

/**
 * Get a specific barangay by ID.
 *
 * @OA\Get(
 *     path="/api/barangay/{id}",
 *     tags={"References"},
 *     summary="Get a specific barangay by ID",
 *     description="Returns a barangay with the given ID",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The ID of the barangay",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with barangay data",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="barangay", type="object",
 *                 @OA\Property(property="citycode", type="string", example="0128"),
 *                 @OA\Property(property="bgycode", type="string", example="012801001"),
 *                 @OA\Property(property="bgyname", type="string", example="Barangay Uno")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Barangay not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Barangay not found")
 *         )
 *     )
 * )
 */
public function barangay($id)
{
    if (!Auth::check()) {
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }


    $barangay = RefBarangayModel::select('citycode', 'bgycode', 'bgyname')
        ->where('bgycode', $id)
        ->first();

    if (!$barangay) {
        return response()->json(['error' => 'Barangay not found'], 404);
    }

    return response()->json([
        'barangay' => $barangay
    ])->header('Content-Type', 'application/json');
}


/**
 * 
 *  Get facility information by fhudcode/facility code.
 *
 * @OA\Get(
 *     path="/api/facility/{id}",
 *     tags={"References"},
 *     summary="Get facility by ID",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Facility HFHUDCODE",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="hfhudcode", type="string", example="12345"),
 *                 @OA\Property(property="facility_name", type="string", example="General Hospital")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Facility not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Facility not found")
 *         )
 *     )
 * )
 */

public function get_facility_list($id)
{
    if (!Auth::check()) {
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }
    

    $facility = RefFacilitiesModel::select([
        'ref_facilities.hfhudcode',
        'ref_facilities.facility_name',
        'ref_facilities.status',
        'ref_facilitytype.description',
        'ref_facilities.fhudaddress as address',
        'ref_region.regname as region',
        'ref_province.provname as province',
        'ref_city.cityname as city',
        'ref_barangay.bgyname as barangay',
    ])
         ->leftJoin('ref_region', 'ref_facilities.region_code', '=', 'ref_region.regcode')
         ->leftJoin('ref_province', 'ref_region.regcode', '=', 'ref_province.regcode')
         ->leftJoin('ref_city', 'ref_city.provcode', '=', 'ref_province.provcode')
         ->leftJoin('ref_barangay', 'ref_barangay.citycode', '=', 'ref_city.citycode')
         ->leftJoin('ref_facilitytype', 'ref_facilitytype.factype_code', '=', 'ref_facilities.facility_type')
         ->orderBy('ref_facilities.fhud_seq','desc')
    ->where('hfhudcode', $id)
    ->first();

    if (!$facility) {
        return response()->json(['error' => 'Facility not found'], 404);
    }

    return response()->json([
        'data' => $facility
    ])->header('Content-Type', 'application/json');
}
/**
 * Get referral data with related patient and clinical information.
 *
 * @OA\Get(
 *     path="/api/get-referral-information/{id}",
 *     tags={"Transactions"},
 *     summary="Get full referral data by LogID",
 *     description="Returns referral data including clinical, patient info, and demographic data.",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="LogID of the referral",
 *         @OA\Schema(type="string", example="LOG123456")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with referral data",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="LogID", type="string", example="LOG123456"),
 *                 @OA\Property(property="referral_information", type="object",
 *                     @OA\Property(property="LogID", type="string", example="LOG123456"),
 *                     @OA\Property(property="referral_reason", type="string", example="SEFTA")
 *                 ),
 *                 @OA\Property(property="patient_information", type="object",
 *                     @OA\Property(property="patient_lastname", type="string", example="Doe")
 *                 ),
 *                 @OA\Property(property="demographic_information", type="object",
 *                     @OA\Property(property="address", type="string", example="123 Main St.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Referral not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Referral not found")
 *         )
 *     )
 * )
 */

 public function getReferralData($id)
 {
    if (!Auth::check()) {
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }

     $referral = ReferralModel::with([
         'patientinformation',
         'facility_to',
         'facility_from',
         'medication',
         'demographics',
         'clinical',
     ])->where('LogID', $id)->first();


     $consulting = DB::table('referral_provider')
     ->where('LogID', $id)
     ->where('provider_type', 'CONSU')
     ->first();

      $referring = DB::table('referral_provider')
     ->where('LogID', $id)
     ->where('provider_type', 'REFER')
     ->first();

     if (!$referral) {
         return response()->json(['error' => 'Referral not found'], 404);
     }
 
     if (!empty($referral->clinical) && !empty($referral->clinical->vitals)) {
         
         if (is_string($referral->clinical->vitals)) {
             $referral->clinical->vitals = json_decode($referral->clinical->vitals, true);
         }
     }

     $transformedDemographics = [];
     if ($referral->demographics) {
         $transformedDemographics['address'] = $referral->demographics->patientStreetAddress ?? null;
         $transformedDemographics['barangay_code'] = $referral->demographics->patientBrgyCode;
         $transformedDemographics['barangay'] = ReferralHelper::getBarangay($referral->demographics->patientBrgyCode);
         $transformedDemographics['city_code'] = $referral->demographics->patientMundCode;
         $transformedDemographics['city'] = ReferralHelper::getCity($referral->demographics->patientMundCode); 
         $transformedDemographics['province_code'] = $referral->demographics->patientProvCode;
         $transformedDemographics['province'] = ReferralHelper::getProvince($referral->demographics->patientProvCode); 
         $transformedDemographics['region_code'] = $referral->demographics->patientRegCode;
         $transformedDemographics['region'] = ReferralHelper::getRegion($referral->demographics->patientRegCode); 
         $transformedDemographics['zipcode'] =$referral->demographics->patientZipCode;
     }else{
        $transformedDemographics['address'] = '';
        $transformedDemographics['barangay_code'] = '';
        $transformedDemographics['barangay'] = '';
        $transformedDemographics['city_code'] = '';
        $transformedDemographics['city'] = '';
        $transformedDemographics['province_code'] = '';
        $transformedDemographics['province'] = '';
        $transformedDemographics['region_code'] = '';
        $transformedDemographics['region'] = '';
        $transformedDemographics['zipcode'] = '';
     }
 
  
     $transformedClinical = [];
     if ($referral->clinical) {
        $diagnosis = $referral->clinical->clinicalDiagnosis;
        $transformedClinical['diagnosis'] = is_string($diagnosis) ? trim($diagnosis) : null;
         $transformedClinical['history'] = $referral->clinical->clinicalHistory ?? null;
         $transformedClinical['chief_complaint'] = $referral->clinical->chiefComplaint ?? null;

         $vitalsRaw = $referral->clinical->vitals;
         $vitalsigns = null;
         
         if (is_string($vitalsRaw)) {
             $decoded = json_decode(stripslashes(trim($vitalsRaw, '"')), true);
             $vitalsigns = $decoded ?: null;
         }
         
         $transformedClinical['vitalsigns'] = $vitalsigns;
         $transformedClinical['findings'] = $referral->clinical->findings ?? null;
         $transformedClinical['physical_examination'] = $referral->clinical->physicalExamination ?? null;
     }else{
        $transformedClinical['diagnosis'] = '';
         $transformedClinical['history'] = '';
         $transformedClinical['chief_complaint'] = '';
         $transformedClinical['vitalsigns'] =  [];
         $transformedClinical['findings'] = '';
         $transformedClinical['physical_examination']='';
     }

      $transformedPatient = [];

      if ($referral->patientinformation) {
          $transformedPatient['patient_lastname'] = strtoupper($referral->patientinformation->patientLastName ?? null);
          $transformedPatient['patient_firstname'] = strtoupper($referral->patientinformation->patientFirstName ?? null);
          $transformedPatient['patient_middlename'] = strtoupper($referral->patientinformation->patientMiddlename ?? null);
          $transformedPatient['patient_suffix'] = strtoupper($referral->patientinformation->patientSuffix ?? null);
          $transformedPatient['patient_birthdate'] = date('m/d/Y',strtotime($referral->patientinformation->patientBirthDate));
          $transformedPatient['patient_sex'] = $referral->patientinformation->patientSex ?? null;
          $transformedPatient['patient_civilstatus'] = $referral->patientinformation->patientCivilStatus ?? null;
          $transformedPatient['patient_contact'] = $referral->patientinformation->patientContactNumber ?? null;
          $transformedPatient['patient_religion'] = $referral->patientinformation->patientReligion ?? null;
          $transformedPatient['patient_blood'] = $referral->patientinformation->patientBloodType ?? null;
          $transformedPatient['patient_bloodRH'] = $referral->patientinformation->patientBloodRH ?? null;
      }else{
            $transformedPatient['patient_lastname'] ='';
            $transformedPatient['patient_firstname'] = '';
            $transformedPatient['patient_middlename'] ='';
            $transformedPatient['patient_birthdate'] = '';
            $transformedPatient['patient_sex'] = '';
            $transformedPatient['patient_civilstatus'] = '';
            $transformedPatient['patient_contact'] = '';
            $transformedPatient['patient_religion'] = '';
            $transformedPatient['patient_blood'] = '';
            $transformedPatient['patient_bloodRH'] = '';
      }

      $transformedMedication = [];

      if ($referral->medications) {
          $transformedMedication['drugcode'] = $referral->medications->drugcode ?? null;
          $transformedMedication['generic_name'] = $referral->medications->generic ?? null;
          $transformedMedication['instructions'] = $referral->medications->instruction ?? null;
      }else{
          $transformedMedication['drugcode'] = '';
          $transformedMedication['generic_name'] = '';
          $transformedMedication['instructions'] ='';
      }

      $transformedFacility_origin = [];
      if ($referral->facility_from) {
          $transformedFacility_origin['referral_hfhudcode'] = $referral->facility_from->hfhudcode ;
          $transformedFacility_origin['referral_facility_name'] = $referral->facility_from->facility_name;
          $transformedFacility_origin['referral_facility_type'] = ReferralHelper::getFacilityType($referral->facility_from->facility_type);
          $transformedFacility_origin['referral_address'] = $referral->facility_from->fhudaddress;
          $transformedFacility_origin['referral_region'] = ReferralHelper::getRegion($referral->facility_from->region_code);
          $transformedFacility_origin['referral_province'] = ReferralHelper::getProvince($referral->facility_from->province_code);
          $transformedFacility_origin['referral_city'] = ReferralHelper::getCity($referral->facility_from->city_code);
          $transformedFacility_origin['referral_barangay'] = ReferralHelper::getBarangay($referral->facility_from->bgycode);
          $transformedFacility_origin['referral_zipcode'] = $referral->facility_from->zip_code;
      }


      $transformedFacility_destination = [];
      if ($referral->facility_to) {
          $transformedFacility_destination['referring_hfhudcode'] = $referral->facility_to->hfhudcode ;
          $transformedFacility_destination['referring_facility_name'] = $referral->facility_to->facility_name;
          $transformedFacility_destination['referring_facility_type'] = ReferralHelper::getFacilityType($referral->facility_to->facility_type);
          $transformedFacility_destination['referring_address'] = $referral->facility_to->fhudaddress;
          $transformedFacility_destination['referring_region'] = ReferralHelper::getRegion($referral->facility_to->region_code);
          $transformedFacility_destination['referring_province'] = ReferralHelper::getProvince($referral->facility_to->province_code);
          $transformedFacility_destination['referring_city'] = ReferralHelper::getCity($referral->facility_to->city_code);
          $transformedFacility_destination['referring_barangay'] = ReferralHelper::getBarangay($referral->facility_to->bgycode);
          $transformedFacility_destination['referring_zipcode'] = $referral->facility_to->zip_code;
      }
      
     $transformedReferral = [
         'LogID' => $referral->LogID,
         'referral_origin' => $referral->fhudFrom,
         'referral_destination' => $referral->fhudTo,
         'referral_type_code' => ReferralHelper::getReferralTypebyCode($referral->typeOfReferral)['code'],
         'referral_type' => ReferralHelper::getReferralTypebyCode($referral->typeOfReferral)['description'],
         'referral_reason_code' => ReferralHelper::getReferralReasonbyCode($referral->referralReason)['code'],
         'referral_reason' => ReferralHelper::getReferralReasonbyCode($referral->referralReason)['description'],
         'referral_date' => date('m/d/Y',strtotime($referral->refferalDate)),
         'referral_time' => $referral->refferalTime,
         
         'referral_category' => $referral->referralCategory,
         'referring_provider' => $consulting ?? '' ,
         'referral_provider' => $referring ?? '',
         'medications' => $referral->medication,
         'special_instructions' => $referral->specialinstruct,
         'referral_contact_name'=>$referral->referralContactPerson,
         'referral_contact_number' => $referral->referringProviderContactNumber,
         'referral_contact_designation' => $referral->referralPersonDesignation,
         'patient_information' => $transformedPatient,
         'patient_demographics' => $transformedDemographics,
         'clinical' => $transformedClinical,
         'facility_origin' => $transformedFacility_origin,
         'facility_destination' => $transformedFacility_destination,
     ];

     // Return the transformed data in the expected format
     return response()->json($transformedReferral);
 }
/**
 * @OA\Get(
 *     path="/api/get-referral-list/{hfhudcode}/{emr_id}",
 *     summary="Get referral list by HFHUDCODE and EMR ID",
 *     tags={"Transactions"},
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="hfhudcode",
 *         in="path",
 *         description="HFH UDCODE of the facility",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="emr_id",
 *         in="path",
 *         description="EMR ID of the referral",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of referrals",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="LogID", type="integer", example=1),
 *                     @OA\Property(property="referral_origin_code", type="string", example="12345"),
 *                     @OA\Property(property="referral_origin_name", type="string", example="Facility A"),
 *                     @OA\Property(property="referral_destination_code", type="string", example="67890"),
 *                     @OA\Property(property="referral_destination_name", type="string", example="Facility B"),
 *                     @OA\Property(property="referral_reason", type="string", example="Consultation"),
 *                     @OA\Property(property="referral_date", type="string", format="date", example="05/04/2025"),
 *                     @OA\Property(property="referral_time", type="string", format="time", example="10:30 AM"),
 *                     @OA\Property(property="referral_category", type="string", example="Routine"),
 *                     @OA\Property(property="referring_provider", type="string", example="Dr. John Doe"),
 *                     @OA\Property(property="contact_number", type="string", example="09171234567"),
 *                     @OA\Property(property="emr", type="string", example="Facility A")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No referrals found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="No referrals found")
 *         )
 *     )
 * )
 */
public function get_referral_list(Request $request, $hfhudcode, $emr_id)
{
    if (!Auth::check()) {
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }

    if (empty($emr_id)) {
        return response()->json(['error' => 'Missing or invalid EMR ID'], 400);
    }

    $referrals = ReferralModel::with(['facility_from', 'facility_to', 'track'])
    ->whereHas('facility_to', function ($query) use ($emr_id, $hfhudcode) {
        $query->where('emr_id', $emr_id)
              ->where('fhudTo', $hfhudcode);
    })
    ->whereDoesntHave('track') // This excludes referrals with any related track
    ->get();

    if ($referrals->isEmpty()) {
        return response()->json(['error' => 'No referrals found/ facility not assigned to any emr'], 404);
    }

    $transformedList = $referrals->map(function ($referral) {
        $patient = ReferralPatientInfoModel::where('LogID', $referral->LogID)->first();
        
        $fullName = strtoupper($patient->patientFirstName) . ' ' .
        strtoupper($patient->patientMiddlename) . ' ' .
        strtoupper($patient->patientLastName) . ' ' .
        (($patient->patientSuffix === 'NOTAP') ? '' : strtoupper($patient->patientSuffix));
        
        return [    
            'LogID' => $referral->LogID,
            'referral_origin_code' => $referral->fhudFrom,
            'referral_origin_name' => optional(RefFacilityModel::where('hfhudcode', $referral->fhudFrom)->first())->facility_name,
            'referral_destination_code' => $referral->fhudTo,
            'referral_destination_name' => $referral->facility_name,
            'referral_reason' => $referral->referralReason,
            'referral_patient'=>$fullName, 
            'referral_patient_sex'=>strtoupper($patient->patientSex),
            'referral_patSex'=>($patient->patientSex=="M")? 'Male': 'Female' ,
            'referral_date' => date('m/d/Y', strtotime($referral->referralDate ?? $referral->refferalDate)),
            'referral_time' => date('h:i A', strtotime($referral->referralTime ?? $referral->refferalTime)),
            'referral_category' => $referral->referralCategory,
            'referral_contact_person' => $referral->referraContactPerson,
            'referral_contact_person_designation' => $referral->referraContactPersonDesignation,
            'referral_remarks'=>$referral->remarks,
            'referring_type' => $referral->typeOfReferral,
            'referring_provider' => $referral->referringProvider,
            'patient_pan'=>$referral->patientPan,
            'contact_number' => $referral->referringProviderContactNumber,
            'emr' => optional(RefFacilityModel::where('emr_id', $referral->emr_id)->first())->facility_name,
        ];
    });

    return response()->json($transformedList);
}



/**
 * @OA\Get(
 *     path="/api/reason-referral",
 *     operationId="getOAOptions",
 *     tags={"References"},
 *     summary="Get list of Reason for referral",
 *     description="Returns a list of predefined codes reason for referral",
 *      security={{ "sanctum": {} }},
 *     @OA\Response(
 *         response=200,
 *         description="List of Referral Reason",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="code", type="string", example="NOEQP"),
 *                 @OA\Property(property="description", type="string", example="No equipment available")
 *             )
 *         )
 *     )
 * )
 */
public function referral_reason()
{
    $referral_reason = ReferralHelper::getReferralReasons();
    return response()->json([
        'data'=>$referral_reason
    ]);
}

/**
 * @OA\Get(
 *     path="/api/reason-referral-code/{code}",
 *     operationId="getReferralReasonByCode",
 *     tags={"References"},
 *     summary="Get specific referral reason by code",
 *     description="Returns a specific referral reason based on the provided code",
 * security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="code",
 *         in="path",
 *         required=true,
 *         description="Referral reason code",
 *         @OA\Schema(type="string", example="NOEQP")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Referral reason details",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="code", type="string", example="NOEQP"),
 *             @OA\Property(property="description", type="string", example="No equipment available")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Referral reason not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Referral reason not found")
 *         )
 *     )
 * )
 */

public function referral_reason_by_code($code)
{
    $referral_reason = ReferralHelper::getReferralReasonbyCode($code);
    
    if (!$referral_reason) {
        return response()->json(['message' => 'Referral reason not found'], 404);
    }

    return response()->json([
        'data' => $referral_reason
    ]);
}


/**
 * @OA\Get(
 *     path="/api/referral-type",
 *     operationId="getReferralTypes",
 *     tags={"References"},
 *     summary="Get Referral Types",
 *     description="Returns a list of available referral types.",
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="string",
 *                     example="TRANS"
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function referral_type()
{
    $referral_type = ReferralHelper::getReferralType();

    return response()->json([
        'data' => $referral_type
    ]);
}

/**
 * @OA\Get(
 *     path="/api/referral-type-code/{code}",
 *     operationId="getReferralTypeByCode",
 *     tags={"References"},
 *     summary="Get Referral Type by Code",
 *     description="Returns details of a referral type by its code.",
 *     @OA\Parameter(
 *         name="code",
 *         in="path",
 *         required=true,
 *         description="Referral type code",
 *         @OA\Schema(
 *             type="string",
 *             example="TRANS"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 example={
 *                     "code": "TRANS",
 *                     "description": "Transfer"
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Referral type not found"
 *     )
 * )
 */
public function referral_type_code($code)
{
    $referral_type = ReferralHelper::getReferralTypebyCode($code);
    
    return response()->json([
        'data' => $referral_type
    ]);
}

/**
 * @OA\Post(
 *     path="/api/received",
 *     summary="Store received referral data",
 *     description="Receives referral tracking information and stores it.",
 *     operationId="storeReceivedReferral",
 *     tags={"Transactions"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"LogID", "received_date", "received_by"},
 *             @OA\Property(property="LogID", type="integer", example=123),
 *             @OA\Property(property="received_date", type="string", format="date-time", example="05/18/2025 14:30:00"),
 *             @OA\Property(property="received_by", type="string", example="Dr. Smith")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Data saved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Data saved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Invalid data")
 *         )
 *     )
 * )
 */
public function received(Request $request)
{
    if (empty($request->all())) {
        return response()->json(['error' => 'Invalid data'], 400);
    }

    $validated = $request->validate([
        'LogID' => 'required',
        'received_date' => 'required|date_format:m/d/Y H:i:s',
        'received_by' => 'required'
    ]);

        // Prevent duplicate insert
        $existing = ReferralTrackModel::find($validated['LogID']);
        if ($existing) {
            return response()->json(['message' => 'Referral already marked as received.'], 400);
        }
      // Insert new record
      ReferralTrackModel::create([
        'LogID' => $validated['LogID'],
        'receivedDate' => $validated['received_date'],
        'receivedPerson' => $validated['received_by'],
    ]);


    return response()->json(['message' => 'Referral successfully received'], 200);
}
    /**
     * @OA\Post(
     *     path="/api/admit",
     *     operationId="admitReferral",
     *     tags={"Transactions"},
     *     summary="Admit a referral",
     *     description="Updates a referral record with LogID, received date, and received by person.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="LogID of the referral to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"LogID", "received_date", "received_by"},
     *             @OA\Property(property="LogID", type="string", example="123456"),
     *             @OA\Property(property="admission_date", type="string", format="date-time", example="05/19/2025 14:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Referral updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Referral updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Referral not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Referral not found")
     *         )
     *     )
     * )
     */

    public function admit(Request $request)
    {
        if (!Auth::check()) {
            // If not authenticated, this will trigger the unauthenticated handler
            return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
        }
        if (empty($request->all())) {
            return response()->json(['error' => 'Invalid data'], 400);
        }
    
        // Validate the request data
        $validated = $request->validate([
            'LogID' => 'required',
            'admission_date' => 'required|date_format:m/d/Y H:i:s'
        ]);
    
        // Find the referral record
        $referral = ReferralTrackModel::find($request->LogID);
    
        if (!$referral) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    
        // Update the record with validated data
        $referral->LogID = $validated['LogID'];
        $referral->admDate = $validated['admission_date'];
        $referral->save();
    
        return response()->json([
            'message' => 'Patient admitted successfully'
        ], 200);
    }

    /**
 * @OA\Post(
 *     path="/api/discharge",
 *     operationId="dischargePatient",
 *     tags={"Transactions"},
 *     summary="Discharge a patient",
 *     description="Submits discharge information including medicine and follow-up schedule. Requires authentication.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"LogID", "admDate", "dischDate", "disposition", "condition", "hasFollowUp", "hasMedicine"},
 *             @OA\Property(property="LogID", type="string", example="HOSP-2071422083643"),
 *             @OA\Property(property="admDate", type="string", format="date-time", example="2022-02-02 13:03:13"),
 *             @OA\Property(property="dischDate", type="string", format="date-time", example="2022-02-03 13:03:13"),
 *             @OA\Property(property="disposition", type="string", example="DISCH"),
 *             @OA\Property(property="condition", type="string", example="IMPRO"),
 *             @OA\Property(property="diagnosis", type="string", example="Diagnosis not specified"),
 *             @OA\Property(property="remarks", type="string", example="REMARKS"),
 *             @OA\Property(property="disnotes", type="string", example="Discharge notes here."),
 *             @OA\Property(property="hasFollowUp", type="string", example="Y"),
 *             @OA\Property(property="hasMedicine", type="string", example="Y"),
 *             @OA\Property(
 *                 property="drugs",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"LogID", "generic", "instruction", "drugcode"},
 *                     @OA\Property(property="LogID", type="string", example="HOSP-2071422032507"),
 *                     @OA\Property(property="generic", type="string", example="AMOXICILLIN"),
 *                     @OA\Property(property="instruction", type="string", example="INUMIN ARAW ARAW"),
 *                     @OA\Property(property="drugcode", type="string", example="130182083180928321")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="schedule",
 *                 type="object",
 *                 @OA\Property(property="LogID", type="string", example="HOSP-2071422032507"),
 *                 @OA\Property(property="date", type="string", format="date-time", example="2022-02-04 13:03:13")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Discharge successful",
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "status": "success",
 *                 "message": "Patient discharged successfully",
 *                 "data": {
 *                     "LogID": "HOSP-2071422083643",
 *                     "dischargeSummary": "Details..."
 *                 }
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data",
 *         @OA\JsonContent(
 *             type="object",
 *             example={"error": "Invalid data"}
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             type="object",
 *             example={"message": "Unauthenticated."}
 *         )
 *     )
 * )
 */

 public function discharge(Request $request)
 {
     if (!Auth::check()) {
         return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
     }
 
     $validated = $request->validate([
         'LogID' => 'required|exists:referral_track,LogID',
         'admDate' => 'required|date',
         'dischDate' => 'required|date|after_or_equal:admDate',
         'disposition' => 'required|string',
         'condition' => 'required|string',
         'diagnosis' => 'nullable|string',
         'remarks' => 'nullable|string',
         'disnotes' => 'nullable|string',
         'hasFollowUp' => 'required|boolean',
         'hasMedicine' => 'required|boolean',
         'schedule.date' => 'nullable|date',
         'schedule.LogID' => 'nullable|string',
         'drugs' => 'nullable|array'
     ]);
 

     $discharge = [
         'LogID'        => $validated['LogID'],
         'admDate'      => date("Y-m-d H:i:s", strtotime($validated['admDate'])),
         'dischDate'    => date("Y-m-d H:i:s", strtotime($validated['dischDate'])),
         'dischDisp'    => $validated['disposition'],
         'dischCond'    => $validated['condition'],
         'diagnosis'    => $validated['diagnosis'] ?? null,
         'trackRemarks' => $validated['remarks'] ?? null,
         'disnotes'     => $validated['disnotes'] ?? null,
         'hasFollowUp'  => $validated['hasFollowUp'],
         'hasMedicine'  => $validated['hasMedicine'],
     ];
 
     $folUp = [  
         'LogID' => $validated['schedule']['LogID'] ?? null,
         'scheduleDateTime' => isset($validated['schedule']['date'])
             ? date("Y-m-d H:i:s", strtotime($validated['schedule']['date']))
             : null,
     ];
 
     $param = [
         'LogID'     => $validated['LogID'],
         'discharge' => $discharge,
         'medicine'  => $validated['drugs'] ?? [],
         'followup'  => $folUp,
     ];
 
     $referral = ReferralTrackModel::find($validated['LogID']);

     if ($referral) {
         $referral->dischDate = $discharge['dischDate'];
         $referral->dischDisp = $discharge['disposition'];
         $referral->dischCond = $discharge['condition'];
         $referral->save();
     }
    
     return response()->json([
        'message' => 'Patient discharged successfully'
    ], 200);

 }
 
 

/**
 * @OA\Get(
 *     path="/api/discharged-data/{logID}",
 *     operationId="getDischargedData",
 *     tags={"Transactions"},
 *     summary="Get discharged patient data",
 *     description="Retrieves discharged patient data by the provided log ID. Requires authentication.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="logID",
 *         in="path",
 *         required=true,
 *         description="The log ID of the patient",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "patient_name": "John Doe",
 *                 "discharge_date": "2025-05-10",
 *                 "diagnosis": "Acute appendicitis"
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data",
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "error": "Invalid data"
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             type="object",
 *             example={
 *                 "message": "Unauthenticated."
 *             }
 *         )
 *     )
 * )
 */

    public function get_discharged_data(Request $request,$logID)
    {

        if (!Auth::check()) {
            // If not authenticated, this will trigger the unauthenticated handler
            return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
        }
  
        if (empty($logID)) {
            return response()->json(['error' => 'Invalid data'], 400);
        }
    
        $output = $this->referralService->getDischargeInformation($logID);
        return response()->json($output);
    }
  

  
    
   

    
}
