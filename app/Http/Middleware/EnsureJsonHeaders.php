<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!str_contains($request->header('accept'), 'application/json')) {
            return response()->json([
                'error' => 'Accept header must include application/json'
            ], 400);
        }

        if (!in_array($request->method(), ['GET', 'HEAD','POST','PUT']) &&
            $request->header('Content-Type') !== 'application/json') {
            return response()->json([
                'error' => 'Content-Type header must be application/json'
            ], 400);
        }

        return $next($request);
    }
}
