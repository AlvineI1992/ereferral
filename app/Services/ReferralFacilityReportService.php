<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReferralFacilityReportService
{
    public function patients(array $filters, ?Authenticatable $user): array
    {
        $dateFrom = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $dateTo = CarbonImmutable::parse($filters['date_to'])->endOfDay();

        $query = DB::table('referral_information as referral')
            ->join('ref_facilities as facility', 'facility.hfhudcode', '=', 'referral.fhudTo')
            ->leftJoin('ref_facilities as origin_facility', 'origin_facility.hfhudcode', '=', 'referral.fhudFrom')
            ->leftJoin('referral_patientinfo as patient', 'patient.LogID', '=', 'referral.LogID')
            ->leftJoin('referral_track as track', 'track.LogID', '=', 'referral.LogID')
            ->whereBetween('referral.refferalDate', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $this->applyScope($query, $user);

        if (filled($filters['referring_facility'] ?? null)) {
            $query->where('referral.fhudFrom', $filters['referring_facility']);
        }

        if (filled($filters['referral_facility'] ?? null)) {
            $query->where('referral.fhudTo', $filters['referral_facility']);
        }

        if (filled($filters['provider'] ?? null)) {
            $query->where('facility.emr_id', $filters['provider']);
        }

        if ($filters['group_type'] === 'rhu') {
            $query->where('referral.fhudFrom', $filters['group_code'])
                ->where('origin_facility.facility_type', '17');
        } else {
            $query->where('referral.fhudTo', $filters['group_code']);
        }

        $rows = $query
            ->select([
                'referral.LogID',
                'referral.refferalDate',
                'referral.refferalTime',
                'referral.fhudFrom',
                'referral.fhudTo',
                'patient.patientFirstName',
                'patient.patientMiddlename',
                'patient.patientLastname',
                'origin_facility.facility_name as referring_facility_name',
                'facility.facility_name as referral_facility_name',
                'track.receivedDate',
            ])
            ->orderByDesc('referral.refferalDate')
            ->orderByDesc('referral.refferalTime')
            ->get()
            ->map(function ($row) {
                $patientName = trim(implode(' ', array_filter([
                    $row->patientFirstName,
                    $row->patientMiddlename,
                    $row->patientLastname,
                ])));

                return [
                    'log_id' => (string) $row->LogID,
                    'patient_name' => $patientName !== '' ? $patientName : 'Patient information unavailable',
                    'referring_facility' => (string) ($row->referring_facility_name ?? $row->fhudFrom),
                    'referral_facility' => (string) ($row->referral_facility_name ?? $row->fhudTo),
                    'referral_date' => CarbonImmutable::parse($row->refferalDate)->format('M j, Y'),
                    'received' => filled($row->receivedDate),
                    'received_date' => filled($row->receivedDate)
                        ? CarbonImmutable::parse($row->receivedDate)->format('M j, Y g:i A')
                        : null,
                ];
            })
            ->values()
            ->all();

        return [
            'data' => $rows,
            'total' => count($rows),
        ];
    }

    public function report(array $filters, ?Authenticatable $user): array
    {
        $dateFrom = CarbonImmutable::parse($filters['date_from'] ?? now()->startOfMonth())->startOfDay();
        $dateTo = CarbonImmutable::parse($filters['date_to'] ?? now())->endOfDay();

        $query = DB::table('referral_information as referral')
            ->join('ref_facilities as facility', 'facility.hfhudcode', '=', 'referral.fhudTo')
            ->leftJoin('ref_facilities as origin_facility', 'origin_facility.hfhudcode', '=', 'referral.fhudFrom')
            ->leftJoin('ref_facilitytype as facility_type', 'facility_type.factype_code', '=', 'facility.facility_type')
            ->leftJoin('ref_emr as provider', 'provider.emr_id', '=', 'facility.emr_id')
            ->leftJoin('referral_track as track', 'track.LogID', '=', 'referral.LogID')
            ->whereBetween('referral.refferalDate', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $this->applyScope($query, $user);

        $options = [
            'providers' => (clone $query)
                ->whereNotNull('facility.emr_id')
                ->selectRaw('facility.emr_id as id, provider.emr_name as name')
                ->distinct()
                ->orderBy('provider.emr_name')
                ->get()
                ->map(fn ($row) => ['id' => (string) $row->id, 'name' => (string) ($row->name ?? $row->id)])
                ->values()
                ->all(),
            'referring_facilities' => (clone $query)
                ->whereNotNull('referral.fhudFrom')
                ->selectRaw('referral.fhudFrom as code, origin_facility.facility_name as name')
                ->distinct()
                ->orderBy('origin_facility.facility_name')
                ->get()
                ->map(fn ($row) => ['code' => (string) $row->code, 'name' => (string) ($row->name ?? $row->code)])
                ->values()
                ->all(),
            'referral_facilities' => (clone $query)
                ->selectRaw('referral.fhudTo as code, facility.facility_name as name')
                ->distinct()
                ->orderBy('facility.facility_name')
                ->get()
                ->map(fn ($row) => ['code' => (string) $row->code, 'name' => (string) ($row->name ?? $row->code)])
                ->values()
                ->all(),
        ];

        if (filled($filters['referring_facility'] ?? null)) {
            $query->where('referral.fhudFrom', $filters['referring_facility']);
        }

        if (filled($filters['referral_facility'] ?? null)) {
            $query->where('referral.fhudTo', $filters['referral_facility']);
        }

        if (filled($filters['provider'] ?? null)) {
            $query->where('facility.emr_id', $filters['provider']);
        }

        $trend = (clone $query)
            ->selectRaw('referral.refferalDate as date')
            ->selectRaw('COUNT(DISTINCT referral.LogID) as sent_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN track.LogID IS NOT NULL AND track.receivedDate IS NOT NULL THEN referral.LogID END) as received_count')
            ->groupBy('referral.refferalDate')
            ->orderBy('referral.refferalDate')
            ->get()
            ->keyBy(fn ($row) => CarbonImmutable::parse($row->date)->toDateString());

        $dailyTrend = collect();
        for ($date = $dateFrom; $date->lte($dateTo); $date = $date->addDay()) {
            $dateKey = $date->toDateString();
            $daily = $trend->get($dateKey);
            $dailyTrend->push([
                'date' => $dateKey,
                'label' => $date->format('M j'),
                'sent_count' => (int) ($daily->sent_count ?? 0),
                'received_count' => (int) ($daily->received_count ?? 0),
            ]);
        }

        $rhuRows = (clone $query)
            ->where('origin_facility.facility_type', '17')
            ->selectRaw('referral.fhudFrom as rhu_code, origin_facility.facility_name as rhu_name')
            ->selectRaw('COUNT(DISTINCT referral.LogID) as sent_count')
            ->selectRaw('COUNT(DISTINCT referral.fhudTo) as destination_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN track.LogID IS NOT NULL AND track.receivedDate IS NOT NULL THEN referral.LogID END) as received_count')
            ->groupBy('referral.fhudFrom', 'origin_facility.facility_name')
            ->orderByDesc('sent_count')
            ->orderBy('origin_facility.facility_name')
            ->get()
            ->map(function ($row) {
                $sent = (int) $row->sent_count;
                $received = (int) $row->received_count;

                return [
                    'rhu_code' => (string) $row->rhu_code,
                    'rhu_name' => (string) $row->rhu_name,
                    'destination_count' => (int) $row->destination_count,
                    'sent_count' => $sent,
                    'received_count' => $received,
                    'pending_count' => max($sent - $received, 0),
                    'receipt_rate' => $sent > 0 ? round(($received / $sent) * 100, 1) : 0,
                ];
            })
            ->values();

        $rows = $query
            ->selectRaw('referral.fhudTo as facility_code, facility.facility_name, facility.facility_type as facility_type_code, facility_type.description as facility_type')
            ->selectRaw('COUNT(DISTINCT referral.LogID) as sent_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN origin_facility.facility_type = '17' THEN referral.LogID END) as rhu_sent_count")
            ->selectRaw('COUNT(DISTINCT CASE WHEN track.LogID IS NOT NULL AND track.receivedDate IS NOT NULL THEN referral.LogID END) as received_count')
            ->groupBy('referral.fhudTo', 'facility.facility_name', 'facility.facility_type', 'facility_type.description')
            ->orderByDesc('sent_count')
            ->orderBy('facility.facility_name')
            ->get()
            ->map(function ($row) {
                $sent = (int) $row->sent_count;
                $received = (int) $row->received_count;

                return [
                    'facility_code' => (string) $row->facility_code,
                    'facility_name' => (string) $row->facility_name,
                    'facility_type_code' => (string) ($row->facility_type_code ?? ''),
                    'facility_type' => (string) ($row->facility_type ?? 'Unspecified'),
                    'sent_count' => $sent,
                    'rhu_sent_count' => (int) $row->rhu_sent_count,
                    'received_count' => $received,
                    'pending_count' => max($sent - $received, 0),
                    'receipt_rate' => $sent > 0 ? round(($received / $sent) * 100, 1) : 0,
                ];
            })
            ->values();

        $sentTotal = (int) $rows->sum('sent_count');
        $receivedTotal = (int) $rows->sum('received_count');
        $periodDays = max($dateFrom->diffInDays($dateTo->startOfDay()) + 1, 1);
        $busiestDay = $dailyTrend->sortByDesc('sent_count')->first();

        return [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'provider' => $filters['provider'] ?? null,
                'referring_facility' => $filters['referring_facility'] ?? null,
                'referral_facility' => $filters['referral_facility'] ?? null,
            ],
            'summary' => [
                'facilities' => $rows->count(),
                'rhu_facilities' => $rhuRows->count(),
                'sent_count' => $sentTotal,
                'rhu_sent_count' => (int) $rows->sum('rhu_sent_count'),
                'received_count' => $receivedTotal,
                'pending_count' => max($sentTotal - $receivedTotal, 0),
                'receipt_rate' => $sentTotal > 0 ? round(($receivedTotal / $sentTotal) * 100, 1) : 0,
                'average_daily_referrals' => round($sentTotal / $periodDays, 1),
                'busiest_day' => ($busiestDay['sent_count'] ?? 0) > 0 ? $busiestDay['label'] : null,
                'busiest_day_count' => (int) ($busiestDay['sent_count'] ?? 0),
            ],
            'trend' => $dailyTrend->all(),
            'data' => $rows->all(),
            'rhu_data' => $rhuRows->all(),
            'options' => $options,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function applyScope(Builder $query, ?Authenticatable $user): void
    {
        $accessType = strtoupper(trim((string) ($user?->access_type ?? '')));
        $accessId = trim((string) ($user?->access_id ?? ''));

        if (! in_array($accessType, ['EMR', 'CHD', 'HOSP'], true) && $user && method_exists($user, 'getRoleNames')) {
            $accessType = match (strtolower((string) ($user->getRoleNames()->first() ?? ''))) {
                'emr' => 'EMR',
                'region' => 'CHD',
                'hospital' => 'HOSP',
                default => '',
            };
        }

        if ($accessType === '') {
            return;
        }

        if ($accessId === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($accessType) {
            'EMR' => $query->where('facility.emr_id', $accessId),
            'CHD' => $query->where('facility.region_code', $accessId),
            'HOSP' => $query->where('facility.hfhudcode', $accessId),
            default => null,
        };
    }
}
