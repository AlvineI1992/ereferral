<?php

namespace App\Http\Controllers;

use App\Models\RefBarangayModel;
use App\Models\RefCityModel;
use App\Models\RefProvinceModel;
use App\Models\RefRegionModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DemographicController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $level = $this->normalizeLevel((string) $request->input('level', 'region'));
        $perPage = max(10, min(100, (int) $request->input('perPage', 15)));
        $status = trim((string) $request->input('status', 'all'));
        $search = trim((string) $request->input('search', ''));

        $query = $this->buildListQuery($level);

        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $query->where(function ($builder) use ($level, $searchLike) {
                match ($level) {
                    'region' => $builder
                        ->whereRaw("LPAD(ref_region.regcode, 2, '0') LIKE ?", [$searchLike])
                        ->orWhere('ref_region.regname', 'like', $searchLike)
                        ->orWhere('ref_region.regabbrev', 'like', $searchLike),
                    'province' => $builder
                        ->where('ref_province.provcode', 'like', $searchLike)
                        ->orWhere('ref_province.provname', 'like', $searchLike)
                        ->orWhere('ref_region.regname', 'like', $searchLike),
                    'city' => $builder
                        ->where('ref_city.citycode', 'like', $searchLike)
                        ->orWhere('ref_city.cityname', 'like', $searchLike)
                        ->orWhere('ref_province.provname', 'like', $searchLike)
                        ->orWhere('ref_region.regname', 'like', $searchLike),
                    'barangay' => $builder
                        ->where('ref_barangay.bgycode', 'like', $searchLike)
                        ->orWhere('ref_barangay.bgyname', 'like', $searchLike)
                        ->orWhere('ref_city.cityname', 'like', $searchLike)
                        ->orWhere('ref_province.provname', 'like', $searchLike)
                        ->orWhere('ref_region.regname', 'like', $searchLike),
                };
            });
        }

        if (in_array($status, ['A', 'I'], true)) {
            $statusColumn = match ($level) {
                'region' => 'ref_region.status',
                'province' => 'ref_province.status',
                'city' => 'ref_city.status',
                'barangay' => 'ref_barangay.status',
            };

            $query->where($statusColumn, $status);
        }

        $regionCode = $this->normalizeRegionCode($request->input('region_code'));
        $provinceCode = trim((string) $request->input('province_code', ''));
        $cityCode = trim((string) $request->input('city_code', ''));

        if ($regionCode !== '') {
            match ($level) {
                'province' => $query->where('ref_province.regcode', $regionCode),
                'city' => $query->where('ref_city.regcode', $regionCode),
                'barangay' => $query->where('ref_barangay.regcode', $regionCode),
                default => null,
            };
        }

        if ($provinceCode !== '' && in_array($level, ['city', 'barangay'], true)) {
            match ($level) {
                'city' => $query->where('ref_city.provcode', $provinceCode),
                'barangay' => $query->where('ref_barangay.provcode', $provinceCode),
                default => null,
            };
        }

        if ($cityCode !== '' && $level === 'barangay') {
            $query->where('ref_barangay.citycode', $cityCode);
        }

        $paginator = $query
            ->paginate($perPage)
            ->through(fn ($row) => $this->transformRecord($level, $row));

        return response()->json($paginator);
    }

    public function list(): JsonResponse
    {
        $regions = RefRegionModel::with(['provinces.cities.barangays'])
            ->orderBy('regname')
            ->get();

        $result = $regions->map(function ($region) {
            return [
                'code' => $this->normalizeRegionCode($region->regcode),
                'name' => $region->regname,
                'provinces' => $region->provinces
                    ->sortBy('provname')
                    ->values()
                    ->map(function ($province) {
                        return [
                            'code' => $province->provcode,
                            'name' => $province->provname,
                            'cities' => $province->cities
                                ->sortBy('cityname')
                                ->values()
                                ->map(function ($city) {
                                    return [
                                        'code' => $city->citycode,
                                        'name' => $city->cityname,
                                        'barangays' => $city->barangays
                                            ->sortBy('bgyname')
                                            ->values()
                                            ->map(function ($barangay) {
                                                return [
                                                    'code' => $barangay->bgycode,
                                                    'name' => $barangay->bgyname,
                                                ];
                                            }),
                                    ];
                                }),
                        ];
                    }),
            ];
        });

        return response()->json([
            'regions' => $result,
        ]);
    }

    public function region_list(): JsonResponse
    {
        $regions = RefRegionModel::query()
            ->selectRaw("LPAD(regcode, 2, '0') as regcode, regname")
            ->orderBy('regname')
            ->get();

        return response()->json([
            'data' => $regions,
        ]);
    }

    public function options(Request $request, string $level): JsonResponse
    {
        $level = $this->normalizeLevel($level);
        $status = trim((string) $request->input('status', 'all'));
        $regionCode = $this->normalizeRegionCode($request->input('region_code'));
        $provinceCode = trim((string) $request->input('province_code', ''));
        $cityCode = trim((string) $request->input('city_code', ''));

        $rows = match ($level) {
            'region' => RefRegionModel::query()
                ->selectRaw("LPAD(regcode, 2, '0') as code, regname as name, status")
                ->when(in_array($status, ['A', 'I'], true), fn ($query) => $query->where('status', $status))
                ->orderBy('regname')
                ->get(),
            'province' => RefProvinceModel::query()
                ->select('provcode as code', 'provname as name', 'status', 'regcode')
                ->when($regionCode !== '', fn ($query) => $query->where('regcode', $regionCode))
                ->when(in_array($status, ['A', 'I'], true), fn ($query) => $query->where('status', $status))
                ->orderBy('provname')
                ->get(),
            'city' => RefCityModel::query()
                ->select('citycode as code', 'cityname as name', 'status', 'regcode', 'provcode')
                ->when($provinceCode !== '', fn ($query) => $query->where('provcode', $provinceCode))
                ->when($provinceCode === '' && $regionCode !== '', fn ($query) => $query->where('regcode', $regionCode))
                ->when(in_array($status, ['A', 'I'], true), fn ($query) => $query->where('status', $status))
                ->orderBy('cityname')
                ->limit($provinceCode === '' ? 500 : 5000)
                ->get(),
            'barangay' => $cityCode === ''
                ? collect()
                : RefBarangayModel::query()
                    ->select('bgycode as code', 'bgyname as name', 'status', 'regcode', 'provcode', 'citycode')
                    ->where('citycode', $cityCode)
                    ->when(in_array($status, ['A', 'I'], true), fn ($query) => $query->where('status', $status))
                    ->orderBy('bgyname')
                    ->limit(10000)
                    ->get(),
        };

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function show(string $level, string $id): JsonResponse
    {
        $level = $this->normalizeLevel($level);
        $record = $this->findRecord($level, $id);

        return response()->json($this->transformRecord($level, $record));
    }

    public function store(Request $request, string $level): JsonResponse
    {
        $level = $this->normalizeLevel($level);
        $payload = $this->validatedPayload($request, $level);
        $modelClass = $this->resolveModel($level);
        $record = $modelClass::create($payload);

        return response()->json([
            'message' => ucfirst($level) . ' created successfully.',
            'data' => $this->transformRecord($level, $record->fresh()),
        ], 201);
    }

    public function update(Request $request, string $level, string $id): JsonResponse
    {
        $level = $this->normalizeLevel($level);
        $record = $this->findRecord($level, $id);
        $payload = $this->validatedPayload($request, $level, true);

        $record->fill(array_filter($payload, fn ($value) => $value !== null));
        $record->save();

        return response()->json([
            'message' => ucfirst($level) . ' updated successfully.',
            'data' => $this->transformRecord($level, $record->fresh()),
        ]);
    }

    public function destroy(string $level, string $id): JsonResponse
    {
        $level = $this->normalizeLevel($level);
        $record = $this->findRecord($level, $id);

        if ($level === 'region' && $record->provinces()->exists()) {
            throw ValidationException::withMessages([
                'form' => 'Delete or reassign provinces under this region first.',
            ]);
        }

        if ($level === 'province' && $record->cities()->exists()) {
            throw ValidationException::withMessages([
                'form' => 'Delete or reassign cities under this province first.',
            ]);
        }

        if ($level === 'city' && $record->barangays()->exists()) {
            throw ValidationException::withMessages([
                'form' => 'Delete or reassign barangays under this city first.',
            ]);
        }

        $record->delete();

        return response()->json([
            'message' => ucfirst($level) . ' deleted successfully.',
        ]);
    }

    private function normalizeLevel(string $level): string
    {
        $normalized = strtolower(trim($level));

        if (! in_array($normalized, ['region', 'province', 'city', 'barangay'], true)) {
            abort(404);
        }

        return $normalized;
    }

    private function normalizeRegionCode(mixed $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        return str_pad($digits !== '' ? $digits : $normalized, 2, '0', STR_PAD_LEFT);
    }

    private function buildListQuery(string $level)
    {
        return match ($level) {
            'region' => RefRegionModel::query()
                ->selectRaw("
                    LPAD(ref_region.regcode, 2, '0') as code,
                    ref_region.regname as name,
                    ref_region.regabbrev,
                    ref_region.nscb_reg_code as nscb_code,
                    ref_region.nscb_reg_name as nscb_name,
                    ref_region.status,
                    ref_region.dateupdated,
                    ref_region.addedby
                ")
                ->orderBy('ref_region.regname'),
            'province' => RefProvinceModel::query()
                ->leftJoin('ref_region', function ($join) {
                    $join->on('ref_province.regcode', '=', DB::raw("LPAD(ref_region.regcode, 2, '0')"));
                })
                ->selectRaw("
                    ref_province.provcode as code,
                    ref_province.provname as name,
                    ref_province.regcode as parent_region_code,
                    ref_region.regname as parent_region_name,
                    ref_province.nscb_prov_code as nscb_code,
                    ref_province.nscb_prov_name as nscb_name,
                    ref_province.newcode,
                    ref_province.status,
                    ref_province.dateupdated,
                    ref_province.addedby
                ")
                ->orderBy('ref_province.provname'),
            'city' => RefCityModel::query()
                ->leftJoin('ref_region', function ($join) {
                    $join->on('ref_city.regcode', '=', DB::raw("LPAD(ref_region.regcode, 2, '0')"));
                })
                ->leftJoin('ref_province', 'ref_city.provcode', '=', 'ref_province.provcode')
                ->selectRaw("
                    ref_city.citycode as code,
                    ref_city.cityname as name,
                    ref_city.regcode as parent_region_code,
                    ref_region.regname as parent_region_name,
                    ref_city.provcode as parent_province_code,
                    ref_province.provname as parent_province_name,
                    ref_city.nscb_city_code as nscb_code,
                    ref_city.nscb_city_name as nscb_name,
                    ref_city.cityclassification,
                    ref_city.chartered,
                    ref_city.newcode,
                    ref_city.status,
                    ref_city.dateupdated,
                    ref_city.addedby
                ")
                ->orderBy('ref_city.cityname'),
            'barangay' => RefBarangayModel::query()
                ->leftJoin('ref_region', function ($join) {
                    $join->on('ref_barangay.regcode', '=', DB::raw("LPAD(ref_region.regcode, 2, '0')"));
                })
                ->leftJoin('ref_province', 'ref_barangay.provcode', '=', 'ref_province.provcode')
                ->leftJoin('ref_city', 'ref_barangay.citycode', '=', 'ref_city.citycode')
                ->selectRaw("
                    ref_barangay.bgycode as code,
                    ref_barangay.bgyname as name,
                    ref_barangay.regcode as parent_region_code,
                    ref_region.regname as parent_region_name,
                    ref_barangay.provcode as parent_province_code,
                    ref_province.provname as parent_province_name,
                    ref_barangay.citycode as parent_city_code,
                    ref_city.cityname as parent_city_name,
                    ref_barangay.nscb_brgy_code as nscb_code,
                    ref_barangay.nscb_brgy_name as nscb_name,
                    ref_barangay.newcode,
                    ref_barangay.status,
                    ref_barangay.dateupdated,
                    ref_barangay.addedby
                ")
                ->orderBy('ref_barangay.bgyname'),
        };
    }

    private function transformRecord(string $level, object $row): array
    {
        if (
            method_exists($row, 'getAttribute')
            && ($row->getAttribute('code') !== null || $row->getAttribute('name') !== null)
        ) {
            return [
                'level' => $level,
                'code' => (string) ($row->getAttribute('code') ?? ''),
                'name' => (string) ($row->getAttribute('name') ?? ''),
                'status' => (string) ($row->getAttribute('status') ?? 'I'),
                'status_label' => ($row->getAttribute('status') ?? 'I') === 'A' ? 'Active' : 'Inactive',
                'updated_at' => $row->getAttribute('dateupdated'),
                'addedby' => $row->getAttribute('addedby'),
                'regabbrev' => $row->getAttribute('regabbrev'),
                'nscb_code' => $row->getAttribute('nscb_code'),
                'nscb_name' => $row->getAttribute('nscb_name'),
                'newcode' => $row->getAttribute('newcode'),
                'cityclassification' => $row->getAttribute('cityclassification'),
                'chartered' => $row->getAttribute('chartered'),
                'parent_region_code' => $row->getAttribute('parent_region_code'),
                'parent_region_name' => $row->getAttribute('parent_region_name'),
                'parent_province_code' => $row->getAttribute('parent_province_code'),
                'parent_province_name' => $row->getAttribute('parent_province_name'),
                'parent_city_code' => $row->getAttribute('parent_city_code'),
                'parent_city_name' => $row->getAttribute('parent_city_name'),
            ];
        }

        if ($row instanceof RefRegionModel) {
            return [
                'level' => 'region',
                'code' => $this->normalizeRegionCode($row->regcode),
                'name' => (string) $row->regname,
                'status' => (string) ($row->status ?? 'I'),
                'status_label' => ($row->status ?? 'I') === 'A' ? 'Active' : 'Inactive',
                'updated_at' => $row->dateupdated,
                'addedby' => $row->addedby,
                'regabbrev' => $row->regabbrev,
                'nscb_code' => $row->nscb_reg_code,
                'nscb_name' => $row->nscb_reg_name,
                'newcode' => null,
                'cityclassification' => null,
                'chartered' => null,
                'parent_region_code' => null,
                'parent_region_name' => null,
                'parent_province_code' => null,
                'parent_province_name' => null,
                'parent_city_code' => null,
                'parent_city_name' => null,
            ];
        }

        if ($row instanceof RefProvinceModel) {
            $regionName = optional($row->region)->regname;

            return [
                'level' => 'province',
                'code' => (string) $row->provcode,
                'name' => (string) $row->provname,
                'status' => (string) ($row->status ?? 'I'),
                'status_label' => ($row->status ?? 'I') === 'A' ? 'Active' : 'Inactive',
                'updated_at' => $row->dateupdated,
                'addedby' => $row->addedby,
                'regabbrev' => null,
                'nscb_code' => $row->nscb_prov_code,
                'nscb_name' => $row->nscb_prov_name,
                'newcode' => $row->newcode,
                'cityclassification' => null,
                'chartered' => null,
                'parent_region_code' => $row->regcode,
                'parent_region_name' => $regionName,
                'parent_province_code' => null,
                'parent_province_name' => null,
                'parent_city_code' => null,
                'parent_city_name' => null,
            ];
        }

        if ($row instanceof RefCityModel) {
            $provinceName = optional($row->province)->provname;
            $regionName = optional($row->region)->regname;

            return [
                'level' => 'city',
                'code' => (string) $row->citycode,
                'name' => (string) $row->cityname,
                'status' => (string) ($row->status ?? 'I'),
                'status_label' => ($row->status ?? 'I') === 'A' ? 'Active' : 'Inactive',
                'updated_at' => $row->dateupdated,
                'addedby' => $row->addedby,
                'regabbrev' => null,
                'nscb_code' => $row->nscb_city_code,
                'nscb_name' => $row->nscb_city_name,
                'newcode' => $row->newcode,
                'cityclassification' => $row->cityclassification,
                'chartered' => $row->chartered,
                'parent_region_code' => $row->regcode,
                'parent_region_name' => $regionName,
                'parent_province_code' => $row->provcode,
                'parent_province_name' => $provinceName,
                'parent_city_code' => null,
                'parent_city_name' => null,
            ];
        }

        if ($row instanceof RefBarangayModel) {
            $cityName = optional($row->city)->cityname;
            $provinceName = optional($row->province)->provname;
            $regionName = optional($row->region)->regname;

            return [
                'level' => 'barangay',
                'code' => (string) $row->bgycode,
                'name' => (string) $row->bgyname,
                'status' => (string) ($row->status ?? 'I'),
                'status_label' => ($row->status ?? 'I') === 'A' ? 'Active' : 'Inactive',
                'updated_at' => $row->dateupdated,
                'addedby' => $row->addedby,
                'regabbrev' => null,
                'nscb_code' => $row->nscb_brgy_code,
                'nscb_name' => $row->nscb_brgy_name,
                'newcode' => $row->newcode,
                'cityclassification' => null,
                'chartered' => null,
                'parent_region_code' => $row->regcode,
                'parent_region_name' => $regionName,
                'parent_province_code' => $row->provcode,
                'parent_province_name' => $provinceName,
                'parent_city_code' => $row->citycode,
                'parent_city_name' => $cityName,
            ];
        }

        return [
            'level' => $level,
            'code' => (string) ($row->code ?? ''),
            'name' => (string) ($row->name ?? ''),
            'status' => (string) ($row->status ?? 'I'),
            'status_label' => ($row->status ?? 'I') === 'A' ? 'Active' : 'Inactive',
            'updated_at' => $row->dateupdated ?? null,
            'addedby' => $row->addedby ?? null,
            'regabbrev' => $row->regabbrev ?? null,
            'nscb_code' => $row->nscb_code ?? null,
            'nscb_name' => $row->nscb_name ?? null,
            'newcode' => $row->newcode ?? null,
            'cityclassification' => $row->cityclassification ?? null,
            'chartered' => $row->chartered ?? null,
            'parent_region_code' => $row->parent_region_code ?? null,
            'parent_region_name' => $row->parent_region_name ?? null,
            'parent_province_code' => $row->parent_province_code ?? null,
            'parent_province_name' => $row->parent_province_name ?? null,
            'parent_city_code' => $row->parent_city_code ?? null,
            'parent_city_name' => $row->parent_city_name ?? null,
        ];
    }

    private function validatedPayload(Request $request, string $level, bool $isUpdate = false): array
    {
        $common = [
            'status' => ['required', 'in:A,I'],
        ];

        $rules = match ($level) {
            'region' => array_merge($common, [
                'regcode' => [$isUpdate ? 'sometimes' : 'required', 'string', 'regex:/^\d{1,2}$/'],
                'regname' => ['required', 'string', 'max:50'],
                'regabbrev' => ['nullable', 'string', 'max:10'],
                'nscb_reg_code' => ['nullable', 'string', 'max:2'],
                'nscb_reg_name' => ['nullable', 'string', 'max:50'],
            ]),
            'province' => array_merge($common, [
                'provcode' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:4'],
                'regcode' => ['required', 'string', 'size:2'],
                'provname' => ['required', 'string', 'max:100'],
                'nscb_prov_code' => ['nullable', 'string', 'max:4'],
                'nscb_prov_name' => ['nullable', 'string', 'max:100'],
                'newcode' => ['nullable', 'string', 'max:4'],
            ]),
            'city' => array_merge($common, [
                'citycode' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:6'],
                'regcode' => ['nullable', 'string', 'size:2'],
                'provcode' => ['required', 'string', 'max:4'],
                'cityname' => ['required', 'string', 'max:100'],
                'nscb_city_code' => ['nullable', 'string', 'max:6'],
                'nscb_city_name' => ['nullable', 'string', 'max:100'],
                'cityclassification' => ['nullable', 'integer', 'between:1,9'],
                'chartered' => ['nullable', 'in:Y,N'],
                'newcode' => ['nullable', 'string', 'max:6'],
            ]),
            'barangay' => array_merge($common, [
                'bgycode' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:9'],
                'regcode' => ['nullable', 'string', 'size:2'],
                'provcode' => ['nullable', 'string', 'max:4'],
                'citycode' => ['required', 'string', 'max:6'],
                'bgyname' => ['required', 'string', 'max:100'],
                'nscb_brgy_code' => ['nullable', 'string', 'max:9'],
                'nscb_brgy_name' => ['nullable', 'string', 'max:100'],
                'newcode' => ['nullable', 'string', 'max:9'],
            ]),
        };

        $validated = $request->validate($rules);

        if (! $isUpdate) {
            match ($level) {
                'region' => $this->ensureCodeAvailable(RefRegionModel::class, 'regcode', (int) $validated['regcode']),
                'province' => $this->ensureCodeAvailable(RefProvinceModel::class, 'provcode', $validated['provcode']),
                'city' => $this->ensureCodeAvailable(RefCityModel::class, 'citycode', $validated['citycode']),
                'barangay' => $this->ensureCodeAvailable(RefBarangayModel::class, 'bgycode', $validated['bgycode']),
            };
        }

        $timestamp = now();
        $actor = (string) ($request->user()?->name ?? 'system');
        $payload = [
            'status' => $validated['status'],
            'UserLevelID' => match ($level) {
                'region' => 1,
                'province' => 2,
                'city' => 3,
                'barangay' => 4,
            },
            'addedby' => $actor,
            'dateupdated' => $timestamp,
        ];

        if ($level === 'region') {
            return array_merge($payload, [
                'regcode' => array_key_exists('regcode', $validated) ? (int) $validated['regcode'] : null,
                'regname' => $validated['regname'],
                'regabbrev' => $validated['regabbrev'] ?? null,
                'nscb_reg_code' => $validated['nscb_reg_code'] ?? null,
                'nscb_reg_name' => $validated['nscb_reg_name'] ?? null,
            ]);
        }

        if ($level === 'province') {
            $region = $this->findRegionByCode($validated['regcode']);

            return array_merge($payload, [
                'provcode' => $validated['provcode'] ?? null,
                'regcode' => $this->normalizeRegionCode($region->regcode),
                'provname' => $validated['provname'],
                'nscb_prov_code' => $validated['nscb_prov_code'] ?? null,
                'nscb_prov_name' => $validated['nscb_prov_name'] ?? null,
                'newcode' => $validated['newcode'] ?? null,
            ]);
        }

        if ($level === 'city') {
            $province = $this->findProvinceByCode($validated['provcode']);

            return array_merge($payload, [
                'citycode' => $validated['citycode'] ?? null,
                'provcode' => $province->provcode,
                'regcode' => $this->normalizeRegionCode($province->regcode),
                'cityname' => $validated['cityname'],
                'nscb_city_code' => $validated['nscb_city_code'] ?? null,
                'nscb_city_name' => $validated['nscb_city_name'] ?? null,
                'cityclassification' => $validated['cityclassification'] ?? null,
                'chartered' => $validated['chartered'] ?? null,
                'newcode' => $validated['newcode'] ?? null,
            ]);
        }

        $city = $this->findCityByCode($validated['citycode']);

        return array_merge($payload, [
            'bgycode' => $validated['bgycode'] ?? null,
            'citycode' => $city->citycode,
            'provcode' => $city->provcode,
            'regcode' => $this->normalizeRegionCode($city->regcode),
            'bgyname' => $validated['bgyname'],
            'nscb_brgy_code' => $validated['nscb_brgy_code'] ?? null,
            'nscb_brgy_name' => $validated['nscb_brgy_name'] ?? null,
            'newcode' => $validated['newcode'] ?? null,
        ]);
    }

    private function ensureCodeAvailable(string $modelClass, string $column, mixed $value): void
    {
        if ($modelClass::query()->where($column, $value)->exists()) {
            throw ValidationException::withMessages([
                $column => 'This code is already in use.',
            ]);
        }
    }

    private function resolveModel(string $level): string
    {
        return match ($level) {
            'region' => RefRegionModel::class,
            'province' => RefProvinceModel::class,
            'city' => RefCityModel::class,
            'barangay' => RefBarangayModel::class,
        };
    }

    private function findRecord(string $level, string $id)
    {
        return match ($level) {
            'region' => RefRegionModel::query()->findOrFail((int) $id),
            'province' => RefProvinceModel::query()->findOrFail($id),
            'city' => RefCityModel::query()->findOrFail($id),
            'barangay' => RefBarangayModel::query()->findOrFail($id),
        };
    }

    private function findRegionByCode(string $code): RefRegionModel
    {
        return RefRegionModel::query()->findOrFail((int) $code);
    }

    private function findProvinceByCode(string $code): RefProvinceModel
    {
        return RefProvinceModel::query()->findOrFail($code);
    }

    private function findCityByCode(string $code): RefCityModel
    {
        return RefCityModel::query()->findOrFail($code);
    }
}
