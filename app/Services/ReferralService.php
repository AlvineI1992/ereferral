<?php

namespace App\Services;

use App\Models\ReferralInformationModel;
use App\Models\ReferralClinicalModel;
use App\Models\ReferralPatientDemoModel;
use App\Models\ReferralPatientInfoModel;
use Illuminate\Support\Facades\DB;
use App\Models\RefFacilitiesModel;


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

    private function _check_logid($id)
    {
        return ReferralInformationModel::where('LogID', $id)->exists();
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

        if(!$this->_check_fhud($data['fhudFrom']))
        {
            return response()->json([
                'code'=>'302',
                'message' => 'Referring facility does not exist!',
            ]);
        }
        
        if(!$this->_check_fhud($data['fhudTo']))
        {
            return response()->json([
                'code'=>'302',
                'message' => 'Referral facility does not exist!',
            ]);
        }

        $data['LogID'] = $this->generate_code($data['fhudFrom']);
        $arr = $data; // Copy all data to $arr
        $arr['LogID'] = $data['LogID']; // Ensure LogID is set correctly
         $this->transaction_refer($arr);

    }

    private  function transaction_refer($data)
    {
        DB::beginTransaction();

        try {
            ReferralInformationModel::create($data);

           

            DB::commit(); 

        } catch (Exception $e) {
            DB::rollBack(); 
          //  echo 'Transaction failed: ' . $e->getMessage();
        }
    }
}
