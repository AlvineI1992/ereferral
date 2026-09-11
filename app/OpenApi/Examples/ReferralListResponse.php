<?php

namespace App\OpenApi\Examples;

use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

class ReferralListResponse
{
    public static function example(): array
    {
        return [[
            'LogID' => 'REF-EXAMPLE-001',
            'referral_origin_code' => 'FAC-001',
            'referral_origin_name' => 'Example Rural Health Unit',
            'referral_destination_code' => 'FAC-002',
            'referral_reason' => 'Further evaluation',
            'referral_patient' => 'EXAMPLE PATIENT',
            'referral_patient_sex' => 'F',
            'referral_patSex' => 'Female',
            'referral_date' => '09/11/2026',
            'referral_time' => '12:00 PM',
            'referral_category' => 'Routine',
            'referral_contact_person' => 'Example Coordinator',
            'referral_contact_person_designation' => 'Nurse',
            'referral_remarks' => null,
            'referring_type' => 'Consultation',
            'referring_provider' => 'Example Provider',
            'patient_pan' => null,
            'contact_number' => null,
        ]];
    }

    public static function schema(): ArrayType
    {
        $item = new ObjectType;
        $nonNullable = ['LogID', 'referral_patient', 'referral_patient_sex', 'referral_patSex', 'referral_date', 'referral_time'];
        foreach (self::example()[0] as $field => $value) {
            $item->addProperty($field, (new StringType)->nullable(! in_array($field, $nonNullable, true))->example($value));
        }
        $item->setRequired(array_keys(self::example()[0]));

        return (new ArrayType)->setItems($item)->example(self::example());
    }
}
