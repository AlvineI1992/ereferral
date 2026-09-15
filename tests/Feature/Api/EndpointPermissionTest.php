<?php

use App\Models\User;
use App\Services\ApiPermissionService;
use Database\Seeders\ApiPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('each real API operation has exactly its own distinct permission', function () {
    $seen = [];
    foreach (app('router')->getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'api/') || $route->uri() === 'api/login') continue;
        $key = $route->methods()[0].' '.$route->uri();
        $expected = ApiPermissionService::ENDPOINTS[$key]['permission'];
        $guards = collect($route->gatherMiddleware())->filter(fn ($name) => str_starts_with($name, 'api.permission:'))->values()->all();
        expect($guards)->toBe(['api.permission:'.$expected]);
        $seen[] = $expected;
    }
    expect(count($seen))->toBe(count(array_unique($seen)))
        ->and(count($seen))->toBe(count(ApiPermissionService::ENDPOINTS));
});

test('a single endpoint grant does not grant sibling operations', function () {
    $this->seed(ApiPermissionSeeder::class);
    $user = User::factory()->create(['status' => 'A']);
    $service = app(ApiPermissionService::class);
    foreach (ApiPermissionService::permissions() as $allowed) {
        $user->syncPermissions([Permission::findByName($allowed, 'api')]);
        foreach (ApiPermissionService::permissions() as $permission) {
            expect($service->allows($user, $permission))->toBe($permission === $allowed);
        }
    }
});

test('legacy role and direct grants migrate once without broad fallback or web changes', function () {
    $legacy = Permission::findOrCreate('beds write', 'api');
    $web = Permission::findOrCreate('beds write', 'web');
    $role = Role::findOrCreate('Existing EMR', 'api');
    $role->givePermissionTo($legacy);
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo($legacy);
    $this->seed(ApiPermissionSeeder::class);
    $this->seed(ApiPermissionSeeder::class);
    $expected = ['bed trackers create', 'bed tracker update', 'bed tracker delete'];
    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(collect($expected)->sort()->values()->all());
    $user = $user->fresh();
    foreach ($expected as $name) expect($user->checkPermissionTo($name, 'api'))->toBeTrue();
    expect($user->checkPermissionTo('bed trackers list', 'api'))->toBeFalse();
    $user->revokePermissionTo(Permission::findByName('bed tracker delete', 'api'));
    expect(app(ApiPermissionService::class)->allows($user, 'bed tracker delete'))->toBeFalse();
    $this->assertDatabaseMissing('permissions', ['name' => 'beds write', 'guard_name' => 'api']);
    $this->assertDatabaseHas('permissions', ['id' => $web->id, 'guard_name' => 'web']);
});
