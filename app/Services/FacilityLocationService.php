<?php

namespace App\Services;

use Illuminate\Http\Request;

class FacilityLocationService
{
    public function validatedCoordinates(Request $request): array
    {
        return $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
    }
}
