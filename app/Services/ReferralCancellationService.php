<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralCancellationService
{
    public function cancel(User $user, string $logId, string $reason): array
    {
        abort_unless($user->status === 'A', 403, 'An active account is required.');
        return DB::transaction(function () use ($user, $logId, $reason) {
            $referral = DB::table('referral_information')->where('LogID', $logId)->lockForUpdate()->first();
            abort_unless($referral, 404, 'Referral not found.');
            if (! $user->isSuperAdministrator()) {
                $source = DB::table('ref_facilities')->where('hfhudcode', $referral->fhudFrom);
                $authorized = match ($user->access_type) {
                    'EMR' => filled($user->access_id) && $source->where('emr_id', $user->access_id)->exists(),
                    'HOSP' => filled($user->access_id) && (string) $user->access_id === (string) $referral->fhudFrom,
                    default => false,
                };
                abort_unless($authorized, 403, 'Only the originating facility or its EMR provider may cancel this referral.');
            }
            abort_if(DB::table('referral_pathway_steps')->where('parent_log_id', $logId)->exists(), 409, 'This referral has already been forwarded.');
            $existing = DB::table('referral_cancellations')->where('LogID', $logId)->first();
            if ($existing) return $this->payload($existing);
            abort_if(DB::table('referral_track')->where('LogID', $logId)->exists(), 409, 'A received or admitted referral cannot be cancelled.');
            $record = ['LogID' => $logId, 'reason' => $reason, 'cancelled_by' => $user->id, 'cancelled_at' => now()->toDateTimeString()];
            DB::table('referral_cancellations')->insert($record);
            return $this->payload((object) $record);
        });
    }

    private function payload(object $record): array
    {
        return ['LogID' => $record->LogID, 'status' => 'CANCELLED', 'reason' => $record->reason,
            'cancelled_at' => $record->cancelled_at, 'cancelled_by' => $record->cancelled_by];
    }
}
