<?php

namespace App\Http\Controllers\Api;

use App\Enums\FacilityHierarchyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReferralNetworkRecommendationRequest;
use App\Services\ReferralNetworkRecommendationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ReferralNetworkRecommendationController extends Controller
{
    public function __invoke(
        ReferralNetworkRecommendationRequest $request,
        string $hfhudcode,
        ReferralNetworkRecommendationService $service
    ): JsonResponse {
        $targetLevel = $request->filled('target_level')
            ? FacilityHierarchyLevel::from($request->string('target_level')->toString())
            : null;

        try {
            $recommendation = $service->recommend($hfhudcode, $targetLevel);

            return response()->json([
                'success' => true,
                'data' => $recommendation,
                'message' => 'Referral facility recommendation generated.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'No active referral-network hierarchy is configured for the supplied facility.',
            ], 404);
        }
    }
}
