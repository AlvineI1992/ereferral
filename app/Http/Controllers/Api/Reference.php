<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\ReferralService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\RefRegionModel;
use App\Models\RefProvinceModel;
use App\Models\RefCityModel;
use App\Models\RefBarangayModel;
use App\Helpers\ReferralHelper;

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
class References extends Controller
{
    

    public function __construct()
    {
       
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


    
}
