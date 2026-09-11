<?php

use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

test('Sanctum ability middleware aliases are registered', function () {
    $aliases = app('router')->getMiddleware();

    expect($aliases)
        ->toHaveKey('abilities', CheckAbilities::class)
        ->toHaveKey('ability', CheckForAnyAbility::class);
});

test('every named middleware used by API routes resolves', function () {
    $router = app('router');
    $aliases = $router->getMiddleware();
    $groups = $router->getMiddlewareGroups();
    $unresolved = [];

    foreach ($router->getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'api/')) {
            continue;
        }

        foreach ($route->middleware() as $middleware) {
            $name = explode(':', $middleware, 2)[0];

            if (isset($aliases[$name]) || isset($groups[$name]) || class_exists($name)) {
                continue;
            }

            $unresolved[] = $route->uri().': '.$name;
        }
    }

    expect($unresolved)->toBeEmpty();
});
