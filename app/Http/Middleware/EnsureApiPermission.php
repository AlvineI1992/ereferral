<?php

namespace App\Http\Middleware;

use App\Services\ApiPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiPermission
{
    public function __construct(private ApiPermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user(), 401, 'Please login to access this resource.');
        abort_unless($this->permissions->allows($request->user(), $permission), 403,
            'Your account does not have the required API permission: '.$permission.'.');

        return $next($request);
    }
}
