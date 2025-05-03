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
     *             required={"patient_id", "facility_code", "reason"},
     *             @OA\Property(property="patient_id", type="string", example="12345"),
     *             @OA\Property(property="facility_code", type="string", example="FHU123"),
     *             @OA\Property(property="reason", type="string", example="Referral due to medical condition")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient referred successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Patient referred successfully!"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid data format")
     *         )
     *     )
     * )
     */
    public function patient_referral(PatientReferralRequest $request)
    {
        // Check if the request contains valid JSON
        $jsonString = $request->getContent(); 
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'error' => 'JSON Decode Error: ' . json_last_error_msg()
            ], 400);
        }

        // Merge validated data with raw input
        $rawData = $data;
        $validatedData = $request->validated();
        $mergedData = array_merge($rawData, $validatedData);

        // Refer the patient using the service
        $output = $this->referralService->refer_patient($mergedData);

        return response()->json([
            'message' => 'Patient referred successfully!',
            'data' => $output
        ]);
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
        return response()->json(['error' => 'Barangay not found'], 404);
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
 *     path="/api/get-referral-list/{hfhudcode}",
 *     summary="Get referral list by HFHUDCODE",
 *     tags={"Transactions"},
 *     @OA\Parameter(
 *         name="hfhudcode",
 *         in="path",
 *         description="HFH UDCODE of the facility",
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
 *                     @OA\Property(property="id", type="integer", example=1),
 *                   
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function get_referral_list($hfhudcode,$emr_id)
{
    $referrals = ReferralModel::with(['facility_from', 'facility_to'])
        ->where('fhudTo', $hfhudcode)
        ->where('emr_id', $emr_id)
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
            'referral_date' => date('m/d/Y',strtotime($referral->refferalDate)),
            'referral_time' => date('h:i A',strtotime($referral->refferalTime)),
            'referral_category' => $referral->referralCategory,
            'referring_provider' => $referral->referringProvider,
            'contact_number' => $referral->referringProviderContactNumber,
        ];
    });

    return response()->json([
        'data' => $transformedList
    ]);
}

 
}
