<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\ReferralService;
use App\Http\Controllers\Controller;

use App\Http\Requests\PatientReferralRequest;

/**
 * @OA\Info(title="API Documentation", version="1.0")
 * @OA\Tag(name="Referral", description="Referral Operations")
 */
class ReferralREST extends Controller
{
    /**
     * Generate a reference code.
     *
     * @OA\Get(
     *     path="/api/generate_code/{fhudcode}",
     *     tags={"Referral"},
     *     summary="Generate reference code",
     *     @OA\Parameter(
     *         name="fhudcode",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reference", type="string", example="GeneratedCode123")
     *         )
     *     )
     * )
     */
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function generate_reference($fhudcode)
    {
        $code = $this->referralService->generate_code($fhudcode);
        if ($code) {
            return response()->json(['code' => $code]);
        }
        return response()->json(['message' => 'Facility not found'], 404);
    }

   /**
 * Refer a patient.
 *
 * @OA\Post(
 *     path="/api/patient_referral",
 *     tags={"Referral"},
 *     summary="Refer a patient",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="fhudFrom", type="string", example="DOH000000000004418"),
 *             @OA\Property(property="fhudTo", type="string", example="DOH000000000037966"),
 *             @OA\Property(property="typeOfReferral", type="string", example="TRANS"),
 *             @OA\Property(property="referralReason", type="string", example="NOPRO"),
 *             @OA\Property(property="otherReasons", type="string", example="OTHER REASON"),
 *             @OA\Property(property="remarks", type="string", example="REMARKS"),
 *             @OA\Property(property="referralContactPerson", type="string", example="RECEIVING PERSON"),
 *             @OA\Property(property="referralPersonDesignation", type="string", example="RECEIVING PERSON DESIGNATION"),
 *             @OA\Property(property="rprhreferral", type="string", example="Y"),
 *             @OA\Property(property="rprhreferralmethod", type="string", example="ELECTRONIC"),
 *             @OA\Property(property="status", type="string", example="PENDING"),
 *             @OA\Property(property="referralDate", type="string", format="date", example="12-12-2012"),
 *             @OA\Property(property="referralTime", type="string", example="12:00AM"),
 *             @OA\Property(property="familyNumber", type="string", example="123456"),
 *             @OA\Property(property="phicNumber", type="string", example="PHIC123456789"),
 *             @OA\Property(property="caseNumber", type="string", example="CASE123456"),
 *             
 *             @OA\Property(property="patientLastName", type="string", example="ABUEL"),
 *             @OA\Property(property="patientFirstName", type="string", example="JACOB"),
 *             @OA\Property(property="patientSuffix", type="string", example="JR"),
 *             @OA\Property(property="patientMiddlename", type="string", example="PADRE"),
 *             @OA\Property(property="patientBirthDate", type="string", format="date", example="07-11-2018"),
 *             @OA\Property(property="patientSex", type="string", example="M"),
 *             @OA\Property(property="patientCivilStatus", type="string", example="SINGLE"),
 *             @OA\Property(property="patientReligion", type="string", example="CATHO"),
 *             @OA\Property(property="patientBloodType", type="string", example="O"),
 *             @OA\Property(property="patientBloodTypeRH", type="string", example="+"),
 *             @OA\Property(property="patientStreetAddress", type="string", example="123 Main St."),
 *             @OA\Property(property="patientBrgyAddress", type="string", example="043420011"),
 *             @OA\Property(property="patientMunAddress", type="string", example="043420"),
 *             @OA\Property(property="patientProvAddress", type="string", example="0434"),
 *             @OA\Property(property="patientRegAddress", type="string", example="04"),
 *             @OA\Property(property="patientZipAddress", type="string", example="4017"),
 *             
 *             @OA\Property(property="clinicalDiagnosis", type="string", example="ER ADMISSION DIAGNOSIS"),
 *             @OA\Property(property="clinicalHistory", type="string", example="HISTORY OF PRESENT ILLNESS"),
 *             
 *             @OA\Property(
 *                 property="vitalSign",
 *                 type="object",
 *                 @OA\Property(property="BP", type="string", example="120/80 mmHg"),
 *                 @OA\Property(property="Temp", type="string", example="36 °C"),
 *                 @OA\Property(property="HR", type="string", example="89/min"),
 *                 @OA\Property(property="RR", type="string", example="89/min"),
 *                 @OA\Property(property="O2Sats", type="string", example="98%"),
 *                 @OA\Property(property="Weight", type="string", example="70 kg"),
 *                 @OA\Property(property="Height", type="string", example="170 cm")
 *             ),
 *             
 *             @OA\Property(property="physicalExamination", type="string", example="Physical examination details"),
 *             @OA\Property(property="chiefComplaint", type="string", example="CHIEF COMPLAINT"),
 *             @OA\Property(property="findings", type="string", example="final diagnosis"),
 *             
 *             @OA\Property(
 *                 property="patientProvider",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="ProviderLast", type="string", example="ACELAJADO"),
 *                     @OA\Property(property="ProviderFirst", type="string", example="GODFREY"),
 *                     @OA\Property(property="ProviderMiddle", type="string", example="ACERO"),
 *                     @OA\Property(property="ProviderSuffix", type="string", example="MD"),
 *                     @OA\Property(property="ProviderContactNo", type="string", example="12345678910"),
 *                     @OA\Property(property="ProviderType", type="string", example="REFER")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="code", type="string", example="ReferralCode123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Facility not found"
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
}

    
}
