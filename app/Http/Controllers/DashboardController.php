<?php

namespace App\Http\Controllers;

use App\Helpers\ReferralHelper;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private array $tableCache = [];

    private array $columnCache = [];

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $summarySnapshot = $this->referralSnapshot($user);

        return Inertia::render('dashboard', [
            'summary' => [
                [
                    'key' => 'today',
                    'label' => "Today's referrals",
                    'value' => $summarySnapshot['today'],
                    'detail' => 'Captured within the last operating day.',
                ],
                [
                    'key' => 'queue',
                    'label' => 'Awaiting action',
                    'value' => $summarySnapshot['queue'],
                    'detail' => 'Referrals without a tracking update.',
                ],
                [
                    'key' => 'emergency',
                    'label' => 'Emergency cases',
                    'value' => $summarySnapshot['emergency'],
                    'detail' => 'Cases tagged as emergency referrals.',
                ],
                [
                    'key' => 'tracked',
                    'label' => 'Tracked referrals',
                    'value' => $summarySnapshot['tracked'],
                    'detail' => 'Records that already have follow-through activity.',
                ],
            ],
            'activity' => $this->buildActivity($user),
            'recentReferrals' => $this->buildRecentReferrals($user),
            'topReasons' => $this->buildTopReasons($user),
            'network' => $this->buildNetwork($user),
            'bedSummary' => $this->buildBedSummary($user),
            'scope' => array_merge(
                $this->resolveScope($user),
                [
                    'totalReferrals' => $summarySnapshot['total'],
                    'trackedReferrals' => $summarySnapshot['tracked'],
                ]
            ),
            'quickActions' => $this->quickActions(),
            'generatedAt' => now()->toIso8601String(),
        ]);
    }

    private function referralSnapshot(?Authenticatable $user): array
    {
        $baseQuery = $this->newReferralBaseQuery($user);

        if (! $baseQuery) {
            return [
                'today' => 0,
                'queue' => 0,
                'emergency' => 0,
                'tracked' => 0,
                'total' => 0,
            ];
        }

        $timestampColumn = $this->referralTimestampColumn();
        $today = 0;

        if ($timestampColumn) {
            $today = (clone $baseQuery)
                ->whereDate($timestampColumn, Carbon::today()->toDateString())
                ->count('ri.LogID');
        }

        $queue = 0;
        $tracked = 0;

        if ($this->hasColumns('referral_track', ['LogID'])) {
            $queue = (clone $baseQuery)
                ->leftJoin('referral_track as rt', 'rt.LogID', '=', 'ri.LogID')
                ->whereNull('rt.LogID')
                ->count('ri.LogID');

            $tracked = (clone $baseQuery)
                ->join('referral_track as rt', 'rt.LogID', '=', 'ri.LogID')
                ->count('ri.LogID');
        }

        $emergency = 0;
        if ($this->hasColumn('referral_information', 'referralCategory')) {
            $emergency = (clone $baseQuery)
                ->where('ri.referralCategory', 'ER')
                ->count('ri.LogID');
        }

        return [
            'today' => $today,
            'queue' => $queue,
            'emergency' => $emergency,
            'tracked' => $tracked,
            'total' => (clone $baseQuery)->count('ri.LogID'),
        ];
    }

    private function buildBedSummary(?Authenticatable $user): array
    {
        if (! $this->hasColumns('bed_trackers', [
            'facility_hfhudcode',
            'bed_type',
            'total_beds',
            'occupied_beds',
            'reserved_beds',
        ]) || ! $this->hasColumns('ref_facilities', ['hfhudcode'])) {
            return [
                'totals' => [
                    'totalBeds' => 0,
                    'occupiedBeds' => 0,
                    'reservedBeds' => 0,
                    'availableBeds' => 0,
                    'occupancyRate' => 0,
                    'facilitiesReporting' => 0,
                    'bedTypesTracked' => 0,
                ],
                'byFacility' => [],
                'byRegion' => [],
            ];
        }

        $baseQuery = DB::table('bed_trackers as bt')
            ->join('ref_facilities as facility', 'facility.hfhudcode', '=', 'bt.facility_hfhudcode');

        $this->applyFacilityScope($baseQuery, $user, 'facility');

        if ($this->hasColumn('bed_trackers', 'status')) {
            $baseQuery->where('bt.status', 'A');
        }

        $totals = (clone $baseQuery)
            ->selectRaw('
                COALESCE(SUM(bt.total_beds), 0) as total_beds,
                COALESCE(SUM(bt.occupied_beds), 0) as occupied_beds,
                COALESCE(SUM(bt.reserved_beds), 0) as reserved_beds,
                COUNT(DISTINCT bt.facility_hfhudcode) as facilities_reporting,
                COUNT(DISTINCT bt.bed_type) as bed_types_tracked
            ')
            ->first();

        $totalBeds = (int) ($totals->total_beds ?? 0);
        $occupiedBeds = (int) ($totals->occupied_beds ?? 0);
        $reservedBeds = (int) ($totals->reserved_beds ?? 0);
        $availableBeds = max($totalBeds - $occupiedBeds - $reservedBeds, 0);

        $facilityLabelColumn = $this->hasColumn('ref_facilities', 'facility_name')
            ? 'facility.facility_name'
            : 'facility.hfhudcode';

        $byFacility = (clone $baseQuery)
    ->selectRaw("
        bt.facility_hfhudcode as facility_key,
        {$facilityLabelColumn} as facility_name,
        COALESCE(SUM(bt.total_beds), 0) as total_beds,
        COALESCE(SUM(bt.occupied_beds), 0) as occupied_beds,
        COALESCE(SUM(bt.reserved_beds), 0) as reserved_beds
    ")
    ->groupBy('bt.facility_hfhudcode', $facilityLabelColumn)
    ->orderByRaw('(COALESCE(SUM(bt.total_beds), 0) - COALESCE(SUM(bt.occupied_beds), 0) - COALESCE(SUM(bt.reserved_beds), 0)) DESC')
    ->orderBy($facilityLabelColumn)
    ->limit(5)
    ->get()
    ->map(fn ($row) => $this->formatBedCapacityItem($row->facility_key, $row->facility_name, $row->total_beds, $row->occupied_beds, $row->reserved_beds))
    ->values()
    ->all();

        $regionGroupingColumns = $this->hasColumns('ref_region', ['regcode', 'regname']) && $this->hasColumn('ref_facilities', 'region_code');

        $byRegion = $regionGroupingColumns
            ? (clone $baseQuery)
                ->leftJoin('ref_region as region', 'region.regcode', '=', 'facility.region_code')
                ->selectRaw('
                    COALESCE(region.regcode, facility.region_code, "UNASSIGNED") as region_key,
                    COALESCE(region.regname, facility.region_code, "Unassigned region") as region_name,
                    COALESCE(SUM(bt.total_beds), 0) as total_beds,
                    COALESCE(SUM(bt.occupied_beds), 0) as occupied_beds,
                    COALESCE(SUM(bt.reserved_beds), 0) as reserved_beds
                ')
                ->groupBy(DB::raw('COALESCE(region.regcode, facility.region_code, "UNASSIGNED")'))
                ->groupBy(DB::raw('COALESCE(region.regname, facility.region_code, "Unassigned region")'))
                ->orderByRaw('(COALESCE(SUM(bt.total_beds), 0) - COALESCE(SUM(bt.occupied_beds), 0) - COALESCE(SUM(bt.reserved_beds), 0)) DESC')
                ->orderByRaw('COALESCE(region.regname, facility.region_code, "Unassigned region") ASC')
                ->limit(5)
                ->get()
                ->map(fn ($row) => $this->formatBedCapacityItem($row->region_key, $row->region_name, $row->total_beds, $row->occupied_beds, $row->reserved_beds))
                ->values()
                ->all()
            : [];

        return [
            'totals' => [
                'totalBeds' => $totalBeds,
                'occupiedBeds' => $occupiedBeds,
                'reservedBeds' => $reservedBeds,
                'availableBeds' => $availableBeds,
                'occupancyRate' => $totalBeds > 0 ? (int) round(($occupiedBeds / $totalBeds) * 100) : 0,
                'facilitiesReporting' => (int) ($totals->facilities_reporting ?? 0),
                'bedTypesTracked' => (int) ($totals->bed_types_tracked ?? 0),
            ],
            'byFacility' => $byFacility,
            'byRegion' => $byRegion,
        ];
    }

    private function buildActivity(?Authenticatable $user): array
    {
        $baseQuery = $this->newReferralBaseQuery($user);
        $timestampColumn = $this->referralTimestampColumn();
        $days = collect(range(6, 0));

        if (! $baseQuery || ! $timestampColumn) {
            return $days->map(fn (int $offset) => $this->emptyActivityPoint($offset))->values()->all();
        }

        $startDate = Carbon::today()->subDays(6)->toDateString();
        $counts = (clone $baseQuery)
            ->selectRaw("DATE({$timestampColumn}) as activity_day, COUNT(*) as total")
            ->whereDate($timestampColumn, '>=', $startDate)
            ->groupBy('activity_day')
            ->pluck('total', 'activity_day');

        return $days
            ->map(function (int $offset) use ($counts) {
                $date = Carbon::today()->subDays($offset);
                $key = $date->toDateString();

                return [
                    'label' => $date->format('D'),
                    'date' => $date->format('M j'),
                    'count' => (int) ($counts[$key] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function buildRecentReferrals(?Authenticatable $user): array
    {
        $baseQuery = $this->newReferralBaseQuery($user);

        if (! $baseQuery || ! $this->hasColumn('referral_information', 'LogID')) {
            return [];
        }

        $query = (clone $baseQuery)->select([
            'ri.LogID',
            'ri.fhudFrom',
            'ri.fhudTo',
        ]);

        if ($this->hasColumn('referral_information', 'referralCategory')) {
            $query->addSelect('ri.referralCategory');
        }

        if ($this->hasColumn('referral_information', 'referralReason')) {
            $query->addSelect('ri.referralReason');
        }

        if ($this->hasColumn('referral_information', 'typeOfReferral')) {
            $query->addSelect('ri.typeOfReferral');
        }

        if ($this->hasColumn('referral_information', 'refferalDate')) {
            $query->addSelect('ri.refferalDate');
        }

        if ($this->hasColumn('referral_information', 'refferalTime')) {
            $query->addSelect('ri.refferalTime');
        }

        if ($this->hasColumns('referral_patientinfo', ['LogID'])) {
            $query->leftJoin('referral_patientinfo as patient', 'patient.LogID', '=', 'ri.LogID');
            $query->addSelect([
                'patient.patientFirstName',
                'patient.patientMiddlename',
                'patient.patientLastname',
            ]);
        }

        if ($this->hasColumns('ref_facilities', ['hfhudcode', 'facility_name'])) {
            $query->leftJoin('ref_facilities as origin_facility', 'origin_facility.hfhudcode', '=', 'ri.fhudFrom');
            $query->leftJoin('ref_facilities as destination_facility', 'destination_facility.hfhudcode', '=', 'ri.fhudTo');
            $query->addSelect([
                'origin_facility.facility_name as origin_name',
                'destination_facility.facility_name as destination_name',
            ]);
        }

        if ($this->hasColumns('referral_track', ['LogID'])) {
            $query->leftJoin('referral_track as rt', 'rt.LogID', '=', 'ri.LogID');
            $query->addSelect(DB::raw('CASE WHEN rt.LogID IS NULL THEN 0 ELSE 1 END as has_track'));
        }

        $orderColumn = $this->referralTimestampColumn() ?? 'ri.LogID';
        $rows = $query
            ->orderByDesc($orderColumn)
            ->limit(6)
            ->get();

        return $rows->map(function ($row) {
            $reason = ReferralHelper::getReferralReasonbyCode($row->referralReason ?? null);
            $type = ReferralHelper::getReferralTypebyCode($row->typeOfReferral ?? null);
            $hasTrack = (bool) ($row->has_track ?? false);

            return [
                'id' => (string) $row->LogID,
                'patientName' => $this->patientName($row),
                'originName' => $row->origin_name ?? $row->fhudFrom ?? 'Origin unavailable',
                'destinationName' => $row->destination_name ?? $row->fhudTo ?? 'Destination unavailable',
                'scheduledFor' => $this->formatReferralDateTime($row->refferalDate ?? null, $row->refferalTime ?? null),
                'category' => $this->referralCategoryLabel($row->referralCategory ?? null),
                'reason' => $reason['description'] ?? ($row->referralReason ?? 'Reason unavailable'),
                'type' => $type['description'] ?? ($row->typeOfReferral ?? 'Type unavailable'),
                'status' => $hasTrack ? 'Tracked' : 'Awaiting action',
                'statusTone' => $hasTrack ? 'tracked' : 'queue',
            ];
        })->values()->all();
    }

    private function buildTopReasons(?Authenticatable $user): array
    {
        $baseQuery = $this->newReferralBaseQuery($user);

        if (! $baseQuery || ! $this->hasColumn('referral_information', 'referralReason')) {
            return [];
        }

        $rows = (clone $baseQuery)
            ->selectRaw('ri.referralReason as reason_code, COUNT(*) as total')
            ->whereNotNull('ri.referralReason')
            ->groupBy('ri.referralReason')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $total = (int) $rows->sum('total');

        return $rows->map(function ($row) use ($total) {
            $reason = ReferralHelper::getReferralReasonbyCode($row->reason_code);

            return [
                'code' => $row->reason_code,
                'label' => $reason['description'] ?? $row->reason_code,
                'count' => (int) $row->total,
                'share' => $total > 0 ? (int) round(($row->total / $total) * 100) : 0,
            ];
        })->values()->all();
    }

    private function buildNetwork(?Authenticatable $user): array
    {
        $facilities = 0;

        if ($this->hasColumns('ref_facilities', ['hfhudcode'])) {
            $facilityQuery = DB::table('ref_facilities');
            $this->applyFacilityScope($facilityQuery, $user, 'ref_facilities');
            $facilities = $facilityQuery->count('hfhudcode');
        }

        $providers = $this->hasColumns('ref_emr', ['emr_id'])
            ? DB::table('ref_emr')->count('emr_id')
            : 0;

        $users = $this->hasColumns('users', ['id'])
            ? DB::table('users')->count('id')
            : 0;

        return [
            [
                'key' => 'facilities',
                'label' => 'Facilities in scope',
                'value' => $facilities,
                'detail' => 'Directory coverage for your current access level.',
            ],
            [
                'key' => 'providers',
                'label' => 'Connected EMRs',
                'value' => $providers,
                'detail' => 'Partner systems available in the platform.',
            ],
            [
                'key' => 'users',
                'label' => 'Platform users',
                'value' => $users,
                'detail' => 'Accounts that can collaborate on referrals.',
            ],
        ];
    }

    private function resolveScope(?Authenticatable $user): array
    {
        $role = $this->normalizedRole($user);

        return match ($role) {
            'emr' => [
                'label' => 'EMR Operations View',
                'description' => 'Monitoring referrals routed to facilities connected with your EMR network.',
            ],
            'region' => [
                'label' => 'Regional Operations View',
                'description' => 'Keeping an eye on referral volume and queue pressure across your assigned region.',
            ],
            'hospital' => [
                'label' => 'Hospital Operations View',
                'description' => 'Focused on referrals that are landing directly in your facility queue.',
            ],
            default => [
                'label' => 'System Operations View',
                'description' => 'A live snapshot of referral demand, follow-through, and network readiness.',
            ],
        };
    }

    private function quickActions(): array
    {
        return [
            [
                'key' => 'new-referral',
                'title' => 'Create referral',
                'description' => 'Open the referral form and submit a new case.',
                'href' => '/referrals/create',
            ],
            [
                'key' => 'incoming',
                'title' => 'Review incoming',
                'description' => 'Work through the active referral queue.',
                'href' => '/incoming',
            ],
            [
                'key' => 'patients',
                'title' => 'Patient registry',
                'description' => 'Check patient records and linked referrals.',
                'href' => '/patient_registry',
            ],
            [
                'key' => 'facilities',
                'title' => 'Facility directory',
                'description' => 'Review the connected facilities network.',
                'href' => '/facilities',
            ],
        ];
    }

    private function newReferralBaseQuery(?Authenticatable $user): ?Builder
    {
        if (! $this->hasColumns('referral_information', ['LogID'])) {
            return null;
        }

        $query = DB::table('referral_information as ri');
        $this->applyReferralScope($query, $user);

        return $query;
    }

    private function applyReferralScope(Builder $query, ?Authenticatable $user): void
    {
        if (! $this->shouldScopeByFacility($user)) {
            return;
        }

        $query->join('ref_facilities as scoped_facility', 'scoped_facility.hfhudcode', '=', 'ri.fhudTo');
        $this->applyFacilityScope($query, $user, 'scoped_facility');
    }

    private function applyFacilityScope(Builder $query, ?Authenticatable $user, string $tableAlias): void
    {
        $accessId = $user?->access_id;
        $role = $this->normalizedRole($user);

        if (! $accessId) {
            return;
        }

        if ($role === 'emr' && $this->hasColumn('ref_facilities', 'emr_id')) {
            $query->where("{$tableAlias}.emr_id", $accessId);
        }

        if ($role === 'region' && $this->hasColumn('ref_facilities', 'region_code')) {
            $query->where("{$tableAlias}.region_code", $accessId);
        }

        if ($role === 'hospital' && $this->hasColumn('ref_facilities', 'hfhudcode')) {
            $query->where("{$tableAlias}.hfhudcode", $accessId);
        }
    }

    private function shouldScopeByFacility(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($this->normalizedRole($user), ['emr', 'region', 'hospital'], true)
            && filled($user->access_id ?? null)
            && $this->hasColumns('ref_facilities', ['hfhudcode']);
    }

    private function normalizedRole(?Authenticatable $user): string
    {
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return 'system';
        }

        return strtolower((string) ($user->getRoleNames()->first() ?? 'system'));
    }

    private function patientName(object $row): string
    {
        $name = trim(implode(' ', array_filter([
            $row->patientFirstName ?? null,
            $row->patientMiddlename ?? null,
            $row->patientLastname ?? null,
        ])));

        return $name !== '' ? $name : 'Patient record unavailable';
    }

    private function referralCategoryLabel(?string $category): string
    {
        return match (strtoupper((string) $category)) {
            'ER' => 'Emergency',
            'OPD' => 'Outpatient',
            default => $category ?: 'Referral',
        };
    }

    private function formatReferralDateTime(?string $date, ?string $time): string
    {
        if (! $date && ! $time) {
            return 'Schedule unavailable';
        }

        try {
            if ($date && $time) {
                return Carbon::parse("{$date} {$time}")->format('M j, Y g:i A');
            }

            if ($date) {
                return Carbon::parse($date)->format('M j, Y');
            }

            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable) {
            return trim(implode(' ', array_filter([$date, $time])));
        }
    }

    private function formatBedCapacityItem(string $key, string $label, int|string $totalBeds, int|string $occupiedBeds, int|string $reservedBeds): array
    {
        $total = (int) $totalBeds;
        $occupied = (int) $occupiedBeds;
        $reserved = (int) $reservedBeds;
        $available = max($total - $occupied - $reserved, 0);

        return [
            'key' => $key,
            'label' => $label,
            'totalBeds' => $total,
            'occupiedBeds' => $occupied,
            'reservedBeds' => $reserved,
            'availableBeds' => $available,
            'occupancyRate' => $total > 0 ? (int) round(($occupied / $total) * 100) : 0,
        ];
    }

    private function referralTimestampColumn(string $alias = 'ri'): ?string
    {
        foreach (['created_at', 'refferalDate', 'logDate'] as $column) {
            if ($this->hasColumn('referral_information', $column)) {
                return "{$alias}.{$column}";
            }
        }

        return null;
    }

    private function emptyActivityPoint(int $offset): array
    {
        $date = Carbon::today()->subDays($offset);

        return [
            'label' => $date->format('D'),
            'date' => $date->format('M j'),
            'count' => 0,
        ];
    }

    private function hasColumns(string $table, array $columns): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! $this->hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tableCache)) {
            $this->tableCache[$table] = Schema::hasTable($table);
        }

        return $this->tableCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (! array_key_exists($cacheKey, $this->columnCache)) {
            $this->columnCache[$cacheKey] = $this->hasTable($table) && Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$cacheKey];
    }
}
