<?php


namespace App\Http\Middleware;

use Closure;

class SecureHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Remove headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('server');

        // Modify headers
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Access-Control-Allow-Origin', 'https://your-domain.com'); // Not '*'

        return $response;
    }
}
