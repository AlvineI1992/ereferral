<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DataEncryptionBackupService
{
    /** @return array{path: string, checksum: string, rows: int} */
    public function createAndVerify(): array
    {
        $payload = json_encode([
            'created_at' => now()->toIso8601String(),
            'users' => DB::table('users')->orderBy('id')->get(['id', 'email'])->all(),
        ], JSON_THROW_ON_ERROR);

        $checksum = hash('sha256', $payload);
        $path = 'data-encryption-backups/users-'.now()->format('Ymd-His').'-'.str()->uuid().'.backup';
        $encrypted = Crypt::encryptString($payload);

        if (! Storage::disk('local')->put($path, $encrypted)) {
            throw new RuntimeException('Unable to write the encrypted pre-conversion backup.');
        }

        $stored = Storage::disk('local')->get($path);
        $verified = Crypt::decryptString($stored);

        if (! hash_equals($checksum, hash('sha256', $verified))) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException('The pre-conversion backup could not be verified.');
        }

        return [
            'path' => $path,
            'checksum' => $checksum,
            'rows' => count(json_decode($verified, true, 512, JSON_THROW_ON_ERROR)['users']),
        ];
    }
}
