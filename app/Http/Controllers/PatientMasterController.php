<?php

namespace App\Http\Controllers;

use App\Helpers\ReferralHelper;
use App\Models\PatientModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientMasterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(10, min(100, (int) $request->input('perPage', 10)));
        $search = trim((string) $request->input('search', ''));

        $query = PatientModel::query()
            ->leftJoin('ref_region', function ($join) {
                $join->on('patient_master_list.region_code', '=', DB::raw("LPAD(ref_region.regcode, 2, '0')"));
            })
            ->leftJoin('ref_province', 'patient_master_list.province_code', '=', 'ref_province.provcode')
            ->leftJoin('ref_city', 'patient_master_list.city_code', '=', 'ref_city.citycode')
            ->leftJoin('ref_barangay', 'patient_master_list.barangay_code', '=', 'ref_barangay.bgycode')
            ->select([
                'patient_master_list.*',
                'ref_region.regname as region_name',
                'ref_province.provname as province_name',
                'ref_city.cityname as city_name',
                'ref_barangay.bgyname as barangay_name',
            ])
            ->whereNull('patient_master_list.deleted_at');

        if ($search !== '') {
            $searchLike = '%' . strtoupper($search) . '%';
            $query->where(function ($builder) use ($searchLike) {
                $builder
                    ->whereRaw('UPPER(patient_master_list.last_name) LIKE ?', [$searchLike])
                    ->orWhereRaw('UPPER(patient_master_list.first_name) LIKE ?', [$searchLike])
                    ->orWhereRaw('UPPER(patient_master_list.middle_name) LIKE ?', [$searchLike])
                    ->orWhereRaw('UPPER(patient_master_list.phic_number) LIKE ?', [$searchLike])
                    ->orWhereRaw('UPPER(patient_master_list.family_id) LIKE ?', [$searchLike])
                    ->orWhereRaw('UPPER(patient_master_list.case_number) LIKE ?', [$searchLike]);
            });
        }

        $paginator = $query
            ->orderBy('patient_master_list.last_name')
            ->orderBy('patient_master_list.first_name')
            ->paginate($perPage)
            ->through(fn ($patient) => $this->transformListRow($patient));

        return response()->json($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $patient = PatientModel::query()->findOrFail($id);

        return response()->json([
            'data' => $this->transformDetailRow($patient),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $payload = $this->buildPayload($validated);

        $this->ensureNoDuplicate($payload);

        $patient = PatientModel::create($payload);

        return response()->json([
            'message' => 'Patient created successfully.',
            'data' => $this->transformDetailRow($patient),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $patient = PatientModel::query()->findOrFail($id);
        $validated = $this->validatePayload($request, $patient->id);
        $payload = $this->buildPayload($validated, $patient->legacy_log_id);

        $this->ensureNoDuplicate($payload, $patient->id);

        $patient->update($payload);

        return response()->json([
            'message' => 'Patient updated successfully.',
            'data' => $this->transformDetailRow($patient->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $patient = PatientModel::query()->findOrFail($id);
        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request, ?int $patientId = null): array
    {
        return $request->validate([
            'legacy_log_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('patient_master_list', 'legacy_log_id')->ignore($patientId),
            ],
            'family_id' => ['nullable', 'string', 'max:20'],
            'phic_number' => ['nullable', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['required', 'date'],
            'sex' => ['required', 'in:M,F'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'blood_type_rh' => ['nullable', 'string', 'max:10'],
            'civil_status' => ['nullable', 'string', 'max:50', Rule::in(ReferralHelper::getAcceptedCivilStatusInputs())],
            'street_address' => ['nullable', 'string', 'max:255'],
            'barangay_code' => ['required', 'string', 'max:10'],
            'city_code' => ['required', 'string', 'max:10'],
            'province_code' => ['required', 'string', 'max:10'],
            'region_code' => ['required', 'string', 'max:10'],
            'zip_code' => ['nullable', 'string', 'max:10'],
        ], [
            'last_name.required' => 'Last name is required.',
            'first_name.required' => 'First name is required.',
            'birth_date.required' => 'Birth date is required.',
            'sex.required' => 'Sex is required.',
            'region_code.required' => 'Region is required.',
            'province_code.required' => 'Province is required.',
            'city_code.required' => 'City / Municipality is required.',
            'barangay_code.required' => 'Barangay is required.',
            'civil_status.in' => 'Choose a valid civil status.',
        ]);
    }

    private function buildPayload(array $validated, ?string $legacyLogId = null): array
    {
        return [
            'legacy_log_id' => $this->normalizeValue($validated['legacy_log_id'] ?? $legacyLogId),
            'family_id' => $this->normalizeValue($validated['family_id'] ?? null),
            'phic_number' => $this->normalizeValue($validated['phic_number'] ?? null),
            'case_number' => $this->normalizeValue($validated['case_number'] ?? null),
            'last_name' => $this->normalizeValue($validated['last_name'] ?? null),
            'first_name' => $this->normalizeValue($validated['first_name'] ?? null),
            'middle_name' => $this->normalizeValue($validated['middle_name'] ?? null),
            'suffix' => $this->normalizeValue($validated['suffix'] ?? null),
            'birth_date' => $this->normalizeDate($validated['birth_date'] ?? null),
            'sex' => $this->normalizeValue($validated['sex'] ?? null),
            'contact_number' => $this->normalizeValue($validated['contact_number'] ?? null),
            'religion' => $this->normalizeValue($validated['religion'] ?? null),
            'blood_type' => $this->normalizeValue($validated['blood_type'] ?? null),
            'blood_type_rh' => $this->normalizeValue($validated['blood_type_rh'] ?? null),
            'civil_status' => ReferralHelper::normalizeCivilStatus($validated['civil_status'] ?? null),
            'street_address' => $this->normalizeValue($validated['street_address'] ?? null),
            'barangay_code' => $this->normalizeValue($validated['barangay_code'] ?? null),
            'city_code' => $this->normalizeValue($validated['city_code'] ?? null),
            'province_code' => $this->normalizeValue($validated['province_code'] ?? null),
            'region_code' => $this->normalizeValue($validated['region_code'] ?? null),
            'zip_code' => $this->normalizeValue($validated['zip_code'] ?? null),
        ];
    }

    private function ensureNoDuplicate(array $payload, ?int $ignoreId = null): void
    {
        $birthDate = $payload['birth_date'] ?? null;

        $identity = [
            'first_name' => $payload['first_name'] ?? null,
            'middle_name' => $payload['middle_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'suffix' => $payload['suffix'] ?? null,
            'civil_status' => $payload['civil_status'] ?? null,
        ];

        $query = PatientModel::query();

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        foreach ($identity as $column => $value) {
            $normalized = $this->normalizeValue($value) ?? '';
            $query->whereRaw("COALESCE(UPPER({$column}), '') = ?", [$normalized]);
        }

        if ($birthDate !== null) {
            $query->whereDate('birth_date', $birthDate);
        } else {
            $query->whereNull('birth_date');
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'form' => 'A patient with the same name, suffix, birth date, and civil status already exists.',
            ]);
        }
    }

    private function transformListRow(object $patient): array
    {
        $fullName = collect([
            $patient->last_name,
            $patient->first_name,
            $patient->middle_name,
            $patient->suffix,
        ])->filter(fn ($value) => filled($value))->implode(', ');

        $address = collect([
            $patient->street_address,
            $patient->barangay_name,
            $patient->city_name,
            $patient->province_name,
        ])->filter(fn ($value) => filled($value))->implode(', ');

        return [
            'id' => $patient->id,
            'legacy_log_id' => $patient->legacy_log_id,
            'full_name' => $fullName,
            'first_name' => $patient->first_name,
            'middle_name' => $patient->middle_name,
            'last_name' => $patient->last_name,
            'suffix' => $patient->suffix,
            'birth_date' => $patient->birth_date,
            'birth_date_label' => $patient->birth_date ? date('Y-m-d', strtotime((string) $patient->birth_date)) : 'N/A',
            'sex' => $patient->sex,
            'sex_label' => match ($patient->sex) {
                'M' => 'Male',
                'F' => 'Female',
                default => 'N/A',
            },
            'civil_status' => $patient->civil_status,
            'civil_status_label' => ReferralHelper::getCivilStatusDescription($patient->civil_status) ?? ($patient->civil_status ?: 'N/A'),
            'religion' => $patient->religion,
            'contact_number' => $patient->contact_number,
            'address' => $address,
            'region_name' => $patient->region_name,
            'province_name' => $patient->province_name,
            'city_name' => $patient->city_name,
            'barangay_name' => $patient->barangay_name,
            'zip_code' => $patient->zip_code,
        ];
    }

    private function transformDetailRow(PatientModel $patient): array
    {
        return [
            'id' => $patient->id,
            'legacy_log_id' => $patient->legacy_log_id,
            'family_id' => $patient->family_id,
            'phic_number' => $patient->phic_number,
            'case_number' => $patient->case_number,
            'last_name' => $patient->last_name,
            'first_name' => $patient->first_name,
            'middle_name' => $patient->middle_name,
            'suffix' => $patient->suffix,
            'birth_date' => optional($patient->birth_date)->format('Y-m-d'),
            'sex' => $patient->sex,
            'contact_number' => $patient->contact_number,
            'religion' => $patient->religion,
            'blood_type' => $patient->blood_type,
            'blood_type_rh' => $patient->blood_type_rh,
            'civil_status' => $patient->civil_status,
            'street_address' => $patient->street_address,
            'barangay_code' => $patient->barangay_code,
            'city_code' => $patient->city_code,
            'province_code' => $patient->province_code,
            'region_code' => $patient->region_code,
            'zip_code' => $patient->zip_code,
        ];
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
