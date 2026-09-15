<?php

namespace App\Services;

use App\Models\ReferralInformationModel;
use App\Models\RefFacilitiesModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReferralAccessService
{
    public function authorizeReferral(?User $user, string $logId): ReferralInformationModel
    {
        $query = ReferralInformationModel::query()->whereKey($logId);
        $this->scopeReferrals($query, $user);

        $referral = $query->first();

        if (! $referral) {
            throw new AccessDeniedHttpException('You do not have access to this referral.');
        }

        return $referral;
    }

    public function authorizeFacility(?User $user, string $hfhudcode): void
    {
        if ($this->isAdministrator($user)) {
            return;
        }

        $query = RefFacilitiesModel::query()->where('hfhudcode', $hfhudcode);
        $this->scopeFacilities($query, $user);

        if (! $query->exists()) {
            throw new AccessDeniedHttpException('You do not have access to this facility.');
        }
    }

    public function scopeReferrals(Builder $query, ?User $user): void
    {
        if ($this->isAdministrator($user)) {
            return;
        }

        $accessId = trim((string) ($user?->access_id ?? ''));
        $accessType = strtoupper(trim((string) ($user?->access_type ?? '')));

        if ($accessId === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $participantQuery) use ($accessId, $accessType) {
            match ($accessType) {
                'HOSP' => $participantQuery
                    ->where('fhudFrom', $accessId)
                    ->orWhere('fhudTo', $accessId),
                'EMR' => $participantQuery
                    ->whereHas('facility_from', fn (Builder $facility) => $facility->where('emr_id', $accessId))
                    ->orWhereHas('facility_to', fn (Builder $facility) => $facility->where('emr_id', $accessId)),
                'CHD' => $participantQuery
                    ->whereHas('facility_from', fn (Builder $facility) => $facility->where('region_code', $accessId))
                    ->orWhereHas('facility_to', fn (Builder $facility) => $facility->where('region_code', $accessId)),
                default => $participantQuery->whereRaw('1 = 0'),
            };
        });
    }

    public function isAdministrator(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdministrator()) {
            return true;
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role));

        return $roles->contains('admin') || $roles->contains('super-admin');
    }

    private function scopeFacilities(Builder $query, ?User $user): void
    {
        $accessId = trim((string) ($user?->access_id ?? ''));
        $accessType = strtoupper(trim((string) ($user?->access_type ?? '')));

        if ($accessId === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($accessType) {
            'HOSP' => $query->where('hfhudcode', $accessId),
            'EMR' => $query->where('emr_id', $accessId),
            'CHD' => $query->where('region_code', $accessId),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
