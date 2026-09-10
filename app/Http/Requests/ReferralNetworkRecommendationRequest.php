<?php

namespace App\Http\Requests;

use App\Enums\FacilityHierarchyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReferralNetworkRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level' => ['nullable', Rule::enum(FacilityHierarchyLevel::class)],
        ];
    }
}
