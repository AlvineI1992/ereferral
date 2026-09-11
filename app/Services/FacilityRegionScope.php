<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FacilityRegionScope
{
    public function apply(Builder $query, ?User $user): Builder
    {
        if (! $this->isRegional($user)) {
            return $query;
        }
        $codes = $this->codes($user?->access_id);

        return $codes === [] ? $query->whereRaw('1 = 0') : $query->whereIn('ref_facilities.region_code', $codes);
    }

    public function authorizeRegion(?User $user, mixed $region): void
    {
        if (! $this->isRegional($user)) {
            return;
        }
        abort_unless(array_intersect($this->codes($user?->access_id), $this->codes($region)) !== [], 403,
            'You can only manage facilities within your assigned region.');
    }

    private function isRegional(?User $user): bool
    {
        return strtoupper(trim((string) $user?->access_type)) === 'CHD';
    }

    private function codes(mixed $value): array
    {
        $code = trim((string) $value);
        if (! preg_match('/\A\d{1,2}\z/', $code) || (int) $code === 0) {
            return [];
        }

        return array_values(array_unique([str_pad($code, 2, '0', STR_PAD_LEFT), (string) (int) $code]));
    }
}
