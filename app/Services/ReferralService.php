<?php

namespace App\Services;

use App\Helpers\ReferralHelper;
use App\Models\BloodTypeModel;
use App\Models\PatientModel;
use App\Models\ReferralInformationModel;
use App\Models\ReferralPatientDemoModel;
use App\Models\ReferralPatientInfoModel;
use App\Models\RefFacilitiesModel;
use App\Models\RefReligionModel;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReferralService
{
    protected $referralModel;

    public function __construct() {}

    public function maxID()
    {
        return (int) ReferralInformationModel::query()->count();
    }

    public function type($id)
    {
        return RefFacilitiesModel::where('hfhudcode', $id)
            ->value('facility_type');
    }

    private function _check_fhud($id)
    {
        return RefFacilitiesModel::where('hfhudcode', $id)->exists();
    }

    private function logIdExists(?string $logId): bool
    {
        return ! empty($logId) && ReferralInformationModel::where('LogID', $logId)->exists();
    }

    public function generate_code($fhudcode)
    {

        $facility = $this->type($fhudcode);
        $type = $facility;
        $maxID = $this->maxID();

        if ($type == '4' || $type == '1') {
            $code = 'HOSP-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } elseif ($type == '17') {
            $code = 'RHU-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } elseif ($type == '15') {
            $code = 'BiHo-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } elseif ($type == '19') {
            $code = 'MHO-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } elseif ($type == '21') {
            $code = 'PHO-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else {
            $code = 'REF-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');

            return str_pad($code, 6, 0, STR_PAD_LEFT);
        }
    }

    public function encrypt($data)
    {
        return Crypt::encryptString($data);
    }

    public function decrypt($data)
    {
        return Crypt::decryptString($data);
    }

    public function generateRandomString($length = 5)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function refer_patient(array $data)
    {
        // List of validations to perform
        $validations = [
            [
                'check' => $this->_check_fhud($data['referral']['facility_from']),
                'error' => 'Referring facility does not exist!',
            ],
            [
                'check' => $this->_check_fhud($data['referral']['facility_to']),
                'error' => 'Referral facility does not exist!',
            ],
            [
                'check' => ReferralHelper::getReferralReasonbyCode($data['referral']['reason']) !== null,
                'error' => 'Please check the reference for referral reason!',
            ],
            [
                'check' => ReferralHelper::getReferralTypebyCode($data['referral']['type_referral']) !== null,
                'error' => 'Please check the reference for referral type!',
            ],
        ];

        // Run validations
        foreach ($validations as $validation) {
            if (! $validation['check']) {
                return [
                    'code' => '401',
                    'message' => $validation['error'],
                ];
            }
        }

        $exists = $this->check($data);

        if ($exists) {
            return [
                'code' => $exists,
                'message' => 'Referral already submitted!',
            ];
        }

        $logID = $this->transaction_refer($data);

        if (is_string($logID) && $logID !== '') {
            return [
                'code' => $logID,
                'message' => 'Referral successfully transmitted',
                'data' => $data,
            ];
        }

        if (is_array($logID)) {
            return [
                'code' => (string) ($logID['code'] ?? 500),
                'message' => $logID['message'] ?? 'Referral failed to transmit',
                'error' => $logID['error'] ?? null,
            ];
        }

        return [
            'code' => '404',
            'message' => 'Referral failed to transmit',
        ];

    }

    public function check(array $data, ?string $ignoreLogId = null)
    {
        $existingPatient = ReferralPatientInfoModel::where([
            ['patientLastName', $data['patient']['last_name']],
            ['patientFirstName', $data['patient']['first_name']],
            ['patientMiddlename', $data['patient']['middle_name']],
            ['patientSuffix', ($data['patient']['suffix']) ? $data['patient']['suffix'] : '.'],
            ['patientBirthDate', $data['patient']['birthdate']],
            ['patientSex', $data['patient']['sex']],
            ['patientCivilStatus', $data['patient']['civil_status']],
        ]);

        $existingReferral = ReferralInformationModel::where([
            ['fhudFrom', $data['referral']['facility_from']],
            ['fhudTo', $data['referral']['facility_to']],
            ['typeOfReferral', $data['referral']['type_referral']],
            ['referralCategory', $data['referral']['category']],
            ['refferalDate', date('Y-m-d', strtotime($data['referral']['refer_date'])) ?? null],
            ['referralReason', $data['referral']['reason']],
        ]);

        if ($ignoreLogId) {
            $existingPatient->where('LogID', '!=', $ignoreLogId);
            $existingReferral->where('LogID', '!=', $ignoreLogId);
        }

        $existingPatient = $existingPatient->first();
        $existingReferral = $existingReferral->first();

        if ($existingReferral && $existingPatient) {
            return $existingReferral->LogID;
        }
    }

    public function transaction_refer(array $data)
    {
        DB::beginTransaction();

        try {
            $requestedLogId = $data['referral']['generated_code'] ?? null;
            $LogID = $this->logIdExists($requestedLogId)
                ? $this->generate_code($data['referral']['facility_from'])
                : ($requestedLogId ?: $this->generate_code($data['referral']['facility_from']));

            $referral = [
                'LogID' => $LogID,
                'fhudFrom' => $data['referral']['facility_from'] ?? null,
                'fhudTo' => $data['referral']['facility_to'] ?? null,
                'typeOfReferral' => $data['referral']['type_referral'] ?? null,
                'referralCategory' => $data['referral']['category'] ?? null,
                'referralReason' => $data['referral']['reason'] ?? null,
                'referralContactPerson' => $data['referral']['contact_person'] ?? null,
                'referralContactPersonDesignation' => $data['referral']['designation'] ?? 'N/A',
                'referringProvider' => 'N/A',
                'referringProviderContactNumber' => $data['referral']['contact_no'] ?? null,
                'otherReasons' => $data['referral']['other_reason'] ?? null,
                'remarks' => $data['referral']['remarks'] ?? null,
                'refferalDate' => $data['referral']['refer_date'] ?? null,
                'refferalTime' => $data['referral']['refer_time'] ?? null,
                'calledDate' => $data['referral']['called_date'] ?? null,
                'logDate' => date('Y-m-d H:i:s'),
                'created_at' => now(),
            ];

            $ref = ReferralInformationModel::create($referral);
            if (! $ref) {
                throw new Exception('Failed to insert referral information');
            }

            $patient = [
                'LogID' => $LogID,
                'FamilyID' => ($data['patient']['family_number']) ? $data['patient']['family_number'] : 0,
                'caseNum' => ($data['patient']['case_no']) ? (int) $data['patient']['case_no'] : 0,
                'phicNum' => ($data['patient']['phic_number']) ? (int) $data['patient']['phic_number'] : 0,
                'patientLastName' => $data['patient']['last_name'],
                'patientFirstName' => $data['patient']['first_name'],
                'patientMiddlename' => $data['patient']['middle_name'],
                'patientSuffix' => ($data['patient']['suffix']) ? $data['patient']['suffix'] : '.',
                'patientBirthDate' => $data['patient']['birthdate'],
                'patientSex' => $data['patient']['sex'],
                'patientContactNumber' => $data['patient']['contact_no'],
                'patientReligion' => $data['patient']['religion'],
                'patientBloodType' => $data['patient']['blood_type'] ?? null,
                'patientBloodTypeRH' => $data['patient']['blood_rh'] ?? null,
                'patientCivilStatus' => $data['patient']['civil_status'] ?? null,
            ];

            $pat = ReferralPatientInfoModel::create($patient);
            if (! $pat) {
                throw new Exception('Failed to insert patient info');
            }

            $demographics = [
                'LogID' => $LogID,
                'patientStreetAddress' => $data['demographics']['street'],
                'patientBrgyCode' => $data['demographics']['brgy_code'],
                'patientMundCode' => $data['demographics']['city_code'],
                'patientProvCode' => $data['demographics']['prov_code'],
                'patientRegCode' => $data['demographics']['reg_code'],
                'patientZipCode' => $data['demographics']['zipcode'],
            ];

            $demo = ReferralPatientDemoModel::create($demographics);
            if (! $demo) {
                throw new Exception('Failed to insert demographics');
            }

            $this->syncPatientMasterList($LogID, $data);

            $referring_provider = [
                'LogID' => $LogID,
                'provider_last' => $data['patient_providers'][0]['provider_last'],
                'provider_first' => $data['patient_providers'][0]['provider_first'],
                'provider_middle' => $data['patient_providers'][0]['provider_middle'],
                'provider_suffix' => ($data['patient_providers'][0]['provider_suffix']) ? $data['patient_providers'][0]['provider_suffix'] : 'N/A',
                'provider_type' => $data['patient_providers'][0]['provider_type'],
            ];

            if (! DB::table('referral_provider')->insert($referring_provider)) {
                throw new Exception('Failed to insert referring provider');
            }

            /*         $consulting_provider = [
                        'LogID'=>$LogID,
                        'provider_last'=>$data['patient_providers'][1]['provider_last'],
                        'provider_first'=>$data['patient_providers'][1]['provider_first'],
                        'provider_middle'=>$data['patient_providers'][1]['provider_middle'],
                        'provider_suffix'=>($data['patient_providers'][1]['provider_suffix']) ? $data['patient_providers'][1]['provider_suffix'] : 'N/A',
                        'provider_type'=>$data['patient_providers'][1]['provider_type'],
                    ];

                    if (!DB::table('referral_provider')->insert($consulting_provider)) {
                        throw new Exception('Failed to insert consulting provider');
                    }
             */
            $clinical = $data['clinical'];

            $insertData = [
                'LogID' => $LogID,
                'clinicalDiagnosis' => implode(', ', $clinical['diagnosis']),

                'clinicalHistory' => $clinical['history'],
                'physicalExamination' => isset($clinical['physical_examination']) ? json_encode($clinical['physical_examination']) : null,
                'chiefComplaint' => $clinical['chief_complaint'],
                'findings' => $clinical['findings'],
                'vitals' => json_encode($data['vital_signs']),
            ];

            if (! DB::table('referral_clinical')->insert($insertData)) {
                throw new Exception('Failed to insert clinical data');
            }

            DB::commit();

            return $LogID;

        } catch (Exception $e) {
            DB::rollBack();

            return [
                'code' => 500,
                'status' => false,
                'message' => 'Transaction failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateReferral(string $logID, array $data): array
    {
        $validations = [
            [
                'check' => $this->_check_fhud($data['referral']['facility_from']),
                'error' => 'Referring facility does not exist!',
            ],
            [
                'check' => $this->_check_fhud($data['referral']['facility_to']),
                'error' => 'Referral facility does not exist!',
            ],
            [
                'check' => ReferralHelper::getReferralReasonbyCode($data['referral']['reason']) !== null,
                'error' => 'Please check the reference for referral reason!',
            ],
            [
                'check' => ReferralHelper::getReferralTypebyCode($data['referral']['type_referral']) !== null,
                'error' => 'Please check the reference for referral type!',
            ],
        ];

        foreach ($validations as $validation) {
            if (! $validation['check']) {
                return [
                    'code' => '401',
                    'message' => $validation['error'],
                ];
            }
        }

        $duplicateLogId = $this->check($data, $logID);
        if ($duplicateLogId) {
            return [
                'code' => $duplicateLogId,
                'message' => 'Referral already submitted!',
            ];
        }

        DB::beginTransaction();

        try {
            $referral = ReferralInformationModel::where('LogID', $logID)->first();

            if (! $referral) {
                throw new Exception('Referral not found.');
            }

            $referral->fill([
                'fhudFrom' => $data['referral']['facility_from'] ?? null,
                'fhudTo' => $data['referral']['facility_to'] ?? null,
                'typeOfReferral' => $data['referral']['type_referral'] ?? null,
                'referralCategory' => $data['referral']['category'] ?? null,
                'referralReason' => $data['referral']['reason'] ?? null,
                'referralContactPerson' => $data['referral']['contact_person'] ?? null,
                'referralContactPersonDesignation' => $data['referral']['designation'] ?? 'N/A',
                'referringProviderContactNumber' => $data['referral']['contact_no'] ?? null,
                'otherReasons' => $data['referral']['other_reason'] ?? null,
                'remarks' => $data['referral']['remarks'] ?? null,
                'refferalDate' => $data['referral']['refer_date'] ?? null,
                'refferalTime' => $data['referral']['refer_time'] ?? null,
                'calledDate' => $data['referral']['called_date'] ?? null,
            ]);
            $referral->save();

            ReferralPatientInfoModel::updateOrCreate(
                ['LogID' => $logID],
                [
                    'FamilyID' => ($data['patient']['family_number']) ? $data['patient']['family_number'] : 0,
                    'caseNum' => ($data['patient']['case_no']) ? (int) $data['patient']['case_no'] : 0,
                    'phicNum' => ($data['patient']['phic_number']) ? (int) $data['patient']['phic_number'] : 0,
                    'patientLastName' => $data['patient']['last_name'],
                    'patientFirstName' => $data['patient']['first_name'],
                    'patientMiddlename' => $data['patient']['middle_name'],
                    'patientSuffix' => ($data['patient']['suffix']) ? $data['patient']['suffix'] : '.',
                    'patientBirthDate' => $data['patient']['birthdate'],
                    'patientSex' => $data['patient']['sex'],
                    'patientContactNumber' => $data['patient']['contact_no'],
                    'patientReligion' => $data['patient']['religion'],
                    'patientBloodType' => $data['patient']['blood_type'] ?? null,
                    'patientBloodTypeRH' => $data['patient']['blood_rh'] ?? null,
                    'patientCivilStatus' => $data['patient']['civil_status'] ?? null,
                ]
            );

            ReferralPatientDemoModel::updateOrCreate(
                ['LogID' => $logID],
                [
                    'patientStreetAddress' => $data['demographics']['street'],
                    'patientBrgyCode' => $data['demographics']['brgy_code'],
                    'patientMundCode' => $data['demographics']['city_code'],
                    'patientProvCode' => $data['demographics']['prov_code'],
                    'patientRegCode' => $data['demographics']['reg_code'],
                    'patientZipCode' => $data['demographics']['zipcode'],
                ]
            );

            DB::table('referral_provider')->updateOrInsert(
                [
                    'LogID' => $logID,
                    'provider_type' => 'REFER',
                ],
                [
                    'provider_last' => $data['patient_providers'][0]['provider_last'],
                    'provider_first' => $data['patient_providers'][0]['provider_first'],
                    'provider_middle' => $data['patient_providers'][0]['provider_middle'],
                    'provider_suffix' => ($data['patient_providers'][0]['provider_suffix']) ? $data['patient_providers'][0]['provider_suffix'] : 'N/A',
                ]
            );

            DB::table('referral_clinical')->updateOrInsert(
                ['LogID' => $logID],
                [
                    'clinicalDiagnosis' => implode(', ', $data['clinical']['diagnosis']),
                    'clinicalHistory' => $data['clinical']['history'],
                    'physicalExamination' => isset($data['clinical']['physical_examination']) ? json_encode($data['clinical']['physical_examination']) : null,
                    'chiefComplaint' => $data['clinical']['chief_complaint'],
                    'findings' => $data['clinical']['findings'],
                    'vitals' => json_encode($data['vital_signs']),
                ]
            );

            $this->syncPatientMasterList($logID, $data);

            DB::commit();

            return [
                'code' => '200',
                'message' => 'Referral updated successfully.',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'code' => '500',
                'message' => 'Unable to update the referral right now.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function syncPatientMasterList(string $logID, array $data): void
    {
        if (! Schema::hasTable('patient_master_list')) {
            return;
        }

        $birthDate = $this->normalizeMasterDate($data['patient']['birthdate'] ?? null);
        $civilStatus = $this->normalizeMasterValue($data['patient']['civil_status'] ?? null);
        $existingPatient = $this->findExistingMasterPatient([
            'first_name' => $data['patient']['first_name'] ?? null,
            'middle_name' => $data['patient']['middle_name'] ?? null,
            'last_name' => $data['patient']['last_name'] ?? null,
            'suffix' => $data['patient']['suffix'] ?? null,
            'birth_date' => $birthDate,
            'civil_status' => $civilStatus,
        ]);

        $payload = [
            'family_id' => $this->normalizeMasterValue($data['patient']['family_number'] ?? null),
            'phic_number' => $this->normalizeMasterValue($data['patient']['phic_number'] ?? null),
            'case_number' => $this->normalizeMasterValue($data['patient']['case_no'] ?? null),
            'last_name' => $this->normalizeMasterValue($data['patient']['last_name'] ?? null),
            'first_name' => $this->normalizeMasterValue($data['patient']['first_name'] ?? null),
            'middle_name' => $this->normalizeMasterValue($data['patient']['middle_name'] ?? null),
            'suffix' => $this->normalizeMasterValue($data['patient']['suffix'] ?? null),
            'birth_date' => $birthDate,
            'sex' => $this->normalizeMasterValue($data['patient']['sex'] ?? null),
            'contact_number' => $this->normalizeMasterValue($data['patient']['contact_no'] ?? null),
            'religion' => $this->normalizeMasterValue($data['patient']['religion'] ?? null),
            'blood_type' => $this->normalizeMasterValue($data['patient']['blood_type'] ?? null),
            'blood_type_rh' => $this->normalizeMasterValue($data['patient']['blood_rh'] ?? null),
            'civil_status' => $civilStatus,
            'street_address' => $this->normalizeMasterValue($data['demographics']['street'] ?? null),
            'barangay_code' => $this->normalizeMasterValue($data['demographics']['brgy_code'] ?? null),
            'city_code' => $this->normalizeMasterValue($data['demographics']['city_code'] ?? null),
            'province_code' => $this->normalizeMasterValue($data['demographics']['prov_code'] ?? null),
            'region_code' => $this->normalizeMasterValue($data['demographics']['reg_code'] ?? null),
            'zip_code' => $this->normalizeMasterValue($data['demographics']['zipcode'] ?? null),
        ];

        if ($existingPatient) {
            if ($existingPatient->trashed()) {
                $existingPatient->restore();
            }

            $existingPatient->fill($payload);
            $existingPatient->save();

            return;
        }

        PatientModel::create([
            'legacy_log_id' => $logID,
            ...$payload,
        ]);
    }

    private function findExistingMasterPatient(array $identity): ?PatientModel
    {
        $birthDate = $this->normalizeMasterDate($identity['birth_date'] ?? null);
        if (! $birthDate) {
            return null;
        }

        $query = PatientModel::withTrashed()->newQuery();

        foreach ([
            'first_name' => $identity['first_name'] ?? null,
            'middle_name' => $identity['middle_name'] ?? null,
            'last_name' => $identity['last_name'] ?? null,
            'suffix' => $identity['suffix'] ?? null,
            'civil_status' => $identity['civil_status'] ?? null,
        ] as $column => $value) {
            $normalized = $this->normalizeMasterValue($value) ?? '';
            $query->whereRaw("UPPER(TRIM(COALESCE({$column}, ''))) = ?", [$normalized]);
        }

        return $query
            ->whereDate('birth_date', $birthDate)
            ->first();
    }

    private function normalizeMasterValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeMasterDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    public function getDischargeInformation($logId)
    {
        try {
            DB::beginTransaction();

            $record = DB::table('referral_track')
                ->whereNotNull('dischDate')
                ->whereNotNull('admDate')
                ->where('LogID', $logId)
                ->first();

            if (! $record) {
                return response()->json(['message' => 'No record found!'], 404);
            }

            $resultRecord = [
                'LogID' => $record->LogID,
                'admDateTime' => date('m/d/Y H:i:s', strtotime($record->admDate)),
                'dischDateTime' => date('m/d/Y H:i:s', strtotime($record->dischDate)),
                'diagnosis' => $record->diagnosis ?? 'Diagnosis not specified',
                'dischDisp' => $record->dischDisp,
                'dischCond' => $record->dischCond,
                'disnotes' => $record->disnotes,
                'hasFollowUp' => $record->hasFollowup,
                'hasMedicine' => $record->hasMedicine,
                'remarks' => $record->trackRemarks ?? '',
            ];

            $scheduleQuery = null;
            if ($record->hasFollowup === 'Y') {
                $scheduleQuery = DB::table('referral_followup')
                    ->where('LogID', $logId)
                    ->value('scheduleDateTime');
            }

            $medQuery = [];
            if ($record->hasMedicine === 'Y') {
                $medQuery = DB::table('referral_medicine')
                    ->select('drugcode', 'generic', 'instruction')
                    ->where('LogID', $logId)
                    ->get();
            }

            DB::commit();

            return response()->json([
                'dischargeData' => $resultRecord,
                'drugs' => $medQuery,
                'schedule' => $scheduleQuery,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DB transaction failed in '.__METHOD__, [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'last_query' => DB::getQueryLog(),
            ]);

            return response()->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function dischargeTransaction($param)
    {
        try {
            DB::beginTransaction();

            $updated = DB::table('referral_track')
                ->where('LogID', $param['LogID'])
                ->update($param['discharge']);

            if (! $updated) {
                throw new Exception('Failed to update discharge record.');
            }

            if ($param['discharge']['hasFollowUp'] === 'Y') {
                $followupInserted = DB::table('referral_followup')->insert($param['followup']);
                if (! $followupInserted) {
                    throw new Exception('Failed to insert follow-up.');
                }
            }

            if ($param['discharge']['hasMedicine'] === 'Y') {
                $medicineInserted = DB::table('referral_medicine')->insert($param['medicine']); // Replace with actual table
                if (! $medicineInserted) {
                    throw new Exception('Failed to insert medicine records.');
                }
            }

            DB::commit();

            return [
                'code' => 200,
                'message' => 'Success!',
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error(sprintf(
                'dischargeTransaction failed: %s in %s on line %d',
                $e->getMessage(), $e->getFile(), $e->getLine()
            ));

            return [
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getActiveFacilities(?string $facility_name = null)
    {
        try {
            $query = RefFacilitiesModel::query()
                ->where('status', 'A')
                ->whereNotNull('emr_id');

            if (! empty($facility_name)) {
                $query->where('facility_name', 'LIKE', '%'.$facility_name.'%');
            }

            $data = $query->get();

            return [
                'code' => 200,
                'data' => $data,
                'message' => 'Success!',
            ];

        } catch (Exception $e) {
            Log::error(sprintf(
                'getActiveFacilities failed: %s in %s on line %d',
                $e->getMessage(), $e->getFile(), $e->getLine()
            ));

            return [
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getBloodTypes(?string $search = null, ?int $is_active = null)
    {
        try {
            // Start query
            $query = BloodTypeModel::select('name', 'value');

            // Optional filter by active status
            if (! is_null($is_active)) {
                $query->where('is_active', $is_active);
            }

            // Optional search by name or value
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('value', 'like', "%{$search}%");
                });
            }

            // Order results by name
            $data = $query->orderBy('name', 'asc')->get();

            // Return structured response
            return [
                'code' => 200,
                'data' => $data,
                'count' => $data->count(),
                'message' => 'Success!',
            ];

        } catch (Exception $e) {
            Log::error(sprintf(
                'getBloodtype failed: %s in %s on line %d',
                $e->getMessage(), $e->getFile(), $e->getLine()
            ));

            return [
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getreligion(array $filters = [])
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['relstat'] ?? 'A'));

        if (Schema::hasTable('ref_religion')) {
            $query = RefReligionModel::query()->select('relcode', 'reldesc');

            if ($status !== '') {
                $query->where('relstat', $status);
            }

            if ($search !== '') {
                $query->where('reldesc', 'like', "%{$search}%");
            }

            return $query
                ->orderBy('reldesc')
                ->get();
        }

        $options = collect();

        if (Schema::hasTable('referral_patientinfo')) {
            $options = DB::table('referral_patientinfo')
                ->select('patientReligion')
                ->whereNotNull('patientReligion')
                ->whereRaw("TRIM(patientReligion) <> ''")
                ->distinct()
                ->orderBy('patientReligion')
                ->get()
                ->map(function ($item) {
                    $value = trim((string) ($item->patientReligion ?? ''));

                    return [
                        'relcode' => strtoupper(str_replace(' ', '_', $value)),
                        'reldesc' => $value,
                    ];
                });
        }

        if ($options->isEmpty()) {
            $options = collect($this->fallbackReligions());
        }

        if ($search !== '') {
            $options = $options->filter(function (array $item) use ($search) {
                return str_contains(strtolower($item['reldesc']), strtolower($search));
            })->values();
        }

        return $options->values();
    }

    private function fallbackReligions(): array
    {
        return [
            ['relcode' => 'ROMAN_CATHOLIC', 'reldesc' => 'Roman Catholic'],
            ['relcode' => 'ISLAM', 'reldesc' => 'Islam'],
            ['relcode' => 'IGLESIA_NI_CRISTO', 'reldesc' => 'Iglesia ni Cristo'],
            ['relcode' => 'SEVENTH_DAY_ADVENTIST', 'reldesc' => 'Seventh-day Adventist'],
            ['relcode' => 'JEHOVAHS_WITNESSES', 'reldesc' => "Jehovah's Witnesses"],
            ['relcode' => 'BORN_AGAIN_CHRISTIAN', 'reldesc' => 'Born Again Christian'],
            ['relcode' => 'PROTESTANT', 'reldesc' => 'Protestant'],
            ['relcode' => 'BAPTIST', 'reldesc' => 'Baptist'],
            ['relcode' => 'METHODIST', 'reldesc' => 'Methodist'],
            ['relcode' => 'AGLIPAYAN', 'reldesc' => 'Aglipayan'],
            ['relcode' => 'OTHERS', 'reldesc' => 'Others'],
        ];
    }

    public function getReferralStatus()
    {
        return ReferralHelper::getReferralStatus();
    }

    public function saveReferralStatus(array $data)
    {
        try {
            //  Validate status against allowed list
            $statusIsValid = ReferralHelper::getReferralStatus($data['status']);
            if (! $statusIsValid) {
                return [
                    'code' => 400,
                    'message' => 'Invalid referral status provided.',
                ];
            }

            // 3️⃣ Check if the same LogID already has this status
            $existing = DB::table('referral_status')
                ->where('LogID', $data['LogID'])
                ->where('referral_status', strtoupper($data['status']))
                ->first();
            if ($existing) {
                return [
                    'code' => 400,
                    'message' => 'This status already exists for the given LogID.',
                ];
            }

            $checklogid = DB::table('referral_information')
                ->where('LogID', $data['LogID'])
                ->first();

            if ($checklogid) {
                return [
                    'code' => 400,
                    'message' => 'Invalid LogID.',
                ];
            }

            // update if exists, otherwise insert
            $saved = DB::table('referral_status')->insert([
                'LogID' => $data['LogID'],
                'referral_status' => $data['status'],
                'remarks' => $data['remarks'] ?? 'n/a',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'code' => 200,
                'message' => 'Referral status saved successfully.',
            ];

        } catch (Exception $e) {
            Log::error(sprintf(
                'saveReferralStatus failed: %s in %s on line %d',
                $e->getMessage(), $e->getFile(), $e->getLine()
            ));

            return [
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getReferralPatientStatus($LogID)
    {
        $status = DB::table('referral_status')
            ->where('LogID', $LogID)
            ->orderBy('created_at', 'desc')
            ->first();

        return $status ? $status->referral_status : null;
    }

    public function getReferralPatientStatusList($LogID)
    {
        $status = DB::table('referral_status')
            ->where('LogID', $LogID)
            ->orderBy('created_at', 'desc')
            ->first();

        return $status ? $status->referral_status : null;
    }
}
