<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PatientEncryptionBackupService
{
    public function createAndVerify(): array
    {
        $directory = 'data-encryption-backups/patients-'.str()->uuid();
        $manifest = [];
        $rows = 0;
        // Each encrypted chunk is bounded in memory and independently verified.
        foreach ([...array_keys(PatientPiiEncryption::FIELDS), 'referral_pathway_steps'] as $table) {
            $key = match ($table) {
                'patient_master_list' => 'id',
                'referral_pathway_steps' => 'log_id',
                default => 'LogID',
            };
            DB::table($table)->chunkById(100, function ($records) use ($directory, $table, &$manifest, &$rows) {
                $payload = json_encode($records->all(), JSON_THROW_ON_ERROR);
                $path = $directory.'/'.$table.'-'.count($manifest).'.backup';
                $checksum = hash('sha256', $payload);
                if (! Storage::disk('local')->put($path, Crypt::encryptString($payload))
                    || ! hash_equals($checksum, hash('sha256', Crypt::decryptString(Storage::disk('local')->get($path))))) {
                    throw new RuntimeException('Patient backup verification failed.');
                }
                $manifest[] = ['path' => $path, 'checksum' => $checksum, 'rows' => $records->count()];
                $rows += $records->count();
            }, $key);
        }
        $payload = json_encode($manifest, JSON_THROW_ON_ERROR);
        $path = $directory.'/manifest.backup';
        $checksum = hash('sha256', $payload);
        if (! Storage::disk('local')->put($path, Crypt::encryptString($payload))
            || ! hash_equals($checksum, hash('sha256', Crypt::decryptString(Storage::disk('local')->get($path))))) {
            throw new RuntimeException('Patient backup manifest verification failed.');
        }

        return compact('path', 'checksum', 'rows');
    }
}
