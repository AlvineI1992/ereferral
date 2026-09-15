<?php

namespace App\Http\Controllers;

use App\Helpers\ReferralHelper;
use App\Services\ReferralPathwayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReferralPathwayController extends Controller
{
    public function index(Request $request, ReferralPathwayService $pathways)
    {
        return Inertia::render('Incoming/ReferralJourney', ['journey' => $this->payload($request, $pathways), 'selectedLogId' => $request->input('LogID')]);
    }

    /** Get the complete referral journey from any authorized transaction. */
    public function show(Request $request, ReferralPathwayService $pathways)
    {
        return response()->json($this->payload($request, $pathways));
    }

    /** Forward the current referral to another facility without overwriting earlier transactions. */
    public function store(Request $request, ReferralPathwayService $pathways)
    {
        $data = $request->validate([
            'LogID' => ['required', 'string', 'max:50'],
            'request_id' => ['required', 'uuid'],
            'facility_to' => ['required', 'string', 'max:19'],
            'reason' => ['required', Rule::in(array_column(ReferralHelper::getReferralReasons(), 'code'))],
            'other_reason' => ['nullable', 'string', 'max:50'],
            'remarks' => ['required', 'string', 'max:5000'],
            'referring_provider' => ['required', 'string', 'max:180'],
            'contact_number' => ['required', 'string', 'max:50'],
            'clinical_update' => ['nullable', 'string', 'max:10000'],
        ]);
        return response()->json(['success' => true, 'message' => 'Referral forwarded.', 'data' => $pathways->forward($request->user(), $data)], 201);
    }

    public function options(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        return response()->json(['facilities' => DB::table('ref_facilities')->where('status', 'A')
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where('facility_name', 'like', '%'.$search.'%'))
            ->orderBy('facility_name')->orderBy('hfhudcode')->limit(50)->get(['hfhudcode', 'facility_name']),
            'reasons' => ReferralHelper::getReferralReasons()]);
    }

    private function payload(Request $request, ReferralPathwayService $pathways): array
    {
        $data = $request->validate(['LogID' => ['required', 'string', 'max:50']]);
        $journey = $pathways->journey($request->user(), $data['LogID']);
        $permission = $request->is('api/*')
            ? app(\App\Services\ApiPermissionService::class)->allows($request->user(), 'referral forward')
            : $request->user()->can('incoming forward');
        $journey['can_forward'] = $journey['can_forward'] && $permission;
        return $journey;
    }
}
