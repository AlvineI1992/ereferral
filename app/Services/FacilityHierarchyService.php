<?php

namespace App\Services;

use App\Enums\FacilityHierarchyLevel;
use App\Models\FacilityHierarchy;
use App\Models\RefFacilitiesModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacilityHierarchyService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return FacilityHierarchy::query()
            ->with(['facility:hfhudcode,facility_name,facility_type,region_code,province_code,fhudaddress,status', 'parent.facility:hfhudcode,facility_name'])
            ->withCount('children')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('referral_network', 'like', "%{$search}%")
                        ->orWhere('facility_hfhudcode', 'like', "%{$search}%")
                        ->orWhereHas('facility', fn (Builder $facility) => $facility->where('facility_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['level'] ?? null, fn (Builder $query, string $level) => $query->where('level', $level))
            ->when($filters['region'] ?? null, fn (Builder $query, string $region) => $query->where('coverage_region_code', $region))
            ->when($filters['province'] ?? null, fn (Builder $query, string $province) => $query->where('coverage_province_code', $province))
            ->orderByRaw("CASE level WHEN 'apex' THEN 1 WHEN 'provincial' THEN 2 WHEN 'district' THEN 3 ELSE 4 END")
            ->orderBy('referral_network')->paginate($filters['per_page'] ?? 20)->withQueryString();
    }

    public function save(array $data, ?FacilityHierarchy $hierarchy = null): FacilityHierarchy
    {
        $hierarchy ??= new FacilityHierarchy();
        $this->validateRelationships($data, $hierarchy);

        return DB::transaction(function () use ($data, $hierarchy) {
            $hierarchy->fill($data)->save();
            return $hierarchy->fresh(['facility', 'parent.facility']);
        });
    }

    public function saveMany(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $records = [];

            foreach ($data['facility_hfhudcodes'] as $facilityCode) {
                $facility = RefFacilitiesModel::findOrFail($facilityCode);
                $recordData = [
                    'facility_hfhudcode' => $facilityCode,
                    'parent_hfhudcode' => $data['parent_hfhudcode'] ?? null,
                    'level' => $data['level'],
                    'referral_network' => $data['referral_network'],
                    'coverage_region_code' => (string) $facility->region_code,
                    'coverage_province_code' => $facility->province_code ? (string) $facility->province_code : null,
                    'coverage_notes' => $data['coverage_notes'] ?? null,
                    'is_active' => $data['is_active'],
                ];

                $hierarchy = new FacilityHierarchy();
                $this->validateRelationships($recordData, $hierarchy);
                $hierarchy->fill($recordData)->save();
                $records[] = $hierarchy->fresh(['facility', 'parent.facility']);
            }

            return $records;
        });
    }

    public function delete(FacilityHierarchy $hierarchy): void
    {
        if ($hierarchy->children()->exists()) {
            throw ValidationException::withMessages(['hierarchy' => 'Reassign or remove child facilities before deleting this hierarchy entry.']);
        }
        $hierarchy->delete();
    }

    private function validateRelationships(array $data, FacilityHierarchy $hierarchy): void
    {
        if ($hierarchy->exists && $hierarchy->facility_hfhudcode !== $data['facility_hfhudcode']) {
            throw ValidationException::withMessages(['facility_hfhudcode' => 'The mapped facility cannot be changed. Create a new mapping instead.']);
        }

        $level = FacilityHierarchyLevel::from($data['level']);
        $parentCode = $data['parent_hfhudcode'] ?? null;
        if (($level === FacilityHierarchyLevel::Apex) !== ($parentCode === null)) {
            throw ValidationException::withMessages(['parent_hfhudcode' => $level === FacilityHierarchyLevel::Apex ? 'Apex hospitals cannot have a parent.' : 'A parent facility is required for this level.']);
        }

        $facility = RefFacilitiesModel::findOrFail($data['facility_hfhudcode']);
        if ((string) $facility->region_code !== (string) $data['coverage_region_code'] || (($data['coverage_province_code'] ?? null) && (string) $facility->province_code !== (string) $data['coverage_province_code'])) {
            throw ValidationException::withMessages(['coverage_region_code' => 'Coverage must match the facility region and province.']);
        }

        if (! $parentCode) return;
        $parent = FacilityHierarchy::where('facility_hfhudcode', $parentCode)->firstOrFail();
        if ($parent->level->rank() !== $level->rank() - 1) {
            throw ValidationException::withMessages(['parent_hfhudcode' => 'The parent must be exactly one level above the selected facility.']);
        }
        if (! $parent->is_active || $parent->coverage_region_code !== $data['coverage_region_code'] || ($level->rank() >= 3 && $parent->coverage_province_code !== ($data['coverage_province_code'] ?? null))) {
            throw ValidationException::withMessages(['parent_hfhudcode' => 'The parent must be active and cover the same required geographic area.']);
        }
        if ($parent->referral_network !== $data['referral_network']) {
            throw ValidationException::withMessages(['referral_network' => 'A child facility must belong to its parent referral network.']);
        }
        $cursor = $parent;
        while ($cursor) {
            if ($cursor->id === $hierarchy->id) throw ValidationException::withMessages(['parent_hfhudcode' => 'This parent assignment would create a cycle.']);
            $cursor = $cursor->parent;
        }
    }
}
