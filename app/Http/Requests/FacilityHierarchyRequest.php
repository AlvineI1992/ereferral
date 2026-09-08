<?php

namespace App\Http\Requests;

use App\Enums\FacilityHierarchyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityHierarchyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('facility edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('facilityHierarchy')?->id;

        return [
            'facility_hfhudcode' => ['required', 'string', 'max:19', 'exists:ref_facilities,hfhudcode', Rule::unique('facility_hierarchies')->ignore($id)],
            'parent_hfhudcode' => ['nullable', 'string', 'max:19', 'different:facility_hfhudcode', 'exists:facility_hierarchies,facility_hfhudcode'],
            'level' => ['required', Rule::enum(FacilityHierarchyLevel::class)],
            'referral_network' => ['required', 'string', 'max:120'],
            'coverage_region_code' => ['required', 'string', 'max:10', 'exists:ref_region,regcode'],
            'coverage_province_code' => ['nullable', 'string', 'max:10', 'exists:ref_province,provcode'],
            'coverage_notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
