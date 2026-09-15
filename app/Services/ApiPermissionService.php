<?php

namespace App\Services;

use App\Models\User;

class ApiPermissionService
{
    public const ENDPOINTS = [
        'GET api/referrals/journey' => ['permission' => 'referral journey read', 'legacy' => null],
        'POST api/referrals/forward' => ['permission' => 'referral forward', 'legacy' => null],
        'POST api/cancel-referral' => ['permission' => 'referral cancel', 'legacy' => null],
        'POST api/docs/api/referral' => ['permission' => 'legacy referral documentation create', 'legacy' => 'reference read'],
        'POST api/docs/api/reference' => ['permission' => 'legacy reference documentation create', 'legacy' => 'reference read'],
        'GET api/generate_code/{hfhudcode}' => ['permission' => 'reference code generate', 'legacy' => 'reference read'],
        'GET api/demographics' => ['permission' => 'demographics read', 'legacy' => 'reference read'],
        'GET api/region/{id}' => ['permission' => 'region read', 'legacy' => 'reference read'],
        'GET api/province/{id}' => ['permission' => 'province read', 'legacy' => 'reference read'],
        'GET api/city/{id}' => ['permission' => 'city read', 'legacy' => 'reference read'],
        'GET api/barangay/{id}' => ['permission' => 'barangay read', 'legacy' => 'reference read'],
        'GET api/reason-referral' => ['permission' => 'referral reasons list', 'legacy' => 'reference read'],
        'GET api/reason-referral-code/{code}' => ['permission' => 'referral reason read', 'legacy' => 'reference read'],
        'GET api/referral-type' => ['permission' => 'referral types list', 'legacy' => 'reference read'],
        'GET api/referral-type-code/{code}' => ['permission' => 'referral type read', 'legacy' => 'reference read'],
        'GET api/facility/{id}' => ['permission' => 'facility referrals list', 'legacy' => 'reference read'],
        'GET api/referral-network/recommendations/{hfhudcode}' => ['permission' => 'referral network recommendations read', 'legacy' => 'reference read'],
        'POST api/refer_patient' => ['permission' => 'patient referral create', 'legacy' => 'referrals write'],
        'GET api/referral-attachments/{attachment}/download' => ['permission' => 'referral attachment download', 'legacy' => 'referrals read'],
        'POST api/incoming/fhir' => ['permission' => 'FHIR referral create', 'legacy' => 'referrals write'],
        'GET api/incoming/fhir/{LogID}' => ['permission' => 'FHIR referral read', 'legacy' => 'referrals read'],
        'GET api/get-referral-information/{LogID}' => ['permission' => 'referral information read', 'legacy' => 'referrals read'],
        'GET api/get-referral-list/{fhudcode}' => ['permission' => 'referral list read', 'legacy' => 'referrals read'],
        'POST api/received' => ['permission' => 'referral receive', 'legacy' => 'referrals write'],
        'POST api/admit' => ['permission' => 'referral admit', 'legacy' => 'referrals write'],
        'GET api/get-discharged-data/{LogID}' => ['permission' => 'discharge data read', 'legacy' => 'referrals read'],
        'GET api/get-accredited-facilities' => ['permission' => 'accredited facilities list', 'legacy' => 'reference read'],
        'GET api/blood-types' => ['permission' => 'blood types list', 'legacy' => 'reference read'],
        'GET api/religions' => ['permission' => 'religions list', 'legacy' => 'reference read'],
        'GET api/bed-trackers' => ['permission' => 'bed trackers list', 'legacy' => 'beds read'],
        'GET api/bed-trackers/facilities' => ['permission' => 'bed tracker facilities list', 'legacy' => 'beds read'],
        'GET api/bed-trackers/{id}' => ['permission' => 'bed tracker read', 'legacy' => 'beds read'],
        'POST api/bed-trackers' => ['permission' => 'bed trackers create', 'legacy' => 'beds write'],
        'PUT api/bed-trackers/{id}' => ['permission' => 'bed tracker update', 'legacy' => 'beds write'],
        'DELETE api/bed-trackers/{id}' => ['permission' => 'bed tracker delete', 'legacy' => 'beds write'],
        'GET api/referral-status' => ['permission' => 'referral statuses list', 'legacy' => 'referrals read'],
        'POST api/referral-status/update' => ['permission' => 'referral status update', 'legacy' => 'referrals write'],
        'GET api/referral-status/patient' => ['permission' => 'patient referral statuses list', 'legacy' => 'referrals read'],
    ];

    public static function permissions(): array
    {
        return array_column(self::ENDPOINTS, 'permission');
    }

    public static function endpointFor(string $permission): ?string
    {
        foreach (self::ENDPOINTS as $endpoint => $definition) {
            if ($definition['permission'] === $permission) {
                return str_replace(' api/', ' /api/', $endpoint);
            }
        }
        return null;
    }

    public function allows(User $user, string $permission): bool
    {
        return in_array($permission, self::permissions(), true)
            && ($user->isSuperAdministrator() || $user->checkPermissionTo($permission, 'api'));
    }
}
