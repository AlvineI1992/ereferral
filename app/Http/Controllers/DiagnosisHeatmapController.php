<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiagnosisHeatmapRequest;
use App\Services\DiagnosisHeatmapService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosisHeatmapController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/DiagnosisHeatmap', ['defaults' => ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]]);
    }

    public function data(DiagnosisHeatmapRequest $request, DiagnosisHeatmapService $service): JsonResponse
    {
        return response()->json($service->report($request->validated(), $request->user()))->header('Cache-Control', 'no-store, private');
    }
}
