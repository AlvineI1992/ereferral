<?php

use App\OpenApi\ApiOperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;

test('API documentation describes the enforced permission for every non login route', function () {
    foreach (app('router')->getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'api/')) {
            continue;
        }
        $operation = new Operation(strtolower($route->methods()[0]));
        (new ApiOperationTransformer)($operation, new RouteInfo($route, $route->methods()[0]));
        if ($route->uri() === 'api/login') {
            expect($operation->description)->toContain('Public. No API permission is required.')
                ->not->toContain('Required permission');
            expect(collect($route->gatherMiddleware())->contains(fn ($name) => str_starts_with($name, 'api.permission:')))->toBeFalse();
            continue;
        }
        $guard = collect($route->gatherMiddleware())->first(fn ($name) => str_starts_with($name, 'api.permission:'));
        expect($operation->description)->toContain('**Required permission:** `'.substr($guard, strlen('api.permission:')).'` under guard `api`')
            ->not->toContain('No endpoint-specific');
    }
});

test('documentation rejects new protected endpoints without permission middleware', function () {
    $route = new Route('GET', 'api/unprotected-example', fn () => null);
    $route->middleware('auth:sanctum');
    expect(fn () => (new ApiOperationTransformer)(new Operation('get'), new RouteInfo($route, 'GET')))
        ->toThrow(LogicException::class, 'Missing API permission');
});
