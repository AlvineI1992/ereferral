<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DiagnosisHeatmapService
{
    public function report(array $filters, User $user): array
    {
        $query = DB::table('referral_information as referral')
            ->join('referral_track as track', 'track.LogID', '=', 'referral.LogID')
            ->join('ref_facilities as facility', 'facility.hfhudcode', '=', 'referral.fhudTo')
            ->whereBetween('track.dischDate', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->whereNotNull('track.diagnosis')->whereRaw("TRIM(track.diagnosis) <> ''");
        $this->scope($query, $user);

        $diagnoses = (clone $query)->selectRaw('UPPER(TRIM(track.diagnosis)) as diagnosis, COUNT(DISTINCT referral.LogID) as count')
            ->groupByRaw('UPPER(TRIM(track.diagnosis))')->orderByDesc('count')->limit(100)->get();
        if (filled($filters['diagnosis'] ?? null)) {
            $query->whereRaw('UPPER(TRIM(track.diagnosis)) = ?', [mb_strtoupper(trim($filters['diagnosis']))]);
        }

        $facilities = $query->select('facility.hfhudcode as code', 'facility.facility_name as name', 'facility.latitude', 'facility.longitude')
            ->selectRaw('COUNT(DISTINCT referral.LogID) as count')
            ->groupBy('facility.hfhudcode', 'facility.facility_name', 'facility.latitude', 'facility.longitude')
            ->orderByDesc('count')->get()->map(function ($row) {
                $mapped = is_numeric($row->latitude) && is_numeric($row->longitude)
                    && (float) $row->latitude >= 4 && (float) $row->latitude <= 22
                    && (float) $row->longitude >= 116 && (float) $row->longitude <= 127;

                return ['code' => $row->code, 'name' => $row->name, 'count' => (int) $row->count,
                    'latitude' => $mapped ? (float) $row->latitude : null,
                    'longitude' => $mapped ? (float) $row->longitude : null, 'mapped' => $mapped];
            });

        return [
            'facilities' => $facilities->values(),
            'diagnoses' => $diagnoses,
            'total' => $facilities->sum('count'),
            'mapped' => $facilities->where('mapped', true)->sum('count'),
            'unmapped' => $facilities->where('mapped', false)->sum('count'),
        ];
    }

    private function scope(Builder $query, User $user): void
    {
        if (app(ReferralAccessService::class)->isAdministrator($user)) {
            return;
        }
        $id = trim((string) $user->access_id);
        if ($id === '') {
            $query->whereRaw('1 = 0');

            return;
        }
        match (strtoupper(trim((string) $user->access_type))) {
            'EMR' => $query->where('facility.emr_id', $id),
            'HOSP' => $query->where('facility.hfhudcode', $id),
            'CHD' => $query->where('facility.region_code', $id),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
