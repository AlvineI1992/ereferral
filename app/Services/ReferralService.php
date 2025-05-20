<?php

namespace App\Services;

use App\Models\ReferralInformationModel;
use App\Models\ReferralClinicalModel;
use App\Models\ReferralPatientDemoModel;
use App\Models\ReferralPatientInfoModel;
use Illuminate\Support\Facades\DB;
use App\Models\RefFacilitiesModel;
use App\Helpers\ReferralHelper;

use Exception;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    protected $referralModel;

    public function __construct() {}

    public function maxID()
    {
        $latestReferral = ReferralInformationModel::orderBy('logDate', 'desc')->first()->value('LogID');
        $lastNumber = preg_replace('/\D+/', '', substr($latestReferral, -1));
        return $lastNumber;
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


    function generate_code($fhudcode)
    {

        $facility = $this->type($fhudcode);
        $type = $facility;
        $maxID = $this->maxID();

        if ($type == '4' || $type == '1') {
            $code = 'HOSP-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');
            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else if ($type == '17') {
            $code = 'RHU-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');
            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else if ($type == '15') {
            $code = 'BiHo-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');
            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else if ($type == '19') {
            $code = 'MHO-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');
            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else if ($type == '21') {
            $code = 'PHO-';
            $code .= $maxID + 1;
            $code .= date('mdyhis');
            return str_pad($code, 6, 0, STR_PAD_LEFT);
        } else {
            return 0;
        }
    }


    function generateRandomString($length = 5)
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
            if (!$validation['check']) {
                return [
                    'code' => '401',
                    'message' => $validation['error'],
                ];
            }
        }

        $logID=$this->generate_code($data['referral']['facility_from']);
        $created =  $this->transaction_refer($data,$logID);

    if($created)
    {
        return [
            'code' =>$logID,
            'message' => 'Referral successfully transmitted'
        ];
    }else{

    }
      
    }
    

    public  function transaction_refer(array $data,string $LogID)
    {
        DB::beginTransaction();

        try {
            $referral = [
                'LogID' => $LogID,
                'fhudFrom' => $data['referral']['facility_from'] ?? null,
                'fhudTo' => $data['referral']['facility_to'] ?? null,
                'typeOfReferral' => $data['referral']['type_referral']  ?? null,
                'referralCategory' => $data['referral']['category'] ?? null,
                'referralReason' =>$data['referral']['reason'] ?? null,
                'referralContactPerson' => $data['referral']['contact_person'] ?? null,
                'referralContactPersonDesignation' => $data['referral']['designation'] ?? 'N/A',
                'referringProvider' =>'N/A',
                'referringProviderContactNumber' => $data['referral']['contact_no'] ?? null,
                'otherReasons' =>$data['referral']['other_reason'] ?? null,
                'remarks' =>$data['referral']['remarks'] ?? null,
                'refferalDate' => $data['referral']['refer_date'] ?? null,
                'refferalTime' =>$data['referral']['refer_time'] ?? null,
                'logDate' => date('Y-m-d H:i:s'),
                'created_at' => now(), 
            ];


       /*      
            'referralContactPerson' => $data['referral']['contact_no'] ?? null,
            'referringProvider' => $data['patient_providers']['other_reason'] ?? 'N/A',
            'referringProviderContactNumber' => $data['referral']['other_reason'] ?? null,
            'referralContactPerson' => $data['referral']['other_reason'] ?? null,
            'referralContactPersonDesignation' => $data['referral']['other_reason'] ?? null,
            'refferalDate' => $data['referral']['refer_date'] ?? null,
            'refferalTime' =>$data['referral']['refer_time'] ?? null, 
            'specialinstruct' => $specialinstruct ?? null,
            */
            ReferralInformationModel::create($referral);
            $patient = [
                'LogID'=>$LogID,
                'FamilyID'=>($data['patient']['family_number'])?$data['patient']['family_number']: 0 ,
                'caseNum'=>($data['patient']['case_no'])?(int)$data['patient']['case_no']: 0 ,
                'phicNum'=>($data['patient']['phic_number'])?(int)$data['patient']['phic_number']: 0 ,
                'patientLastName'=>$data['patient']['last_name'],
                'patientFirstName'=>$data['patient']['first_name'],
                'patientMiddlename'=>$data['patient']['middle_name'],
                'patientSuffix'=>($data['patient']['suffix'])?(int)$data['patient']['suffix']: '.' ,
                'patientBirthDate'=>$data['patient']['birthdate'],
                'patientSex'=>$data['patient']['sex'],
                'patientContactNumber'=>$data['patient']['contact_no'],
                'patientReligion'=>$data['patient']['religion'],
                'patientBloodType'=>$data['patient']['blood_type'],
                'patientBloodTypeRH'=>$data['patient']['blood_rh'],
                'patientCivilStatus'=>$data['patient']['civil_status']
            ];

            ReferralPatientInfoModel::create($patient);

            $demographics = [
                'LogID'=>$LogID,
                'patientStreetAddress'=>$data['demographics']['street'],
                'patientBrgyCode'=>$data['demographics']['brgy_code'],
                'patientMundCode'=>$data['demographics']['city_code'],
                'patientProvCode'=>$data['demographics']['prov_code'],
                'patientRegCode'=>$data['demographics']['reg_code'],
                'patientZipCode'=>$data['demographics']['zipcode']
            ];
            ReferralPatientDemoModel::create($demographics);


            $referring_provider=[
                'LogID'=>$LogID,
                'provider_last'=>$data['patient_providers'][0]['provider_last_name'],
                'provider_first'=>$data['patient_providers'][0]['provider_fist_name'],
                'provider_middle'=>$data['patient_providers'][0]['provider_middle_name'],
                'provider_suffix'=>$data['patient_providers'][0]['provider_suffix_name'],
                'provider_type'=>$data['patient_providers'][0]['provider_type'],
            ];
            
            DB::table('referral_provider')->insert($referring_provider);

            $consulting_provider=[
                'LogID'=>$LogID,
                'provider_last'=>$data['patient_providers'][1]['provider_last_name'],
                'provider_first'=>$data['patient_providers'][1]['provider_fist_name'],
                'provider_middle'=>$data['patient_providers'][1]['provider_middle_name'],
                'provider_suffix'=>$data['patient_providers'][1]['provider_suffix_name'],
                'provider_type'=>$data['patient_providers'][1]['provider_type'],
            ];

            DB::table('referral_provider')->insert($consulting_provider);


            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack(); 
            return 'Transaction failed: ' . $e->getMessage();
        }
    }


    public function receive_incoming(array $data)
    {

        

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
    
            if (!$record) {
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
                'remarks' => $record->trackRemarks ?? ''
            ];
    
            $scheduleQuery = null;
            if ($record->hasFollowup === 'Y') {
                $scheduleQuery = DB::table('followup_schedule')
                    ->where('LogID', $logId)
                    ->value('scheduleDateTime');
            }
    
            $medQuery = [];
            if ($record->hasMedicine === 'Y') {
                $medQuery = DB::table('medications')
                    ->select('drugcode', 'generic', 'instruction')
                    ->where('LogID', $logId)
                    ->get();
            }
    
            DB::commit();
    
            return response()->json([
                'dischargeData' => $resultRecord,
                'drugs' => $medQuery,
                'schedule' => $scheduleQuery
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DB transaction failed in " . __METHOD__, [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'last_query' => DB::getQueryLog()
            ]);
    
            return response()->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
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

            if (!$updated) {
                throw new Exception("Failed to update discharge record.");
            }

          
            if ($param['discharge']['hasFollowUp'] === 'Y') {
                $followupInserted = DB::table('referral_followup')->insert($param['followup']); 
                if (!$followupInserted) {
                    throw new Exception("Failed to insert follow-up.");
                }
            }

          
            if ($param['discharge']['hasMedicine'] === 'Y') {
                $medicineInserted = DB::table('referral_medicine')->insert($param['medicine']); // Replace with actual table
                if (!$medicineInserted) {
                    throw new Exception("Failed to insert medicine records.");
                }
            }

            DB::commit();

            return [
                'code' => 200,
                'message' => 'Success!'
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error(sprintf(
                'dischargeTransaction failed: %s in %s on line %d',
                $e->getMessage(), $e->getFile(), $e->getLine()
            ));

            return [
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage()
            ];
        }
    }

}
