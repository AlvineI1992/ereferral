<?php

namespace App\Http\Controllers;

use App\Models\DataEncryptionSetting;
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
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $this->authorizeAdministrator($request);

        return response()->json($this->payload(DataEncryptionSetting::current()));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdministrator($request);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'confirmation' => ['required_if:enabled,true', 'string'],
        ]);

        $setting = DataEncryptionSetting::current();

        if ($validated['enabled']) {
            abort_unless($validated['confirmation'] === 'ENABLE ENCRYPTION', 422, 'Type ENABLE ENCRYPTION to confirm.');

            return response()->json([
                'message' => 'The switch is ready, but activation is locked until the resumable data converter and backup preflight are complete.',
                'encryption' => $this->payload($setting),
            ], 409);
        }

        $setting->update([
            'enabled' => false,
            'status' => 'inactive',
            'last_error' => null,
        ]);

        return response()->json([
            'message' => 'CipherSweet activation remains off.',
            'encryption' => $this->payload($setting->fresh()),
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
                'keyConfigured' => filled(config('ciphersweet.providers.string.key')),
                'blindIndexesReady' => Schema::hasTable('blind_indexes'),
                'settingsReady' => Schema::hasTable('data_encryption_settings'),
            ],
        ];
    }

    private function authorizeAdministrator(Request $request): void
    {
        $roles = $request->user()?->getRoleNames()->map(fn ($role) => strtolower($role))->all() ?? [];
        abort_unless(in_array('admin', $roles, true) || in_array('super-admin', $roles, true), 403);
    }
}
