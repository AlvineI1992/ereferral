<?php

namespace App\Services;

use App\Models\User;

class LoginEligibilityService
{
    public function failureMessage(User $user): ?string
    {
        if ($user->status !== 'A') {
            return 'Your account is not active.';
        }

        if ($user->isSuperAdministrator()) {
            return null;
        }

        if (blank($user->access_type) || blank($user->access_id)) {
            return 'Your account does not have an assigned access scope. Please contact an administrator.';
        }

        return null;
    }
}
