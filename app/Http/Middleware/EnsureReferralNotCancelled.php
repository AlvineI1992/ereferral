<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureReferralNotCancelled
{
    public function handle(Request $request, Closure $next)
    {
        if (! is_string($request->input('LogID'))) return $next($request);
        return DB::transaction(function () use ($request, $next) {
            DB::table('referral_information')->where('LogID', $request->input('LogID'))->lockForUpdate()->first();
            abort_if(DB::table('referral_pathway_steps')->where('parent_log_id', $request->input('LogID'))->exists(), 409, 'This referral has already been forwarded.');
            abort_if(DB::table('referral_cancellations')->where('LogID', $request->input('LogID'))->exists(), 409, 'This referral has been cancelled.');
            return $next($request);
        });
    }
}
