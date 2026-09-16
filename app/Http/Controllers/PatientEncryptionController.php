<?php

namespace App\Http\Controllers;

use App\Jobs\EncryptPatientPii;
use App\Models\PatientEncryptionSetting;
use App\Services\PatientEncryptionBackupService;
use App\Services\PatientPiiEncryption;
use App\Services\ReferralAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PatientEncryptionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $this->authorizeAdministrator($request);

        return response()->json($this->payload());
    }

    public function update(Request $request, PatientEncryptionBackupService $backups): JsonResponse
    {
        $this->authorizeAdministrator($request);
        $request->validate(['confirmation' => ['required', 'in:ENABLE PATIENT ENCRYPTION']]);
        $lock = Cache::lock('patient-pii-activation', 1300);
        abort_unless($lock->get(), 409, 'Patient encryption activation is already in progress.');
        try {
            abort_unless(collect($this->payload()['preflight'])->every(fn ($ready) => $ready), 422, 'Complete patient encryption preflight checks first.');
            $setting = PatientEncryptionSetting::current();
            if (in_array($setting->status, ['active', 'converting'], true)) {
                return response()->json(['message' => 'Patient encryption is already active or converting.', 'encryption' => $this->payload()], 202);
            }
            $backup = $backups->createAndVerify();
            $setting->update([
                'status' => 'converting', 'enabled' => false, 'total_rows' => $backup['rows'],
                'backup_path' => $backup['path'], 'backup_checksum' => $backup['checksum'],
                'activated_by' => $request->user()->id, 'activated_at' => now(), 'completed_at' => null, 'last_error' => null,
            ]);
            try {
                EncryptPatientPii::dispatch();
            } catch (Throwable) {
                $setting->update(['status' => 'failed', 'last_error' => 'Unable to complete or queue conversion. Retry activation after checking the queue.']);
                abort(503, 'Patient conversion could not start. Existing encrypted records remain protected.');
            }

            return response()->json(['message' => 'Verified patient backup completed and conversion queued.', 'encryption' => $this->payload()], 202);
        } finally {
            $lock->release();
        }
    }

    public function payload(): array
    {
        $settingsReady = Schema::hasTable('patient_encryption_settings');
        $setting = $settingsReady ? PatientEncryptionSetting::current() : new PatientEncryptionSetting;
        $columnsReady = Schema::hasTable('referral_pathway_steps');
        foreach (PatientPiiEncryption::FIELDS as $table => $columns) {
            $columnsReady = $columnsReady && Schema::hasColumns($table, $columns);
        }
        $key = config('ciphersweet.providers.string.key');

        return [
            'enabled' => (bool) $setting->enabled, 'status' => $setting->status ?? 'inactive',
            'processedRows' => (int) $setting->processed_rows, 'totalRows' => (int) $setting->total_rows,
            'lastError' => $setting->last_error, 'backupVerified' => filled($setting->backup_checksum),
            'preflight' => [
                'keyConfigured' => is_string($key) && strlen($key) === 32,
                'blindIndexesReady' => Schema::hasTable('patient_pii_indexes'),
                'settingsReady' => $settingsReady,
                'converterReady' => $columnsReady && Schema::hasTable('jobs'),
            ],
        ];
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless(app(ReferralAccessService::class)->isAdministrator($request->user()), 403);
    }
}
