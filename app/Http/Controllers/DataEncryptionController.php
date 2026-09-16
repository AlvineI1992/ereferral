<?php

namespace App\Http\Controllers;

use App\Models\DataEncryptionSetting;
use App\Jobs\EncryptUserEmails;
use App\Services\DataEncryptionBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DataEncryptionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdministrator($request);

        $setting = DataEncryptionSetting::current();

        return Inertia::render('Admin/DataEncryption', [
            'encryption' => $this->payload($setting),
            'patientEncryption' => app(PatientEncryptionController::class)->payload(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $this->authorizeAdministrator($request);

        return response()->json($this->payload(DataEncryptionSetting::current()));
    }

    public function update(Request $request, DataEncryptionBackupService $backups): JsonResponse
    {
        $this->authorizeAdministrator($request);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'confirmation' => ['required_if:enabled,true', 'string'],
        ]);

        $setting = DataEncryptionSetting::current();

        if ($validated['enabled']) {
            abort_unless($validated['confirmation'] === 'ENABLE ENCRYPTION', 422, 'Type ENABLE ENCRYPTION to confirm.');

            if ($setting->enabled) {
                return response()->json(['message' => 'CipherSweet encryption is already active.', 'encryption' => $this->payload($setting)]);
            }

            if ($setting->status === 'converting') {
                return response()->json(['message' => 'Encryption conversion is already running.', 'encryption' => $this->payload($setting)], 202);
            }

            abort_unless($this->preflightReady(), 422, 'Complete all encryption preflight checks before activation.');

            $backup = $backups->createAndVerify();
            $setting->update([
                'enabled' => false,
                'status' => 'converting',
                'processed_rows' => 0,
                'total_rows' => $backup['rows'],
                'last_error' => null,
                'activated_by' => $request->user()->id,
                'activated_at' => now(),
                'completed_at' => null,
                'backup_path' => $backup['path'],
                'backup_checksum' => $backup['checksum'],
            ]);

            EncryptUserEmails::dispatch();

            return response()->json([
                'message' => 'The verified backup is complete and encryption conversion has started.',
                'encryption' => $this->payload($setting->fresh()),
            ], 202);
        }

        if ($setting->enabled || $setting->status === 'converting') {
            return response()->json([
                'message' => 'Disabling encryption requires a controlled decryption and restore operation.',
                'encryption' => $this->payload($setting),
            ], 409);
        }

        return response()->json([
            'message' => 'CipherSweet activation remains off.',
            'encryption' => $this->payload($setting),
        ]);
    }

    private function payload(DataEncryptionSetting $setting): array
    {
        return [
            'enabled' => $setting->enabled,
            'status' => $setting->status,
            'processedRows' => $setting->processed_rows,
            'totalRows' => $setting->total_rows,
            'lastError' => $setting->last_error,
            'activatedAt' => $setting->activated_at?->toIso8601String(),
            'completedAt' => $setting->completed_at?->toIso8601String(),
            'preflight' => [
                'keyConfigured' => $this->validKeyConfigured(),
                'blindIndexesReady' => Schema::hasTable('blind_indexes'),
                'settingsReady' => Schema::hasTable('data_encryption_settings'),
                'converterReady' => Schema::hasTable('jobs')
                    && Schema::hasColumn('data_encryption_settings', 'backup_path')
                    && Schema::hasColumn('data_encryption_settings', 'backup_checksum')
                    && Schema::hasColumn('users', 'email'),
            ],
            'backupVerified' => filled($setting->backup_path) && filled($setting->backup_checksum),
        ];
    }

    private function preflightReady(): bool
    {
        return collect($this->payload(DataEncryptionSetting::current())['preflight'])
            ->every(fn (bool $ready) => $ready);
    }

    private function validKeyConfigured(): bool
    {
        $key = config('ciphersweet.providers.string.key');

        return is_string($key) && strlen($key) === 32;
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless(app(\App\Services\ReferralAccessService::class)->isAdministrator($request->user()), 403);
    }
}
