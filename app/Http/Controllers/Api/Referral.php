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
        $jsonString = $request->getContent(); 
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'error' => 'JSON Decode Error: ' . json_last_error_msg()
            ], 400);
        }

        $rawData = $data;
        $validatedData = $request->validated();
        $mergedData = array_merge($rawData, $validatedData);
        $check_from =  RefFacilityModel::select('emr_id')->where('hfhudcode', $mergedData['referral']['facility_from'])->first()->facility_name;
        if($check_from)
        {
            return response()->json([
                'error' =>'Referring facility not registered to any emr provider!'
            ], 400);
        }
        $check_to =  RefFacilityModel::select('emr_id')->where('hfhudcode', $mergedData['referral']['facility_to'])->first()->facility_name;

        if($check_to)
        {
            return response()->json([
                'error' =>'Referral facility not registered to any emr provider!'
            ], 400);
        }

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
 *     path="api/region/{id}",
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
 *     path="api/barangay/{id}",
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
 * @OA\Get(
 *     path="/api/facility/{id}",
 *     tags={"References"},
 *     summary="Get facility by ID",
 *     security={{ "sanctum": {} }},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Facility HFH UDCODE",
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
        // If not authenticated, this will trigger the unauthenticated handler
        return $this->unauthenticated($request, new \Illuminate\Auth\AuthenticationException);
    }
     $referral = ReferralModel::with([
         'patientinformation',
         'medication',
         'demographics',
         'clinical'
     ])->where('LogID', $id)->first();
 
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
         $transformedDemographics['barangay'] = $referral->demographics->patientBrgyCode ?? null;
         $transformedDemographics['city'] = $referral->demographics->patientMundCode ?? null;
         $transformedDemographics['province'] = $referral->demographics->patientProvCode ?? null;
         $transformedDemographics['region'] = $referral->demographics->patientRegCode ?? null;
         $transformedDemographics['zipcode'] = $referral->demographics->patientZipCode ?? null;
     }
 
  
     $transformedClinical = [];
     if ($referral->clinical) {
         $transformedClinical['diagnosis'] = $referral->clinical->clinicalDiagnosis ?? null;
         $transformedClinical['history'] = $referral->clinical->clinicalHistory ?? null;
         $transformedClinical['chief_complaint'] = $referral->clinical->chiefComplaint ?? null;
         $transformedClinical['vitals'] = $referral->clinical->vitals ?? null;
     }

      $transformedPatient = [];
      if ($referral->patientinformation) {
          $transformedPatient['patient_lastname'] = $referral->patientinformation->patientLastName ?? null;
          $transformedPatient['patient_firstname'] = $referral->patientinformation->patientFirstName ?? null;
          $transformedPatient['patient_middlename'] = $referral->patientinformation->patientMiddleName ?? null;
          $transformedPatient['patient_birthdate'] = $referral->patientinformation->patientBirthDate ?? null;
          $transformedPatient['patient_sex'] = $referral->patientinformation->patientSex ?? null;
          $transformedPatient['patient_civilstatus'] = $referral->patientinformation->patientCivilStatus ?? null;
          $transformedPatient['patient_contact'] = $referral->patientinformation->patientContactNumber ?? null;
          $transformedPatient['patient_religion'] = $referral->patientinformation->patientReligion ?? null;
          $transformedPatient['patient_blood'] = $referral->patientinformation->patientBloodType ?? null;
          $transformedPatient['patient_bloodRH'] = $referral->patientinformation->patientBloodRH ?? null;
      }

     /*  $transformedMedication = [];
      if ($referral->medications) {
          $transformedMedication['drugcode'] = $referral->medications->drugcode ?? null;
          $transformedMedication['generic_name'] = $referral->medications->generic ?? null;
          $transformedMedication['instructions'] = $referral->medications->instruction ?? null;
      } */
 
     $transformedReferral = [
         'LogID' => $referral->LogID,
         'referral_origin' => $referral->fhudFrom,
         'referral_destination' => $referral->fhudTo,
         'referral_reason' => $referral->referralReason,
         'referral_date' => $referral->refferalDate,
         'referral_time' => $referral->refferalTime,
         'referral_category' => $referral->referralCategory,
         'referring_provider' => $referral->referringProvider,
         'medications' => $referral->medication,
         'special_instructions' => $referral->specialinstruct,
         'contact_number' => $referral->referringProviderContactNumber,
         'patient_information' => $transformedPatient   ,
         'demographics' => $transformedDemographics,
         'clinical' => $transformedClinical
     ];

     // Return the transformed data in the expected format
     return response()->json([
         'data' => [
            'referral' => $transformedReferral,
         ]
     ]);
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

    $referrals = ReferralModel::with(['facility_from', 'facility_to'])
        ->whereHas('facility_to', function ($query) use ($emr_id, $hfhudcode) {
            $query->where('emr_id', $emr_id)
                  ->where('fhudTo', $hfhudcode);
        })
        ->get();

    if ($referrals->isEmpty()) {
        return response()->json(['error' => 'No referrals found'], 404);
    }

    $transformedList = $referrals->map(function ($referral) {
        return [
            'LogID' => $referral->LogID,
            'referral_origin_code' => $referral->fhudFrom,
            'referral_origin_name' => optional($referral->facility_from)->facility_name,
            'referral_destination_code' => $referral->fhudTo,
            'referral_destination_name' => optional($referral->facility_to)->facility_name,
            'referral_reason' => $referral->referralReason,
            'referral_date' => date('m/d/Y', strtotime($referral->referralDate ?? $referral->refferalDate)),
            'referral_time' => date('h:i A', strtotime($referral->referralTime ?? $referral->refferalTime)),
            'referral_category' => $referral->referralCategory,
            'referring_provider' => $referral->referringProvider,
            'contact_number' => $referral->referringProviderContactNumber,
            'emr' => optional(RefFacilityModel::where('emr_id', $referral->emr_id)->first())->facility_name,
        ];
    });

    return response()->json([
        'data' => $transformedList
    ]);
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

        ReferralTrackModel::create($validated);

        return response()->json(['message' => 'Data saved successfully'], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/admit/{id}",
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
     *             @OA\Property(property="received_date", type="string", format="date-time", example="05/19/2025 14:30:00"),
     *             @OA\Property(property="received_by", type="string", example="Dr. John Doe")
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

    public function admit(Request $request, $id)
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
            'received_date' => 'required|date_format:m/d/Y H:i:s',
            'received_by' => 'required'
        ]);
    
        // Find the referral record
        $referral = ReferralTrackModel::find($id);
    
        if (!$referral) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    
        // Update the record with validated data
        $referral->LogID = $validated['LogID'];
        $referral->receivedDate = $validated['received_date'];
        $referral->receivedPerson = $validated['received_by'];
        $referral->save();
    
        return response()->json([
            'message' => 'Referral updated successfully'
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
 
     $data = $request->all();
 
     if (empty($data['LogID'])) {
         return response()->json(['error' => 'Invalid data'], 400);
     }
 
     $discharge = [
         'LogID'       => $data['LogID'],
         'admDate'     => date("Y-m-d H:i:s", strtotime($data['admDate'])),
         'dischDate'   => date("Y-m-d H:i:s", strtotime($data['dischDate'])),
         'dischDisp'   => $data['disposition'],
         'dischCond'   => $data['condition'],
         'diagnosis'   => $data['diagnosis'] ?? null,
         'trackRemarks'=> $data['remarks'] ?? null,
         'disnotes'    => $data['disnotes'] ?? null,
         'hasFollowUp' => $data['hasFollowUp'],
         'hasMedicine' => $data['hasMedicine'],
     ];
 
     $folUp = [
         'LogID' => $data['schedule']['LogID'] ?? null,
         'scheduleDateTime' => isset($data['schedule']['date']) 
             ? date("Y-m-d H:i:s", strtotime($data['schedule']['date'])) 
             : null,
     ];
 
     $param = [
         'LogID'     => $data['LogID'],
         'discharge' => $discharge,
         'medicine'  => $data['drugs'] ?? [],
         'followup'  => $folUp,
     ];
 
     $output = $this->referralService->getDischargeInformation($param);
 
     return response()->json($output);
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
