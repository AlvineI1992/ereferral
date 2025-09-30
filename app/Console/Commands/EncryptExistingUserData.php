<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class EncryptExistingUserData extends Command
{
    protected $signature = 'users:encrypt-existing';
    protected $description = 'Encrypt existing name and email fields for users';

    public function handle()
    {
        $this->info('Starting encryption of existing users...');

        User::chunk(100, function ($users) {
            foreach ($users as $user) {

                $updated = false;

                // Encrypt name if not already encrypted
                try {
                    Crypt::decrypt($user->name);
                    $this->info("User {$user->id} name already encrypted.");
                } catch (\Exception $e) {
                    $user->name = Crypt::encryptString($user->name);
                    $updated = true;
                }

                // Encrypt email if not already encrypted
                try {
                    Crypt::decrypt($user->email);
                    $this->info("User {$user->id} email already encrypted.");
                } catch (\Exception $e) {
                    $user->email = Crypt::encryptString($user->email);
                    $updated = true;
                }

                if ($updated) {
                    $user->save();
                    $this->info("User {$user->id} encrypted.");
                }
            }
        });

        $this->info('Encryption complete!');
        return 0;
    }
}
