<?php

namespace App\Jobs;

use App\Models\DataEncryptionSetting;
use App\Models\User;
use App\Services\DataEncryptionManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use Throwable;

class EncryptUserEmails implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;
    public int $tries = 1;

    public function middleware(): array
    {
        return [(new WithoutOverlapping('data-encryption-activation'))->expireAfter(1300)];
    }

    public function handle(CipherSweet $engine, DataEncryptionManager $manager): void
    {
        $setting = DataEncryptionSetting::current();
        $encryptedRow = new EncryptedRow($engine, 'users');
        User::configureEncryptionSchema($encryptedRow);

        try {
            DB::table('users')->orderBy('id')->chunkById(100, function ($users) use ($encryptedRow, $setting) {
                foreach ($users as $user) {
                    $row = (array) $user;

                    try {
                        $plain = $encryptedRow->decryptRow($row);
                        $ciphertext = $row;
                        $indexes = $encryptedRow->getAllBlindIndexes($plain);
                    } catch (InvalidCiphertextException) {
                        [$ciphertext, $indexes] = $encryptedRow->prepareRowForStorage($row);
                    }

                    DB::transaction(function () use ($user, $ciphertext, $indexes) {
                        DB::table('users')->where('id', $user->id)->update(['email' => $ciphertext['email']]);

                        foreach ($indexes as $name => $value) {
                            DB::table('blind_indexes')->updateOrInsert([
                                'indexable_type' => (new User())->getMorphClass(),
                                'indexable_id' => $user->id,
                                'name' => $name,
                            ], ['value' => $value]);
                        }
                    });

                    $setting->increment('processed_rows');
                }
            });

            $setting->update([
                'enabled' => true,
                'status' => 'active',
                'completed_at' => now(),
                'last_error' => null,
            ]);
            $manager->forget();
            User::$cipherSweetEncryptedRow = null;
        } catch (Throwable $exception) {
            $setting->update(['enabled' => false, 'status' => 'failed', 'last_error' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
