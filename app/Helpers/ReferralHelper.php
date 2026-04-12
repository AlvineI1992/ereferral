<?php

namespace App\Helpers;

use App\Models\RefBarangayModel;
use App\Models\RefCityModel;
use App\Models\RefFacilitytypeModel;
use App\Models\RefProvinceModel;
use App\Models\RefRegionModel;

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
            ['code' => 'OTHER', 'description' => 'Other'],
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
            ['code' => 'OTHER', 'description' => 'Other'],
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

    public static function getReferralTypebyCode($type = null)
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

    public static function getCivilStatuses()
    {
        return [
            ['code' => 'A', 'description' => 'Annulled'],
            ['code' => 'C', 'description' => 'Co-Habitation'],
            ['code' => 'D', 'description' => 'Divorced'],
            ['code' => 'M', 'description' => 'Married'],
            ['code' => 'N', 'description' => 'Not Stated'],
            ['code' => 'S', 'description' => 'Single'],
            ['code' => 'U', 'description' => 'Unknown'],
            ['code' => 'W', 'description' => 'Widowed'],
            ['code' => 'X', 'description' => 'Legally Separated'],
            ['code' => 'Y', 'description' => 'Separated (in Fact)'],
        ];
    }

    public static function getCivilStatusByCode(?string $code = null)
    {
        $civilStatuses = self::getCivilStatuses();

        if ($code) {
            return collect($civilStatuses)->firstWhere('code', strtoupper(trim($code))) ?? null;
        }

        return $civilStatuses;
    }

    public static function getCivilStatusDescription(?string $code): ?string
    {
        return self::getCivilStatusByCode($code)['description'] ?? null;
    }

    public static function getAcceptedCivilStatusInputs(): array
    {
        return [
            'A', 'C', 'D', 'M', 'N', 'S', 'U', 'W', 'X', 'Y',
            'Annulled',
            'Annuled',
            'Co-Habitation',
            'Cohabitation',
            'Divorced',
            'Legally Separated',
            'Married',
            'Not Stated',
            'Separated',
            'Separated (in Fact)',
            'Single',
            'Unknown',
            'Widowed',
        ];
    }

    public static function normalizeCivilStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $value = trim($status);

        if ($value === '') {
            return null;
        }

        $normalized = strtoupper($value);

        if (self::getCivilStatusByCode($normalized)) {
            return $normalized;
        }

        return match ($normalized) {
            'ANNULLED', 'ANNULED' => 'A',
            'CO-HABITATION', 'COHABITATION' => 'C',
            'DIVORCED' => 'D',
            'MARRIED' => 'M',
            'NOT STATED' => 'N',
            'SINGLE' => 'S',
            'UNKNOWN' => 'U',
            'WIDOWED' => 'W',
            'LEGALLY SEPARATED' => 'X',
            'SEPARATED', 'SEPARATED (IN FACT)' => 'Y',
            default => null,
        };
    }

    /**
     * Get allowed referral statuses.
     * If $status is provided, validate it and throw an exception if invalid.
     *
     * @throws \InvalidArgumentException
     */
    public static function getReferralStatus(?string $status = null): array
    {
        $type_array = [
            ['code' => 'ADMIT', 'description' => 'Admitted'],
            ['code' => 'DISCH', 'description' => 'Managed and discharged'],
            ['code' => 'OBSRV', 'description' => 'Observation'],
            ['code' => 'REFER', 'description' => 'Referred to another facility'],
            ['code' => 'RETUR', 'description' => 'Returned to originating facility'],
        ];

        // If no status provided, return the full list
        if (is_null($status)) {
            return $type_array;
        }

        // Validate status
        $allowed_codes = array_column($type_array, 'code');
        if (! in_array(strtoupper($status), $allowed_codes, true)) {
            throw new \InvalidArgumentException("Invalid referral status: {$status}");
        }

        return $type_array;
    }

    public static function getRegion($id)
    {
        $data = RefRegionModel::find($id);

        return $data ? $data->regname : null;
    }

    public static function getProvince($id)
    {
        $data = RefProvinceModel::find($id);

        return $data ? $data->provname : null;
    }

    public static function getCity($id)
    {
        $data = RefCityModel::find($id);

        return $data ? $data->cityname : null;
    }

    public static function getBarangay($id)
    {
        $data = RefBarangayModel::find($id);

        return $data ? $data->bgyname : null;
    }

    public static function getFacilityType($id)
    {
        $zero = self::addLeadingZero($id);
        $data = RefFacilitytypeModel::find($zero);

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
            return '0'.$input;
        }

        // If input is a string that is 1–9
        if (is_string($input) && preg_match('/^\d$/', $input)) {
            return '0'.$input;
        }

        // For text strings, add leading zeros to standalone 1–9
        if (is_string($input)) {
            return preg_replace('/\b([1-9])\b/', '0$1', $input);
        }

        // Otherwise return input as-is
        return $input;
    }
}
