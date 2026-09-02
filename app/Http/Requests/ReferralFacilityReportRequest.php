<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferralFacilityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('incoming list');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'provider' => ['nullable', 'integer', 'exists:ref_emr,emr_id'],
            'referring_facility' => ['nullable', 'string', 'max:50', 'exists:ref_facilities,hfhudcode'],
            'referral_facility' => ['nullable', 'string', 'max:50', 'exists:ref_facilities,hfhudcode'],
        ];
    }
}
