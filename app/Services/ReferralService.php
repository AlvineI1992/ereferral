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

    
        return [
            'code' =>$logID,
            'message' => 'Referral successfully transmitted',
            'data' =>$created,
        ];
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
                'referralReason' =>$data['referral']['reason'] ?? null,
                'otherReasons' =>$data['referral']['other_reason'] ?? null,
                'remarks' =>$data['referral']['other_reason'] ?? null,
                'referringProvider' => $data['referral']['other_reason'] ?? null,
                'referralCategory' => $data['referral']['other_reason'] ?? null,
                'referringProviderContactNumber' => $data['referral']['other_reason'] ?? null,
                'referralContactPerson' => $data['referral']['other_reason'] ?? null,
                'referralContactPersonDesignation' => $data['referral']['other_reason'] ?? null,
                'refferalDate' => $data['referral']['refer_date'] ?? null,
                'refferalTime' =>$data['referral']['refer_time'] ?? null,
                'logDate' => $logDate ?? null,
                'tdcode' => $tdcode ?? null,
                'licno' => $licno ?? null,
                'patientPan' => $data['referral']['refer_date'],
                'specialinstruct' => $specialinstruct ?? null,
                'created_at' => now(), // or Carbon::now(), depending on your use case
            ];
            
            $patient = [
                'LogID'=>$LogID,
                'FamilyID'=>$data['patient']['family_number'],
                'caseNum'=>$data['patient']['case_no'],
                'phicNum'=>$data['patient']['phic_number'],
                'patientLastName'=>$data['patient']['last_name'],
                'patientFirstName'=>$data['patient']['first_name'],
                'patientMiddlename'=>$data['patient']['middle_name'],
                'patientSuffix'=>$data['patient']['suffix'],
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

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack(); 
            return 'Transaction failed: ' . $e->getMessage();
        }
    }
}
