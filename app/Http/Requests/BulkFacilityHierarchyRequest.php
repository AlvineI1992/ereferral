<?php

namespace App\Http\Requests;

use App\Enums\FacilityHierarchyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkFacilityHierarchyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('facility edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'facility_hfhudcodes' => ['required', 'array', 'min:1', 'max:200'],
            'facility_hfhudcodes.*' => [
                'required', 'string', 'max:19', 'distinct', 'exists:ref_facilities,hfhudcode',
                Rule::unique('facility_hierarchies', 'facility_hfhudcode'),
            ],
            'parent_hfhudcode' => ['nullable', 'string', 'max:19', 'exists:facility_hierarchies,facility_hfhudcode'],
            'level' => ['required', Rule::enum(FacilityHierarchyLevel::class)],
            'referral_network' => ['required', 'string', 'max:120'],
            'coverage_notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
