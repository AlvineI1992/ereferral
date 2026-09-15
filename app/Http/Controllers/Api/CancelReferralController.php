<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralCancellationService;
use Illuminate\Http\Request;

class CancelReferralController extends Controller
{
    /** Cancel an unreceived referral from the originating facility. Repeated requests return the original cancellation. */
    public function __invoke(Request $request, ReferralCancellationService $cancellations)
    {
        $request->merge(['reason' => is_string($request->input('reason')) ? trim($request->input('reason')) : $request->input('reason')]);
        $data = $request->validate(['LogID' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:1000']]);
        return response()->json(['success' => true, 'message' => 'Referral cancelled.',
            'data' => $cancellations->cancel($request->user(), $data['LogID'], $data['reason'])]);
    }
}
