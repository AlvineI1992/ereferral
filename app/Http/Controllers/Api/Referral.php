<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\ReferralService;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientReferralRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\RefRegionModel;

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
     * )ap
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
     *     path="/api/referral",
     *     tags={"Referral"},
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

        $output = $this->referralService->refer_patient($mergedData);

        return response()->json([
            'message' => 'Patient referred successfully!',
            'data' => $output
        ]);
        return response()->json(['error' => 'Unauthorized'], 401);
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
 *         description="Facility not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Facility not found")
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
public function demographic_reference()
{
    // Check if the user is authenticated (using Sanctum)
    if (!auth()->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
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

    // Return the formatted data in JSON format
    return response()->json([
        'regions' => $result
    ]);
}
}
