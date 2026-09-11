<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisHeatmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('diagnosis heatmap list') ?? false;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from', 'before_or_equal:today'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
