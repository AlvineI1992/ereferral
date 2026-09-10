<?php

namespace App\Services;

use App\Helpers\ReferralHelper;
use Illuminate\Database\Eloquent\Builder;

class IncomingDashboardService
{
    public function summarize(Builder $query): array
    {
        $totals = (clone $query)
            ->selectRaw('COUNT(*) as totalIncoming')
            ->selectRaw('SUM(CASE WHEN DATE(refferalDate) = ? THEN 1 ELSE 0 END) as todayIncoming', [now()->toDateString()])
            ->selectRaw("SUM(CASE WHEN referralCategory = 'ER' THEN 1 ELSE 0 END) as emergencyCount")
            ->selectRaw("SUM(CASE WHEN referralCategory = 'OPD' THEN 1 ELSE 0 END) as outpatientCount")
            ->selectRaw('COUNT(DISTINCT fhudTo) as receivingFacilities')
            ->first();

        return [
            'totalIncoming' => (int) ($totals->totalIncoming ?? 0),
            'todayIncoming' => (int) ($totals->todayIncoming ?? 0),
            'emergencyCount' => (int) ($totals->emergencyCount ?? 0),
            'outpatientCount' => (int) ($totals->outpatientCount ?? 0),
            'receivingFacilities' => (int) ($totals->receivingFacilities ?? 0),
            'topReasons' => (clone $query)
                ->selectRaw('referralReason, COUNT(*) as aggregate')
                ->groupBy('referralReason')
                ->orderByDesc('aggregate')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $reason = ReferralHelper::getReferralReasonbyCode($item->referralReason);

                    return [
                        'code' => $item->referralReason,
                        'label' => $reason['description'] ?? ($item->referralReason === 'OTHER' ? 'Other reason' : $item->referralReason),
                        'count' => (int) $item->aggregate,
                    ];
                })
                ->values(),
            'topProvinces' => $this->locationSummary(
                (clone $query)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_province as province', 'demo.patientProvCode', '=', 'province.provcode'),
                'demo.patientProvCode',
                'province.provname'
            ),
            'topCities' => $this->locationSummary(
                (clone $query)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_city as city', 'demo.patientMundCode', '=', 'city.citycode'),
                'demo.patientMundCode',
                'city.cityname'
            ),
            'topBarangays' => $this->locationSummary(
                (clone $query)
                    ->leftJoin('referral_patientdemo as demo', 'referral_information.LogID', '=', 'demo.LogID')
                    ->leftJoin('ref_barangay as barangay', 'demo.patientBrgyCode', '=', 'barangay.bgycode'),
                'demo.patientBrgyCode',
                'barangay.bgyname'
            ),
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    private function locationSummary(Builder $query, string $codeColumn, string $nameColumn): array
    {
        return $query
            ->selectRaw("{$codeColumn} as code, COALESCE(NULLIF({$nameColumn}, ''), 'Unspecified') as label, COUNT(DISTINCT referral_information.LogID) as aggregate")
            ->whereNotNull($codeColumn)
            ->groupBy($codeColumn, $nameColumn)
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get()
            ->map(fn ($item) => [
                'code' => $item->code,
                'label' => $item->label,
                'count' => (int) $item->aggregate,
            ])
            ->values()
            ->all();
    }
}
