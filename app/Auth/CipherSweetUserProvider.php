<?php

namespace App\Auth;

use App\Services\DataEncryptionManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CipherSweetUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || ! array_key_exists('email', $credentials)
            || ! app(DataEncryptionManager::class)->isEnabled()) {
            return parent::retrieveByCredentials($credentials);
        }

        $model = $this->createModel();
        $email = strtolower(trim((string) $credentials['email']));
        $manager = app(DataEncryptionManager::class);
        $query = $model->newQuery()->where(function ($query) use ($email, $manager) {
            $query->whereBlind('email', 'email_index', $email);

            if ($manager->isConverting()) {
                $query->orWhere('email', $email);
            }
        });

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password') && $key !== 'email') {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }
}
