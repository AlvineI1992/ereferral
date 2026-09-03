<?php

namespace App\Http\Controllers;

use App\Helpers\ReferralHelper;
use App\Models\ReferralInformationModel;
use App\Services\ReferralAttachmentService;
use App\Services\ReferralService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReferralController extends Controller
{
    protected $referralService;

    protected $referralAttachmentService;

    public function __construct(ReferralService $referralService, ReferralAttachmentService $referralAttachmentService)
    {
        $this->referralService = $referralService;
        $this->referralAttachmentService = $referralAttachmentService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'origin' => ['nullable', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(collect(ReferralHelper::getReferralType())->pluck('code')->all())],
            'category' => ['nullable', Rule::in(['ER', 'OPD'])],
            'reason' => ['nullable', Rule::in(collect(ReferralHelper::getReferralReasons())->pluck('code')->all())],
        ]);

        if (filled($filters['date_from'] ?? null) && filled($filters['date_to'] ?? null) && $filters['date_from'] > $filters['date_to']) {
            throw ValidationException::withMessages([
                'date_to' => 'Date to must be on or after date from.',
            ]);
        }

        $perPage = max(1, (int) $request->input('per_page', 5));
        $page = max(1, (int) $request->input('page', 1));

        $query = ReferralInformationModel::with(['patientinformation', 'facility_from', 'facility_to'])
            ->whereDoesntHave('track'); // This ensures you get referrals without a track

        $this->applyIncomingScope($query, $user);

        $summaryQuery = clone $query;
        $filterOptionsQuery = clone $query;

        $filterOptions = [
            'origins' => (clone $filterOptionsQuery)
                ->setEagerLoads([])
                ->whereNotNull('fhudFrom')
                ->select('fhudFrom')
                ->distinct()
                ->with('facility_from:hfhudcode,facility_name')
                ->get()
                ->map(fn ($referral) => [
                    'code' => (string) $referral->fhudFrom,
                    'name' => (string) ($referral->facility_from?->facility_name ?? $referral->fhudFrom),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'destinations' => (clone $filterOptionsQuery)
                ->setEagerLoads([])
                ->whereNotNull('fhudTo')
                ->select('fhudTo')
                ->distinct()
                ->with('facility_to:hfhudcode,facility_name')
                ->get()
                ->map(fn ($referral) => [
                    'code' => (string) $referral->fhudTo,
                    'name' => (string) ($referral->facility_to?->facility_name ?? $referral->fhudTo),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'types' => ReferralHelper::getReferralType(),
            'reasons' => ReferralHelper::getReferralReasons(),
        ];

        $summary = [
            'totalIncoming' => (clone $summaryQuery)->count(),
            'todayIncoming' => (clone $summaryQuery)->whereDate('refferalDate', now()->toDateString())->count(),
            'emergencyCount' => (clone $summaryQuery)->where('referralCategory', 'ER')->count(),
            'outpatientCount' => (clone $summaryQuery)->where('referralCategory', 'OPD')->count(),
            'receivingFacilities' => (clone $summaryQuery)->distinct('fhudTo')->count('fhudTo'),
            'topReasons' => (clone $summaryQuery)
                ->selectRaw('referralReason, COUNT(*) as aggregate')
                ->groupBy('referralReason')
                ->orderByDesc('aggregate')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $reason = ReferralHelper::getReferralReasonbyCode($item->referralReason);

                    return [
                        'code' => $item->referralReason,
                        'label' => $reason['description'] ?? ($item->referralReason === 'OTHER' ? 'Other reason' : $item->referralReason),
                        'count' => (int) $item->aggregate,
                    ];
                })
                ->values(),
            'topProvinces' => $this->buildLocationSummary(
                (clone $summaryQuery)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_province as province', 'demo.patientProvCode', '=', 'province.provcode'),
                'demo.patientProvCode',
                'province.provname'
            ),
            'topCities' => $this->buildLocationSummary(
                (clone $summaryQuery)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_city as city', 'demo.patientMundCode', '=', 'city.citycode'),
                'demo.patientMundCode',
                'city.cityname'
            ),
            'topBarangays' => $this->buildLocationSummary(
                (clone $summaryQuery)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_barangay as barangay', 'demo.patientBrgyCode', '=', 'barangay.bgycode'),
                'demo.patientBrgyCode',
                'barangay.bgyname'
            ),
            'generatedAt' => now()->toIso8601String(),
        ];

        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('refferalDate', '>=', $filters['date_from']);
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('refferalDate', '<=', $filters['date_to']);
        }

        if (filled($filters['origin'] ?? null)) {
            $query->where('fhudFrom', $filters['origin']);
        }

        if (filled($filters['destination'] ?? null)) {
            $query->where('fhudTo', $filters['destination']);
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('typeOfReferral', $filters['type']);
        }

        if (filled($filters['category'] ?? null)) {
            $query->where('referralCategory', $filters['category']);
        }

        if (filled($filters['reason'] ?? null)) {
            $query->where('referralReason', $filters['reason']);
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
        $rowOffset = ($paginated->currentPage() - 1) * $paginated->perPage();

        // Transform the data for response
        $transformedList = $paginated->getCollection()->values()->map(function ($referral, $index) use ($rowOffset) {
            $referral_reason_desc = ReferralHelper::getReferralReasonbyCode($referral->referralReason);
            $referral_type_desc = ReferralHelper::getReferralTypebyCode($referral->typeOfReferral);
            $patientInformation = $referral->patientinformation;
            $patientName = trim(implode(' ', array_filter([
                $patientInformation?->patientFirstName,
                $patientInformation?->patientMiddlename,
                $patientInformation?->patientLastname,
            ])));

            return [
                'index' => $rowOffset + $index + 1,
                'LogID' => $referral->LogID,
                'patient_name' => $patientName,
                'patient_sex' => $patientInformation?->patientSex === 'M'
                    ? 'Male'
                    : ($patientInformation?->patientSex === 'F' ? 'Female' : 'Unknown'),
                'patient_birthdate' => $patientInformation?->patientBirthDate,
                'patient_civilstatus' => ReferralHelper::getCivilStatusDescription($patientInformation?->patientCivilStatus)
                    ?? $patientInformation?->patientCivilStatus,
                'referral_origin_code' => $referral->fhudFrom,
                'referral_origin_name' => optional($referral->facility_from)->facility_name,
                'referral_destination_code' => $referral->fhudTo,
                'referral_destination_name' => optional($referral->facility_to)->facility_name,
                'referral_reason_code' => $referral->referralReason,
                'referral_reason_description' => ($referral_reason_desc) ? $referral_reason_desc['description'] : $referral->otherReasons,
                'referral_type_code' => $referral->typeOfReferral,
                'referral_type_description' => ($referral_type_desc) ? $referral_type_desc['description'] : $referral->otherReasons,
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
            'summary' => $summary,
            'filter_options' => $filterOptions,
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
        $validated = $request->validate($this->referralValidationRules(), $this->referralValidationMessages());

        $result = $this->referralService->refer_patient($this->buildReferralPayload($validated));
        $resultCode = is_array($result) ? (string) ($result['code'] ?? '') : '';
        $resultMessage = is_array($result) ? (string) ($result['message'] ?? '') : '';
        $isDuplicate = str_contains(strtolower($resultMessage), 'already submitted');
        $isErrorCode = $resultCode === ''
            || (ctype_digit($resultCode) && (int) $resultCode >= 400);

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'form' => $resultMessage !== '' ? $resultMessage : 'Referral already submitted.',
            ]);
        }

        if (! $isErrorCode) {
            $attachments = $request->file('attachments', []);

            if ($attachments !== []) {
                $this->referralAttachmentService->store(
                    $resultCode,
                    is_array($attachments) ? $attachments : [$attachments],
                    $request->user()?->id
                );
            }

            return redirect('/incoming', 303);
        }

        throw ValidationException::withMessages([
            'form' => is_array($result)
                ? ($resultMessage !== '' ? $resultMessage : 'Unable to create the referral right now.')
                : 'Unable to create the referral right now.',
        ]);
    }

    // Display the specified resource
    public function show($LogID)
    {
        $decodedID = base64_decode($LogID);

        $query = ReferralInformationModel::with(['patientinformation', 'facility_from', 'facility_to', 'attachments'])
            ->where('LogID', $decodedID);

        $this->applyIncomingScope($query, auth()->user());

        $referral = $query
            ->first();

        if (! $referral) {
            return response()->json(['message' => 'Referral not found.'], 404);
        }

        $reason = ReferralHelper::getReferralReasonbyCode($referral->referralReason);
        $type = ReferralHelper::getReferralTypebyCode($referral->typeOfReferral);

        $data = [
            'patient' => $referral->patientinformation,
            'origin' => $referral->facility_from,
            'destination' => $referral->facility_to,
            'attachments' => $referral->attachments
                ->map(fn ($attachment) => $this->referralAttachmentService->metadata($attachment))
                ->values(),
            'referral_info' => [
                'LogID' => $referral->LogID,
                'date' => $referral->refferalDate,
                'category' => $referral->referralCategory,
                'reason' => $reason['description'] ?? $referral->otherReasons,
                'type' => $type['description'] ?? $referral->typeOfReferral,
            ],
        ];

        return response()->json($data);
    }

    // Show the form for editing the specified resource
    public function edit($LogID)
    {
        $decodedID = $this->decodeLogId($LogID);

        $query = ReferralInformationModel::with(['patientinformation', 'demographics', 'clinical'])
            ->where('LogID', $decodedID);

        $this->applyIncomingScope($query, auth()->user());

        $referral = $query->first();

        if (! $referral) {
            return response()->json(['message' => 'Referral not found.'], 404);
        }

        $provider = DB::table('referral_provider')
            ->where('LogID', $decodedID)
            ->where('provider_type', 'REFER')
            ->first();

        $clinical = $referral->clinical;
        $vitals = json_decode((string) ($clinical?->vitals ?? '{}'), true) ?: [];

        return response()->json([
            'data' => [
                'profilePic' => null,
                'patientFirstName' => (string) ($referral->patientinformation?->patientFirstName ?? ''),
                'patientMiddleName' => (string) ($referral->patientinformation?->patientMiddlename ?? ''),
                'patientLastName' => (string) ($referral->patientinformation?->patientLastName ?? ''),
                'patientSuffix' => $this->emptyDotValue($referral->patientinformation?->patientSuffix),
                'patientBirthDate' => $this->formatDate($referral->patientinformation?->patientBirthDate),
                'patientSex' => (string) ($referral->patientinformation?->patientSex ?? ''),
                'patientCivilStatus' => (string) ($referral->patientinformation?->patientCivilStatus ?? ''),
                'patientContactNumber' => (string) ($referral->patientinformation?->patientContactNumber ?? ''),
                'familyNumber' => $this->emptyZeroValue($referral->patientinformation?->FamilyID),
                'caseNumber' => $this->emptyZeroValue($referral->patientinformation?->caseNum),
                'phicNumber' => $this->emptyZeroValue($referral->patientinformation?->phicNum),
                'religion' => (string) ($referral->patientinformation?->patientReligion ?? ''),
                'bloodType' => (string) ($referral->patientinformation?->patientBloodType ?? ''),
                'bloodRh' => (string) ($referral->patientinformation?->patientBloodTypeRH ?? ''),
                'patientStreetAddress' => (string) ($referral->demographics?->patientStreetAddress ?? ''),
                'region' => (string) ($referral->demographics?->patientRegCode ?? ''),
                'province' => (string) ($referral->demographics?->patientProvCode ?? ''),
                'city' => (string) ($referral->demographics?->patientMundCode ?? ''),
                'barangay' => (string) ($referral->demographics?->patientBrgyCode ?? ''),
                'zipcode' => (string) ($referral->demographics?->patientZipCode ?? ''),
                'calledDate' => $this->formatDateTimeLocal($referral->calledDate),
                'refferalDate' => $this->formatDateTimeLocal($this->combineReferralDateTime($referral->refferalDate, $referral->refferalTime)),
                'referringFacility' => (string) ($referral->fhudFrom ?? ''),
                'referralFacility' => (string) ($referral->fhudTo ?? ''),
                'transactionCode' => (string) $referral->LogID,
                'typeOfReferral' => (string) ($referral->typeOfReferral ?? ''),
                'referralCategory' => (string) ($referral->referralCategory ?? ''),
                'referralReason' => (string) ($referral->referralReason ?? ''),
                'otherReferralReason' => (string) ($referral->otherReasons ?? ''),
                'contactPerson' => (string) ($referral->referralContactPerson ?? ''),
                'contactDesignation' => $this->emptyNaValue($referral->referralContactPersonDesignation),
                'referralContactNumber' => (string) ($referral->referringProviderContactNumber ?? ''),
                'referralRemarks' => (string) ($referral->remarks ?? ''),
                'diagnosis' => (string) ($clinical?->clinicalDiagnosis ?? ''),
                'chiefComplaint' => (string) ($clinical?->chiefComplaint ?? ''),
                'clinicalHistory' => (string) ($clinical?->clinicalHistory ?? ''),
                'findings' => (string) ($clinical?->findings ?? ''),
                'providerFirstName' => (string) ($provider->provider_first ?? ''),
                'providerMiddleName' => (string) ($provider->provider_middle ?? ''),
                'providerLastName' => (string) ($provider->provider_last ?? ''),
                'providerSuffix' => $this->emptyNaValue($provider->provider_suffix ?? null),
                'bp' => (string) ($vitals['BP'] ?? ''),
                'temp' => (string) ($vitals['temp'] ?? ''),
                'hr' => (string) ($vitals['HR'] ?? ''),
                'rr' => (string) ($vitals['RR'] ?? ''),
                'o2Sats' => (string) ($vitals['O2_sats'] ?? ''),
                'weight' => (string) ($vitals['weight'] ?? ''),
                'height' => (string) ($vitals['height'] ?? ''),
            ],
        ]);
    }

    // Update the specified resource in storage
    public function update(Request $request, $LogID)
    {
        $validated = $request->validate($this->referralValidationRules(), $this->referralValidationMessages());
        $decodedID = $this->decodeLogId($LogID);

        $query = ReferralInformationModel::query()->where('LogID', $decodedID);
        $this->applyIncomingScope($query, auth()->user());

        if (! $query->exists()) {
            throw ValidationException::withMessages([
                'form' => 'Referral not found or inaccessible.',
            ]);
        }

        $result = $this->referralService->updateReferral($decodedID, $this->buildReferralPayload($validated));
        $resultCode = is_array($result) ? (string) ($result['code'] ?? '') : '';
        $resultMessage = is_array($result) ? (string) ($result['message'] ?? '') : '';
        $isDuplicate = str_contains(strtolower($resultMessage), 'already submitted');
        $isErrorCode = $resultCode === ''
            || (ctype_digit($resultCode) && (int) $resultCode >= 400);

        if ($isDuplicate || $isErrorCode) {
            throw ValidationException::withMessages([
                'form' => $resultMessage !== '' ? $resultMessage : 'Unable to update the referral right now.',
            ]);
        }

        $attachments = $request->file('attachments', []);

        if ($attachments !== []) {
            $this->referralAttachmentService->store(
                $decodedID,
                is_array($attachments) ? $attachments : [$attachments],
                $request->user()?->id
            );
        }

        return redirect('/incoming/profile/'.base64_encode($decodedID), 303);
    }

    // Remove the specified resource from storage
    public function destroy(Request $request, string $LogID)
    {
        $decodedID = $this->decodeLogId($LogID);
        $query = ReferralInformationModel::query()->where('LogID', $decodedID);
        $this->applyIncomingScope($query, $request->user());

        if (! $query->exists()) {
            return response()->json([
                'message' => 'Referral not found or inaccessible.',
            ], 404);
        }

        $this->referralService->deleteReferralTransaction($decodedID);

        Log::notice('Incoming referral transaction deleted.', [
            'log_id' => $decodedID,
            'deleted_by' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Referral transaction {$decodedID} was deleted successfully.",
        ]);
    }

    public function generate_hfhudcode(Request $request, ?string $hfhudcode = null)
    {
        try {
            $resolvedCode = $hfhudcode ?: $request->query('hfhudcode', '');

            // Generate the transaction code via service
            $transaction_code = $this->referralService->generate_code($resolvedCode);

            return response()->json([
                'code' => $transaction_code,
                'hfhudcode' => $transaction_code,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate HFHUD code.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function religions(Request $request)
    {
        return response()->json([
            'data' => $this->referralService->getreligion($request->all()),
        ]);
    }

    public function test()
    {
        $plaintext = '1';
        $encrypted = Crypt::encryptString($plaintext);
        echo 'Encrypted: '.$encrypted;

        $decrypted = Crypt::decryptString($encrypted);
        // echo "Decrypted: " . $decrypted;

    }

    private function buildReferralPayload(array $validated): array
    {
        [$referDate, $referTime] = $this->splitDateTime($validated['refferalDate']);
        $calledDate = ! empty($validated['calledDate'])
            ? Carbon::parse($validated['calledDate'])->format('Y-m-d H:i:s')
            : null;

        return [
            'referral' => [
                'facility_from' => $validated['referringFacility'],
                'facility_to' => $validated['referralFacility'],
                'contact_no' => $validated['referralContactNumber'] ?? null,
                'type_referral' => $validated['typeOfReferral'],
                'category' => $validated['referralCategory'],
                'reason' => $validated['referralReason'],
                'other_reason' => $validated['otherReferralReason'] ?? null,
                'remarks' => $validated['referralRemarks'] ?? null,
                'contact_person' => $validated['contactPerson'],
                'designation' => $validated['contactDesignation'] ?? null,
                'refer_date' => $referDate,
                'refer_time' => $referTime,
                'called_date' => $calledDate,
                'generated_code' => $validated['transactionCode'] ?? null,
            ],
            'patient' => [
                'family_number' => $validated['familyNumber'] ?? null,
                'phic_number' => $validated['phicNumber'] ?? null,
                'case_no' => $validated['caseNumber'] ?? null,
                'last_name' => $validated['patientLastName'],
                'first_name' => $validated['patientFirstName'],
                'middle_name' => $validated['patientMiddleName'] ?? '',
                'suffix' => $validated['patientSuffix'] ?? '.',
                'birthdate' => Carbon::parse($validated['patientBirthDate'])->format('Y-m-d'),
                'sex' => $validated['patientSex'],
                'civil_status' => ReferralHelper::normalizeCivilStatus($validated['patientCivilStatus'] ?? null),
                'religion' => $validated['religion'] ?? null,
                'contact_no' => $validated['patientContactNumber'] ?? null,
                'blood_type' => $validated['bloodType'] ?? null,
                'blood_rh' => $validated['bloodRh'] ?? null,
            ],
            'demographics' => [
                'street' => $validated['patientStreetAddress'],
                'brgy_code' => $validated['barangay'],
                'city_code' => $validated['city'],
                'prov_code' => $validated['province'],
                'reg_code' => $validated['region'],
                'zipcode' => $validated['zipcode'],
            ],
            'clinical' => [
                'diagnosis' => $this->parseDiagnoses($validated['diagnosis']),
                'history' => $validated['clinicalHistory'] ?? '',
                'physical_examination' => null,
                'chief_complaint' => $validated['chiefComplaint'],
                'findings' => $validated['findings'] ?? '',
            ],
            'vital_signs' => [
                'BP' => $validated['bp'] ?? null,
                'temp' => $validated['temp'] ?? null,
                'HR' => $validated['hr'] ?? null,
                'RR' => $validated['rr'] ?? null,
                'O2_sats' => $validated['o2Sats'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'height' => $validated['height'] ?? null,
            ],
            'patient_providers' => [[
                'provider_last' => $validated['providerLastName'],
                'provider_first' => $validated['providerFirstName'],
                'provider_middle' => $validated['providerMiddleName'] ?? '',
                'provider_suffix' => $validated['providerSuffix'] ?? null,
                'provider_type' => 'REFER',
            ]],
            'ICD' => [],
        ];
    }

    private function splitDateTime(string $value): array
    {
        $dateTime = Carbon::parse($value);

        return [
            $dateTime->format('Y-m-d'),
            $dateTime->format('H:i:s'),
        ];
    }

    private function parseDiagnoses(string $value): array
    {
        $diagnoses = collect(preg_split('/[\r\n,]+/', $value))
            ->map(fn (?string $item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        if (empty($diagnoses)) {
            throw ValidationException::withMessages([
                'diagnosis' => 'Add at least one diagnosis.',
            ]);
        }

        return $diagnoses;
    }

    private function referralValidationRules(): array
    {
        return [
            'profilePic' => 'nullable|image|max:5120',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf',
            'patientFirstName' => 'required|string|max:50',
            'patientMiddleName' => 'nullable|string|max:50',
            'patientLastName' => 'required|string|max:50',
            'patientSuffix' => 'nullable|string|max:5',
            'patientBirthDate' => 'required|date',
            'patientSex' => 'required|in:M,F',
            'patientCivilStatus' => ['nullable', 'string', 'max:25', Rule::in(ReferralHelper::getAcceptedCivilStatusInputs())],
            'patientContactNumber' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:50',
            'bloodType' => 'nullable|string|max:5',
            'bloodRh' => 'nullable|string|max:2',
            'familyNumber' => 'nullable|string|max:20',
            'caseNumber' => 'nullable|string|max:50',
            'phicNumber' => 'nullable|string|max:50',
            'patientStreetAddress' => 'required|string|max:255',
            'region' => 'required|string|max:2',
            'province' => 'required|string|max:4',
            'city' => 'required|string|max:6',
            'barangay' => 'required|string|max:10',
            'zipcode' => 'required|string|max:5',
            'referringFacility' => 'required|string|exists:ref_facilities,hfhudcode',
            'referralFacility' => 'required|string|different:referringFacility|exists:ref_facilities,hfhudcode',
            'calledDate' => 'nullable|date',
            'refferalDate' => 'required|date',
            'transactionCode' => 'nullable|string|max:50',
            'typeOfReferral' => 'required|string|in:TRANS,CONSU,DIAGT,OTHER',
            'referralCategory' => 'required|string|in:ER,OPD',
            'referralReason' => 'required|string|max:5',
            'otherReferralReason' => 'nullable|required_if:referralReason,OTHER|string|max:50',
            'contactPerson' => 'required|string|max:100',
            'contactDesignation' => 'nullable|string|max:100',
            'referralContactNumber' => 'nullable|string|max:20',
            'referralRemarks' => 'nullable|string|max:1000',
            'diagnosis' => 'required|string|max:1000',
            'chiefComplaint' => 'required|string|max:1000',
            'clinicalHistory' => 'nullable|string|max:2000',
            'findings' => 'nullable|string|max:2000',
            'providerFirstName' => 'required|string|max:25',
            'providerMiddleName' => 'nullable|string|max:25',
            'providerLastName' => 'required|string|max:25',
            'providerSuffix' => 'nullable|string|max:4',
            'bp' => 'nullable|string|max:20',
            'temp' => 'nullable|string|max:20',
            'hr' => 'nullable|string|max:20',
            'rr' => 'nullable|string|max:20',
            'o2Sats' => 'nullable|string|max:20',
            'weight' => 'nullable|string|max:20',
            'height' => 'nullable|string|max:20',
        ];
    }

    private function referralValidationMessages(): array
    {
        return [
            'referralFacility.different' => 'Choose a different receiving facility.',
            'otherReferralReason.required_if' => 'Please specify the referral reason.',
            'contactPerson.required' => 'A receiving contact person is required.',
            'diagnosis.required' => 'Add at least one diagnosis.',
            'chiefComplaint.required' => 'Chief complaint is required.',
            'patientCivilStatus.in' => 'Choose a valid civil status.',
            'attachments.max' => 'A maximum of 5 attachments is allowed per upload.',
            'attachments.*.file' => 'Each attachment must be a valid uploaded file.',
            'attachments.*.max' => 'Each attachment must not exceed 10 MB.',
            'attachments.*.mimes' => 'Attachments must be JPEG, PNG, WebP, or PDF files.',
        ];
    }

    private function decodeLogId(string $value): string
    {
        $decoded = base64_decode($value, true);

        return $decoded !== false && $decoded !== '' ? $decoded : $value;
    }

    private function formatDateTimeLocal(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format('Y-m-d\TH:i');
    }

    private function formatDate(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function combineReferralDateTime(mixed $date, mixed $time): ?string
    {
        if (empty($date)) {
            return null;
        }

        $resolvedTime = empty($time) ? '00:00:00' : $time;

        return Carbon::parse(trim((string) $date).' '.trim((string) $resolvedTime))->toDateTimeString();
    }

    private function emptyDotValue(mixed $value): string
    {
        return trim((string) $value) === '.' ? '' : (string) ($value ?? '');
    }

    private function emptyNaValue(mixed $value): string
    {
        return strtoupper(trim((string) $value)) === 'N/A' ? '' : (string) ($value ?? '');
    }

    private function emptyZeroValue(mixed $value): string
    {
        return trim((string) $value) === '0' ? '' : (string) ($value ?? '');
    }

    private function buildLocationSummary($query, string $codeColumn, string $nameColumn): array
    {
        return $query
            ->selectRaw("{$codeColumn} as code, COALESCE(NULLIF({$nameColumn}, ''), 'Unspecified') as label, COUNT(DISTINCT referral_information.LogID) as aggregate")
            ->whereNotNull($codeColumn)
            ->groupBy($codeColumn, $nameColumn)
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get()
            ->map(fn ($item) => [
                'code' => $item->code,
                'label' => $item->label,
                'count' => (int) $item->aggregate,
            ])
            ->values()
            ->all();
    }

    private function applyIncomingScope($query, ?Authenticatable $user): void
    {
        $scope = $this->resolveIncomingScopeType($user);
        $accessId = trim((string) ($user?->access_id ?? ''));

        if (! $scope) {
            return;
        }

        if ($accessId === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($scope === 'emr') {
            $query->whereHas('facility_to', function ($facilityQuery) use ($accessId) {
                $facilityQuery->where('emr_id', $accessId);
            });

            return;
        }

        if ($scope === 'region') {
            $query->whereHas('facility_to', function ($facilityQuery) use ($accessId) {
                $facilityQuery->where('region_code', $accessId);
            });

            return;
        }

        if ($scope === 'hospital') {
            $query->whereHas('facility_to', function ($facilityQuery) use ($accessId) {
                $facilityQuery->where('hfhudcode', $accessId);
            });
        }
    }

    private function resolveIncomingScopeType(?Authenticatable $user): ?string
    {
        if (! $user) {
            return null;
        }

        $accessType = strtoupper(trim((string) ($user->access_type ?? '')));

        if ($accessType === 'EMR') {
            return 'emr';
        }

        if ($accessType === 'CHD') {
            return 'region';
        }

        if ($accessType === 'HOSP') {
            return 'hospital';
        }

        if (! method_exists($user, 'getRoleNames')) {
            return null;
        }

        $role = strtolower((string) ($user->getRoleNames()->first() ?? ''));

        return match ($role) {
            'emr' => 'emr',
            'region' => 'region',
            'hospital' => 'hospital',
            default => null,
        };
    }
}
