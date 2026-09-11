<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = null;

        try {
            $response = $next($request);

            return $response;
        } finally {
            $this->record($request, $response?->getStatusCode() ?? 500, $startedAt);
        }
    }

    private function record(Request $request, int $statusCode, int $startedAt): void
    {
        try {
            $user = $request->user();
            $route = $request->route();
            $routeUri = $route?->uri() ?? $request->path();

            DB::connection(config('audit.drivers.database.connection', config('database.default')))
                ->table(config('audit.drivers.database.table', 'audits'))
                ->insert([
                    'user_type' => $user?->getMorphClass(),
                    'user_id' => $user?->getAuthIdentifier(),
                    'event' => 'accessed',
                    'auditable_type' => 'API Access',
                    'auditable_id' => 0,
                    'old_values' => '{}',
                    'new_values' => json_encode([
                        'method' => $request->method(),
                        'route' => $route?->getName(),
                        'path' => '/'.$routeUri,
                        'status_code' => $statusCode,
                        'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                    ], JSON_THROW_ON_ERROR),
                    'url' => $request->url(),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 1023),
                    'tags' => 'api-access',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            Log::warning('API access audit could not be recorded.', [
                'method' => $request->method(),
                'path' => $request->path(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
