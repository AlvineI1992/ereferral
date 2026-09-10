<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== 'A') {
            $request->user()?->tokens()->delete();

            return response()->json([
                'error' => 'Account inactive',
                'message' => 'This account is not active.',
            ], 403);
        }

        return $next($request);
    }
}
