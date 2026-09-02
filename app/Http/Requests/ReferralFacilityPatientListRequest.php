<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReferralFacilityPatientListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('incoming list');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'provider' => ['nullable', 'integer', 'exists:ref_emr,emr_id'],
            'referring_facility' => ['nullable', 'string', 'max:50', 'exists:ref_facilities,hfhudcode'],
            'referral_facility' => ['nullable', 'string', 'max:50', 'exists:ref_facilities,hfhudcode'],
            'group_type' => ['required', Rule::in(['receiving_facility', 'rhu'])],
            'group_code' => ['required', 'string', 'max:50', 'exists:ref_facilities,hfhudcode'],
        ];
    }
}
