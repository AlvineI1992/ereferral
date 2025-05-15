<?php

namespace App\Helpers;

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
}
