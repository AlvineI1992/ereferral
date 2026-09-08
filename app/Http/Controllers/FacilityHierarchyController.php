<?php

namespace App\Http\Controllers;

use App\Enums\FacilityHierarchyLevel;
use App\Http\Requests\BulkFacilityHierarchyRequest;
use App\Http\Requests\FacilityHierarchyRequest;
use App\Models\FacilityHierarchy;
use App\Models\RefBarangayModel;
use App\Models\RefCityModel;
use App\Models\RefFacilitiesModel;
use App\Models\RefFacilitytypeModel;
use App\Models\RefProvinceModel;
use App\Models\RefRegionModel;
use App\Services\FacilityHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacilityHierarchyController extends Controller
{
    public function __construct(private readonly FacilityHierarchyService $service) {}

    public function index(Request $request): Response
    {
        return Inertia::render('FacilityHierarchy/Index', [
            'canCreate' => $request->user()->can('facility hierarchy create'),
            'canEdit' => $request->user()->can('facility hierarchy edit'),
            'canDelete' => $request->user()->can('facility hierarchy delete'),
            'levels' => collect(FacilityHierarchyLevel::cases())->map(fn ($level) => ['value' => $level->value, 'label' => $level->label()]),
            'regions' => RefRegionModel::query()->selectRaw('regcode as code, regname as name')->orderBy('regname')->get(),
            'provinces' => RefProvinceModel::query()->select('provcode as code', 'provname as name', 'regcode as region_code')->orderBy('provname')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'], 'level' => ['nullable', 'string'],
            'region' => ['nullable', 'string', 'max:10'], 'province' => ['nullable', 'string', 'max:10'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        return response()->json($this->service->paginate($validated));
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region' => ['required', 'string', 'max:10', 'exists:ref_region,regcode'],
        ]);

        $facilities = RefFacilitiesModel::query()
            ->select([
                'hfhudcode', 'facility_name', 'facility_type', 'region_code', 'province_code', 'city_code', 'bgycode', 'status',
            ])
            ->with('hierarchy:id,facility_hfhudcode,level,referral_network,coverage_region_code,coverage_province_code,is_active')
            ->where('status', 'A')
            ->where('region_code', $validated['region'])
            ->orderBy('facility_name')
            ->get();

        $regionName = RefRegionModel::where('regcode', $validated['region'])->value('regname');
        $provinceNames = RefProvinceModel::whereIn('provcode', $facilities->pluck('province_code')->filter()->unique())
            ->pluck('provname', 'provcode');
        $cityNames = RefCityModel::whereIn('citycode', $facilities->pluck('city_code')->filter()->unique())
            ->pluck('cityname', 'citycode');
        $barangayNames = RefBarangayModel::whereIn('bgycode', $facilities->pluck('bgycode')->filter()->unique())
            ->pluck('bgyname', 'bgycode');
        $facilityTypeNames = RefFacilitytypeModel::whereIn('factype_code', $facilities->pluck('facility_type')->filter()->unique())
            ->get(['factype_code', 'description'])
            ->mapWithKeys(fn (RefFacilitytypeModel $type) => [(string) (int) $type->factype_code => $type->description]);

        $facilities->each(function (RefFacilitiesModel $facility) use ($regionName, $provinceNames, $cityNames, $barangayNames, $facilityTypeNames) {
            $facility->setAttribute('region_name', $regionName);
            $facility->setAttribute('province_name', $provinceNames->get($facility->province_code));
            $facility->setAttribute('city_name', $cityNames->get($facility->city_code));
            $facility->setAttribute('barangay_name', $barangayNames->get($facility->bgycode));
            $facility->setAttribute('facility_type_name', $facilityTypeNames->get((string) (int) $facility->facility_type));
        });

        // The facility directory stores some region codes numerically (1) while
        // the region reference uses canonical zero-padded codes (01).
        $facilities->each->setAttribute('region_code', $validated['region']);

        return response()->json(['data' => $facilities]);
    }

    public function store(FacilityHierarchyRequest $request): JsonResponse
    {
        $record = $this->service->save($request->validated());
        return response()->json(['message' => 'Facility hierarchy mapping created.', 'data' => $record], 201);
    }

    public function bulkStore(BulkFacilityHierarchyRequest $request): JsonResponse
    {
        $records = $this->service->saveMany($request->validated());

        return response()->json([
            'message' => count($records).' facility hierarchy mappings created.',
            'data' => $records,
        ], 201);
    }

    public function update(FacilityHierarchyRequest $request, FacilityHierarchy $facilityHierarchy): JsonResponse
    {
        $record = $this->service->save($request->validated(), $facilityHierarchy);
        return response()->json(['message' => 'Facility hierarchy mapping updated.', 'data' => $record]);
    }

    public function destroy(FacilityHierarchy $facilityHierarchy): JsonResponse
    {
        $this->service->delete($facilityHierarchy);
        return response()->json(['message' => 'Facility hierarchy mapping deleted.']);
    }
}
