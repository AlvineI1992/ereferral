<?php

namespace App\Http\Controllers;

use App\Models\BedTracker;
use App\Models\RefFacilitiesModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BedTrackerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(10, min((int) $request->input('perPage', 10), 100));
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', 'all'));
        $facility = trim((string) $request->input('facility_hfhudcode', ''));

        $query = BedTracker::query()
            ->leftJoin('ref_facilities', 'bed_trackers.facility_hfhudcode', '=', 'ref_facilities.hfhudcode')
            ->leftJoin('ref_region', 'ref_facilities.region_code', '=', 'ref_region.regcode')
            ->select([
                'bed_trackers.*',
                'ref_facilities.facility_name',
                'ref_facilities.region_code',
                'ref_facilities.emr_id',
                'ref_region.regname',
            ]);

        $this->applyAccessScope($query, $request->user());

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('bed_trackers.bed_type', 'like', "%{$search}%")
                    ->orWhere('ref_facilities.facility_name', 'like', "%{$search}%")
                    ->orWhere('bed_trackers.facility_hfhudcode', 'like', "%{$search}%")
                    ->orWhere('ref_region.regname', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['A', 'I'], true)) {
            $query->where('bed_trackers.status', $status);
        }

        if ($facility !== '') {
            $query->where('bed_trackers.facility_hfhudcode', $facility);
        }

        $paginator = $query
            ->orderBy('ref_facilities.facility_name')
            ->orderBy('bed_trackers.bed_type')
            ->paginate($perPage)
            ->through(fn ($row) => $this->transformRow($row));

        return response()->json($paginator);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $record = $this->findAccessibleRecord($request, $id);

        return response()->json([
            'data' => $this->transformDetail($record),
        ]);
    }

    public function facilityOptions(Request $request): JsonResponse
    {
        $query = RefFacilitiesModel::query()
            ->select('hfhudcode', 'facility_name', 'region_code', 'emr_id')
            ->where('status', 'A');

        $this->applyFacilityAccessScope($query, $request->user());

        $rows = $query
            ->orderBy('facility_name')
            ->get()
            ->map(fn ($facility) => [
                'hfhudcode' => $facility->hfhudcode,
                'facility_name' => $facility->facility_name,
                'region_code' => $facility->region_code,
                'emr_id' => $facility->emr_id,
            ])
            ->values();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $this->ensureFacilityAccessible($request, $validated['facility_hfhudcode']);

        $record = BedTracker::create($this->buildPayload($request, $validated));

        return response()->json([
            'message' => 'Bed tracker record created successfully.',
            'data' => $this->transformDetail($record->fresh()),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->findAccessibleRecord($request, $id);
        $validated = $this->validatePayload($request, $record->id);
        $this->ensureFacilityAccessible($request, $validated['facility_hfhudcode']);

        $record->update($this->buildPayload($request, $validated));

        return response()->json([
            'message' => 'Bed tracker record updated successfully.',
            'data' => $this->transformDetail($record->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $record = $this->findAccessibleRecord($request, $id);
        $record->delete();

        return response()->json([
            'message' => 'Bed tracker record deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'facility_hfhudcode' => ['required', 'string', 'exists:ref_facilities,hfhudcode'],
            'bed_type' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bed_trackers', 'bed_type')
                    ->where(fn ($query) => $query->where('facility_hfhudcode', $request->input('facility_hfhudcode')))
                    ->ignore($id),
            ],
            'total_beds' => ['required', 'integer', 'min:0'],
            'occupied_beds' => ['required', 'integer', 'min:0'],
            'reserved_beds' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:A,I'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'facility_hfhudcode.required' => 'Facility is required.',
            'bed_type.required' => 'Bed type is required.',
            'bed_type.unique' => 'This bed type already exists for the selected facility.',
            'total_beds.required' => 'Total beds is required.',
            'occupied_beds.required' => 'Occupied beds is required.',
        ]);

        $reservedBeds = (int) ($validated['reserved_beds'] ?? 0);
        $occupiedBeds = (int) $validated['occupied_beds'];
        $totalBeds = (int) $validated['total_beds'];

        if ($occupiedBeds + $reservedBeds > $totalBeds) {
            throw ValidationException::withMessages([
                'occupied_beds' => 'Occupied plus reserved beds cannot exceed total beds.',
            ]);
        }

        return $validated;
    }

    private function buildPayload(Request $request, array $validated): array
    {
        return [
            'facility_hfhudcode' => $validated['facility_hfhudcode'],
            'bed_type' => strtoupper(trim($validated['bed_type'])),
            'total_beds' => (int) $validated['total_beds'],
            'occupied_beds' => (int) $validated['occupied_beds'],
            'reserved_beds' => (int) ($validated['reserved_beds'] ?? 0),
            'status' => $validated['status'],
            'remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'updated_by' => $request->user()?->name,
        ];
    }

    private function transformRow(object $row): array
    {
        $totalBeds = (int) $row->total_beds;
        $occupiedBeds = (int) $row->occupied_beds;
        $reservedBeds = (int) $row->reserved_beds;
        $availableBeds = max($totalBeds - $occupiedBeds - $reservedBeds, 0);

        return [
            'id' => $row->id,
            'facility_hfhudcode' => $row->facility_hfhudcode,
            'facility_name' => $row->facility_name,
            'region_name' => $row->regname,
            'bed_type' => $row->bed_type,
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'reserved_beds' => $reservedBeds,
            'available_beds' => $availableBeds,
            'occupancy_rate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0,
            'status' => $row->status,
            'status_label' => $row->status === 'A' ? 'Active' : 'Inactive',
            'remarks' => $row->remarks,
            'updated_by' => $row->updated_by,
            'updated_at' => $row->updated_at,
        ];
    }

    private function transformDetail(BedTracker $record): array
    {
        $record->loadMissing('facility');
        $totalBeds = (int) $record->total_beds;
        $occupiedBeds = (int) $record->occupied_beds;
        $reservedBeds = (int) $record->reserved_beds;

        return [
            'id' => $record->id,
            'facility_hfhudcode' => $record->facility_hfhudcode,
            'facility_name' => $record->facility?->facility_name,
            'bed_type' => $record->bed_type,
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'reserved_beds' => $reservedBeds,
            'available_beds' => max($totalBeds - $occupiedBeds - $reservedBeds, 0),
            'status' => $record->status,
            'remarks' => $record->remarks,
            'updated_by' => $record->updated_by,
            'updated_at' => $record->updated_at,
        ];
    }

    private function findAccessibleRecord(Request $request, int $id): BedTracker
    {
        $query = BedTracker::query();
        $this->applyAccessScope($query, $request->user());

        return $query->findOrFail($id);
    }

    private function ensureFacilityAccessible(Request $request, string $facilityHfhudcode): void
    {
        $query = RefFacilitiesModel::query()->where('hfhudcode', $facilityHfhudcode);
        $this->applyFacilityAccessScope($query, $request->user());

        if (! $query->exists()) {
            throw ValidationException::withMessages([
                'facility_hfhudcode' => 'You do not have access to this facility.',
            ]);
        }
    }

    private function applyAccessScope(Builder $query, $user): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        $accessType = strtoupper((string) ($user?->access_type ?? ''));
        $accessId = (string) ($user?->access_id ?? '');

        match ($accessType) {
            'EMR' => $query->where('ref_facilities.emr_id', $accessId),
            'CHD' => $query->where('ref_facilities.region_code', $accessId),
            'HOSP' => $query->where('bed_trackers.facility_hfhudcode', $accessId),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function applyFacilityAccessScope(Builder $query, $user): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        $accessType = strtoupper((string) ($user?->access_type ?? ''));
        $accessId = (string) ($user?->access_id ?? '');

        match ($accessType) {
            'EMR' => $query->where('emr_id', $accessId),
            'CHD' => $query->where('region_code', $accessId),
            'HOSP' => $query->where('hfhudcode', $accessId),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function isAdmin($user): bool
    {
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
            : [];

        return in_array('admin', $roles, true) || in_array('super-admin', $roles, true);
    }
}
