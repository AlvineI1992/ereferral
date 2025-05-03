<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // If the user is not authenticated, return a JSON response with an error
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthorized access. Please provide a valid Bearer token.'
            ], 401);
        }

        return $next($request);
    }
}
