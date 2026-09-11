<?php

namespace App\Services;

use App\Exceptions\InvalidEmrCredentialException;
use App\Models\User;
use App\Models\RefFacilitiesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmrCredentialService
{
    public function generate(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($user->status !== 'A' || $user->access_type !== 'EMR' || ! filled($user->access_id)) {
                throw ValidationException::withMessages(['user' => 'Select an active user assigned to an EMR provider.']);
            }

            $token = 'emr_'.bin2hex(random_bytes(32));
            DB::table('emr_credentials')->updateOrInsert(['user_id' => $user->id], [
                'emr_id' => $user->access_id,
                'token_hash' => hash('sha256', $token),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $token;
        });
    }

    public function revoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            User::query()->lockForUpdate()->findOrFail($user->id);
            DB::table('emr_credentials')->where('user_id', $user->id)->delete();
        });
    }

    public function resolve(User $user, ?string $token): string
    {
        $token = $token === null ? null : trim($token);
        if ($token === null || trim($token) === '') {
            throw new InvalidEmrCredentialException('Missing X-EMR-Token header. Generate an EMR token on Users and paste it into X-EMR-Token.');
        }
        if (! preg_match('/\Aemr_[a-f0-9]{64}\z/', $token)) {
            throw new InvalidEmrCredentialException('Invalid X-EMR-Token format. Paste only the generated emr_ token, without quotes or a Bearer prefix. Numeric EMR IDs are not accepted.');
        }
        $credential = DB::table('emr_credentials')->where('user_id', $user->id)->first();
        if (! (
            $user->status === 'A' && $user->access_type === 'EMR' && $credential
            && (string) $credential->emr_id === (string) $user->access_id
            && hash_equals($credential->token_hash, hash('sha256', $token))
        )) {
            throw new InvalidEmrCredentialException;
        }

        return (string) $credential->emr_id;
    }

    public function status(User $user): array
    {
        $credential = DB::table('emr_credentials')->where('user_id', $user->id)->first();

        return [
            'saved' => $credential !== null,
            'active' => $credential !== null && $user->status === 'A' && $user->access_type === 'EMR'
                && (string) $credential->emr_id === (string) $user->access_id,
            'generated_at' => $credential?->updated_at,
        ];
    }

    public function authorizeFacility(string $emrId, string $facilityCode): void
    {
        abort_unless(RefFacilitiesModel::query()->where('hfhudcode', $facilityCode)->where('emr_id', $emrId)->exists(),
            403, 'This facility is not assigned to the EMR provider associated with this token.');
    }
}
