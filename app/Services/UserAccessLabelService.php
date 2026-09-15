<?php

namespace App\Services;

use App\Models\RefEmrModel;
use App\Models\RefFacilitiesModel;
use App\Models\RefRegionModel;
use App\Models\User;

class UserAccessLabelService
{
    public function resolve(User $user): string
    {
        $roles = $user->getRoleNames()->map(fn ($role) => strtolower($role));

        if ($user->isSuperAdministrator() || $roles->contains('admin') || $roles->contains('super-admin')) {
            return 'Administrator';
        }

        $accessId = trim((string) $user->access_id);

        if ($accessId === '') {
            return 'General access';
        }

        return match (strtoupper((string) $user->access_type)) {
            'CHD' => RefRegionModel::query()->where('regcode', $accessId)->value('regname') ?: $accessId,
            'HOSP' => RefFacilitiesModel::query()->where('hfhudcode', $accessId)->value('facility_name') ?: $accessId,
            'EMR' => RefEmrModel::query()->where('emr_id', $accessId)->value('emr_name') ?: $accessId,
            default => $accessId,
        };
    }
}
