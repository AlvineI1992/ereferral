<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmrCredentialService;
use Illuminate\Http\JsonResponse;

class EmrCredentialController extends Controller
{
    public function show(User $user, EmrCredentialService $credentials): JsonResponse
    {
        return response()->json($credentials->status($user))->header('Cache-Control', 'no-store, private');
    }

    public function store(User $user, EmrCredentialService $credentials): JsonResponse
    {
        $token = $credentials->generate($user);

        return response()->json(['token' => $token, 'emr_id_token' => $token, 'message' => 'EMR token saved for this account and provider.'])
            ->header('Cache-Control', 'no-store, private');
    }

    public function destroy(User $user, EmrCredentialService $credentials): JsonResponse
    {
        $credentials->revoke($user);

        return response()->json(['message' => 'EMR credential revoked.']);
    }
}
