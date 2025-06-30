<?php

namespace App\Helpers;

use App\Models\RefRegionModel;
use App\Models\RefProvinceModel;
use App\Models\RefCityModel;
use App\Models\RefBarangayModel;

use App\Models\RefFacilitytypeModel;

class ReferralHelper
{
 
    public static function getReferralReasons()
    {
        return [
            ['code' => 'NOEQP', 'description' => 'No equipment available'],
            ['code' => 'NODOC', 'description' => 'No doctor available'],
            ['code' => 'NOPRO', 'description' => 'No procedure available'],
            ['code' => 'NOLAB', 'description' => 'No laboratory available'],
            ['code' => 'NOROM', 'description' => 'No room available'],
            ['code' => 'SEASO', 'description' => 'Seek advise/second opinion'],
            ['code' => 'SESPE', 'description' => 'Seek specialized evaluation'],
            ['code' => 'SEFTA', 'description' => 'Seek further treatment appropriate to the case'],
        ];
    }

    public static function getReferralReasonbyCode($code = null)
    {
        $reasons = [
            ['code' => 'NOEQP', 'description' => 'No equipment available'],
            ['code' => 'NODOC', 'description' => 'No doctor available'],
            ['code' => 'NOPRO', 'description' => 'No procedure available'],
            ['code' => 'NOLAB', 'description' => 'No laboratory available'],
            ['code' => 'NOROM', 'description' => 'No room available'],
            ['code' => 'SEASO', 'description' => 'Seek advise/second opinion'],
            ['code' => 'SESPE', 'description' => 'Seek specialized evaluation'],
            ['code' => 'SEFTA', 'description' => 'Seek further treatment appropriate to the case'],
        ];

        if ($code) {
            return collect($reasons)->firstWhere('code', strtoupper($code)) ?? null;
        }

        return $reasons;
    }

    public static function getReferralType()
    {
        return [
            ['code' => 'TRANS', 'description' => 'Transfer'],
            ['code' => 'CONSU', 'description' => 'Consultation'],
            ['code' => 'DIAGT', 'description' => 'Diagnostic Test'],
            ['code' => 'OTHER', 'description' => 'Others'],
        ];
    }

    public static function getReferralTypebyCode($type=null)
    {
        $type_array = [
            ['code' => 'TRANS', 'description' => 'Transfer'],
            ['code' => 'CONSU', 'description' => 'Consultation'],
            ['code' => 'DIAGT', 'description' => 'Diagnostic Test'],
            ['code' => 'OTHER', 'description' => 'Others'],
        ];
        if ($type) {
            return collect($type_array)->firstWhere('code', strtoupper($type)) ?? null;
        }

        return $type_array;
    }

    public static function getRegion($id)
    {
        $data =RefRegionModel::find($id);
        return $data ? $data->regname : null;
    }

    public static function getProvince($id)
    {
        $data =RefProvinceModel::find($id);
        return $data ? $data->provname : null;
    }

    public static function getCity($id)
    {
        $data =RefCityModel::find($id);
        return $data ? $data->cityname : null;
    }

    public static function getBarangay($id)
    {
        $data =RefBarangayModel::find($id);
        return $data ? $data->bgyname : null;
    }

    public static function getFacilityType($id)
    {
        $zero  = self::addLeadingZero($id);
        $data =RefFacilitytypeModel::find($zero);
        return $data ? $data->description : null;
    }

    public static function addLeadingZero($input)
    {
        // If input is an array, map each item recursively
        if (is_array($input)) {
            return array_map([self::class, 'addLeadingZero'], $input);
        }
    
        // If input is numeric and 1–9
        if (is_numeric($input) && $input >= 1 && $input <= 9) {
            return '0' . $input;
        }
    
        // If input is a string that is 1–9
        if (is_string($input) && preg_match('/^\d$/', $input)) {
            return '0' . $input;
        }
    
        // For text strings, add leading zeros to standalone 1–9
        if (is_string($input)) {
            return preg_replace('/\b([1-9])\b/', '0$1', $input);
        }
    
        // Otherwise return input as-is
        return $input;
    }
}
