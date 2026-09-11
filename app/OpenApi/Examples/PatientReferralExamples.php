<?php

namespace App\OpenApi\Examples;

final class PatientReferralExamples
{
    public static function minimal(): array
    {
        return [
            'referral' => [
                'facility_from' => 'DOH000000000007520',
                'facility_to' => 'DOH000000000005280',
                'type_referral' => 'TRANS',
                'category' => 'ER',
                'reason' => 'SEFTA',
                'contact_person' => 'Receiving Personnel',
                'refer_date' => '2026-09-11',
                'refer_time' => '13:00',
            ],
            'patient' => [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'birthdate' => '1990-01-01',
                'sex' => 'M',
                'religion' => 'CATHO',
            ],
            'demographics' => [
                'street' => '123 Sample Street',
                'brgy_code' => '043405061',
                'city_code' => '043405',
                'prov_code' => '0434',
                'reg_code' => '04',
                'zipcode' => '4027',
            ],
            'clinical' => [
                'diagnosis' => ['Acute appendicitis'],
                'chief_complaint' => 'Severe abdominal pain',
                'history' => null,
                'findings' => null,
            ],
            'ICD' => ['K35.80'],
            'vital_signs' => [
                'BP' => null,
                'temp' => null,
                'HR' => null,
                'RR' => null,
                'O2_sats' => null,
                'weight' => null,
                'height' => null,
            ],
            'patient_providers' => [
                [
                    'provider_last' => 'Mendoza',
                    'provider_first' => 'Ana',
                    'provider_middle' => null,
                    'provider_suffix' => null,
                    'ProviderContactNo' => null,
                    'provider_type' => 'REFER',
                ],
            ],
        ];
    }

    public static function complete(): array
    {
        return array_replace_recursive(self::minimal(), [
            'referral' => [
                'phic_pan' => 'PAN-123456',
                'contact_no' => '09171234567',
                'other_reason' => null,
                'remarks' => 'Requires urgent transfer.',
                'designation' => 'Emergency Room Nurse',
            ],
            'patient' => [
                'family_number' => 'FAM-0001',
                'phic_number' => '123456789012',
                'case_no' => '2026-000001',
                'middle_name' => 'Santos',
                'suffix' => null,
                'civil_status' => 'S',
                'contact_no' => '09179876543',
                'blood_type' => 'O',
                'blood_rh' => '+',
            ],
            'clinical' => [
                'history' => 'Abdominal pain started eight hours ago.',
                'findings' => 'Right lower quadrant tenderness.',
                'physical_examination' => 'Patient is conscious and coherent.',
            ],
            'vital_signs' => [
                'BP' => '120/80',
                'temp' => '37.0',
                'HR' => '88',
                'RR' => '18',
                'O2_sats' => '98',
                'weight' => '65',
                'height' => '170',
            ],
            'patient_providers' => [
                [
                    'provider_last' => 'Mendoza',
                    'provider_first' => 'Ana',
                    'provider_middle' => 'Reyes',
                    'provider_suffix' => null,
                    'ProviderContactNo' => '09170000000',
                    'provider_type' => 'REFER',
                ],
            ],
        ]);
    }

    public static function all(): array
    {
        return [self::complete(), self::minimal()];
    }

    public static function successResponse(): array
    {
        return [
            'code' => 'HOSP-20260911130000',
            'message' => 'Referral successfully transmitted',
            'data' => self::complete(),
        ];
    }

    public static function facilityErrorResponse(): array
    {
        return [
            'error' => 'Referring facility does not exist!',
        ];
    }
}
