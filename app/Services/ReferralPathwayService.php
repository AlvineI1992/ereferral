<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReferralPathwayService
{
    private const COPIED_TABLES = ['referral_patientinfo', 'referral_patientdemo', 'referral_clinical'];

    public function forward(User $user, array $data): array
    {
        abort_unless($user->status === 'A', 403, 'An active account is required.');
        return DB::transaction(function () use ($user, $data) {
            $requestHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
            $parent = DB::table('referral_information')->where('LogID', $data['LogID'])->lockForUpdate()->first();
            abort_unless($parent, 404, 'Referral not found.');
            abort_unless($this->ownsDestination($user, $parent), 403, 'Only the receiving facility or its EMR provider may forward this referral.');
            $retry = DB::table('referral_pathway_steps')->where('request_id', $data['request_id'])->first();
            if ($retry) {
                abort_unless($retry->parent_log_id === $parent->LogID && (int) $retry->created_by === $user->id
                    && hash_equals((string) $retry->request_hash, $requestHash), 409, 'Request ID already used with different details.');
                return ['LogID' => $retry->log_id, 'root_LogID' => $retry->root_log_id, 'parent_LogID' => $parent->LogID];
            }
            abort_if(DB::table('referral_pathway_steps')->where('parent_log_id', $parent->LogID)->exists(), 409, 'This referral has already been forwarded. Open the latest transaction.');
            abort_if(DB::table('referral_cancellations')->where('LogID', $parent->LogID)->exists(), 409, 'A cancelled referral cannot be forwarded.');
            $track = DB::table('referral_track')->where('LogID', $parent->LogID)->first();
            abort_if(filled($track?->dischDate), 409, 'A discharged referral cannot be forwarded.');
            abort_if($data['facility_to'] === $parent->fhudTo, 422, 'Select a different receiving facility.');
            $destination = DB::table('ref_facilities')->where('hfhudcode', $data['facility_to'])->where('status', 'A')->first();
            abort_unless($destination, 422, 'Select an active destination facility.');
            $step = DB::table('referral_pathway_steps')->where('log_id', $parent->LogID)->first();
            $root = $step?->root_log_id ?? $parent->LogID;
            $sequence = $step?->sequence ?? 1;
            $snapshot = $this->snapshot($parent);
            if (! $step) {
                DB::table('referral_pathway_steps')->insert(['log_id' => $parent->LogID, 'root_log_id' => $root,
                    'sequence' => 1, 'snapshot' => app(PatientPiiEncryption::class)->snapshot($snapshot, $parent->LogID), 'created_at' => now()]);
            }
            // Freeze the clinical and workflow state of this leg before creating the next one.
            DB::table('referral_pathway_steps')->where('log_id', $parent->LogID)
                ->update(['forwarded_at' => now(), 'snapshot' => app(PatientPiiEncryption::class)->snapshot($snapshot, $parent->LogID)]);
            $logId = 'FWD-'.Str::ulid();
            $next = (array) $parent;
            $next = array_replace($next, ['LogID' => $logId, 'fhudFrom' => $parent->fhudTo, 'fhudTo' => $destination->hfhudcode,
                'referralReason' => $data['reason'], 'remarks' => $data['remarks'], 'referringProvider' => $data['referring_provider'],
                'referringProviderContactNumber' => $data['contact_number'], 'referralContactPerson' => $data['referring_provider'],
                'referralContactPersonDesignation' => 'Referring provider', 'otherReasons' => $data['other_reason'] ?? null,
                'refferalDate' => now()->toDateString(), 'refferalTime' => now()->format('H:i:s'), 'logDate' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(), 'calledDate' => null, 'status' => null]);
            if (array_key_exists('updated_at', $next)) $next['updated_at'] = now()->toDateTimeString();
            if (array_key_exists('deleted_at', $next)) $next['deleted_at'] = null;
            DB::table('referral_information')->insert($next);
            foreach (self::COPIED_TABLES as $table) {
                $rows = DB::table($table)->where('LogID', $parent->LogID)->get();
                if ($table === 'referral_patientinfo') abort_if($rows->isEmpty(), 409, 'The original patient record is missing.');
                foreach ($rows as $row) {
                    $copy = (array) $row;
                    $copy['LogID'] = $logId;
                    if ($table === 'referral_clinical' && filled($data['clinical_update'] ?? null)) {
                        $copy['findings'] = $data['clinical_update'];
                    }
                    if (isset(PatientPiiEncryption::FIELDS[$table])) {
                        $class = $table === 'referral_patientinfo' ? \App\Models\ReferralPatientInfoModel::class : \App\Models\ReferralPatientDemoModel::class;
                        $plain = app(PatientPiiEncryption::class)->decryptRecord($table, (object) $copy);
                        $model = new $class;
                        $model->forceFill((array) $plain)->save();
                    } else {
                        DB::table($table)->insert($copy);
                    }
                }
                if ($table === 'referral_clinical' && $rows->isEmpty() && filled($data['clinical_update'] ?? null)) {
                    DB::table($table)->insert(['LogID' => $logId, 'findings' => $data['clinical_update']]);
                }
            }
            DB::table('referral_pathway_steps')->insert(['log_id' => $logId, 'root_log_id' => $root,
                'parent_log_id' => $parent->LogID, 'sequence' => $sequence + 1, 'request_id' => $data['request_id'], 'request_hash' => $requestHash,
                'created_by' => $user->id, 'snapshot' => app(PatientPiiEncryption::class)->snapshot($this->snapshot((object) $next), $logId), 'created_at' => now()]);
            return ['LogID' => $logId, 'root_LogID' => $root, 'parent_LogID' => $parent->LogID];
        });
    }

    public function journey(User $user, string $logId): array
    {
        app(ReferralAccessService::class)->authorizeReferral($user, $logId);
        $step = DB::table('referral_pathway_steps')->where('log_id', $logId)->first();
        $root = $step?->root_log_id ?? $logId;
        $steps = DB::table('referral_pathway_steps')->where('root_log_id', $root)->orderBy('sequence')->get();
        if ($steps->isEmpty()) {
            $steps = collect([(object) ['log_id' => $logId, 'parent_log_id' => null, 'sequence' => 1, 'forwarded_at' => null, 'created_by' => null]]);
        }
        $records = DB::table('referral_information')->whereIn('LogID', $steps->pluck('log_id'))->get()->keyBy('LogID');
        $transactions = $steps->map(function ($step) use ($records) {
            $snapshot = $step->forwarded_at || !isset($records[$step->log_id])
                ? app(PatientPiiEncryption::class)->readSnapshot($step->snapshot, $step->log_id)
                : $this->snapshot($records[$step->log_id]);
            return array_merge($snapshot, ['LogID' => $step->log_id, 'parent_LogID' => $step->parent_log_id,
                'sequence' => $step->sequence, 'created_by' => $step->created_by,
                'status' => $step->forwarded_at ? 'FORWARDED' : $snapshot['status'], 'forwarded_at' => $step->forwarded_at]);
        })->all();
        $latest = $steps->last();
        $current = $records->get($latest->log_id);
        $latestStatus = $transactions[count($transactions) - 1]['status'];
        return ['root_LogID' => $root, 'current_LogID' => $latest->log_id, 'transactions' => $transactions,
            'can_forward' => $current && $user->status === 'A' && $this->ownsDestination($user, $current)
                && !in_array($latestStatus, ['CANCELLED', 'DISCHARGED'], true)];
    }

    private function ownsDestination(User $user, object $referral): bool
    {
        if ($user->isSuperAdministrator()) return true;
        if (!filled($user->access_id)) return false;
        return match ($user->access_type) {
            'HOSP' => (string) $user->access_id === (string) $referral->fhudTo,
            'EMR' => DB::table('ref_facilities')->where('hfhudcode', $referral->fhudTo)->where('emr_id', $user->access_id)->exists(),
            default => false,
        };
    }

    private function snapshot(object $referral): array
    {
        $track = DB::table('referral_track')->where('LogID', $referral->LogID)->first();
        $cancelled = DB::table('referral_cancellations')->where('LogID', $referral->LogID)->first();
        $facilities = DB::table('ref_facilities')->whereIn('hfhudcode', [$referral->fhudFrom, $referral->fhudTo])->pluck('facility_name', 'hfhudcode');
        return ['source' => ['code' => $referral->fhudFrom, 'name' => $facilities[$referral->fhudFrom] ?? $referral->fhudFrom],
            'destination' => ['code' => $referral->fhudTo, 'name' => $facilities[$referral->fhudTo] ?? $referral->fhudTo],
            'referred_at' => trim(($referral->refferalDate ?? '').' '.($referral->refferalTime ?? '')),
            'status' => $cancelled ? 'CANCELLED' : (filled($track?->dischDate) ? 'DISCHARGED' : (filled($track?->admDate) ? 'ADMITTED' : ($track ? 'RECEIVED' : 'PENDING'))),
            'received_at' => $track?->receivedDate, 'admitted_at' => $track?->admDate, 'discharged_at' => $track?->dischDate,
            'cancellation' => $cancelled, 'details' => $referral,
            'patient' => \App\Models\ReferralPatientInfoModel::find($referral->LogID)?->toArray(),
            'demographics' => \App\Models\ReferralPatientDemoModel::find($referral->LogID)?->toArray(),
            'clinical' => DB::table('referral_clinical')->where('LogID', $referral->LogID)->first(),
            'providers' => Schema::hasTable('referral_provider') ? DB::table('referral_provider')->where('LogID', $referral->LogID)->get()->all() : [],
            'medications' => Schema::hasTable('referral_medicine') ? DB::table('referral_medicine')->where('LogID', $referral->LogID)->get()->all() : [],
            'attachments' => Schema::hasTable('referral_attachments') ? DB::table('referral_attachments')->where('LogID', $referral->LogID)->get(['id', 'original_name', 'mime_type', 'size'])->all() : [],
            'workflow' => $track,
            'status_history' => Schema::hasTable('referral_status') ? DB::table('referral_status')->where('LogID', $referral->LogID)->orderBy('created_at')->get()->all() : [],
        ];
    }
}
