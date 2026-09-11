<?php

namespace App\Services;

use App\Models\User;

class LoginEligibilityService
{
    private const UNSCOPED_ADMIN_EMAIL = 'admin@referral.doh.gov.ph';

    public function failureMessage(User $user): ?string
    {
        if ($user->status !== 'A') {
            return 'Your account is not active.';
        }

        if (strtolower(trim((string) $user->email)) === self::UNSCOPED_ADMIN_EMAIL) {
            return null;
        }

        if (blank($user->access_type) || blank($user->access_id)) {
            return 'Your account does not have an assigned access scope. Please contact an administrator.';
        }

        return null;
    }
}
