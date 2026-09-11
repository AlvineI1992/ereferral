<?php

namespace App\Rules;

use App\Models\User;
use App\Services\DataEncryptionManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEncryptedEmail implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        $query = User::query()->where(function ($query) use ($email) {
            $query->whereBlind('email', 'email_index', $email);

            if (app(DataEncryptionManager::class)->isConverting()) {
                $query->orWhere('email', $email);
            }
        });

        if ($this->ignoreUserId !== null) {
            $query->whereKeyNot($this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('validation.unique')->translate();
        }
    }
}
