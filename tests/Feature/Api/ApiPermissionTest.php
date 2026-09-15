<?php

use App\Models\User;
use App\Services\ApiPermissionService;
use Database\Seeders\ApiPermissionSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(ApiPermissionSeeder::class);
    Route::get('/api/guard-probe', fn () => response()->json(['ok' => true]))
        ->middleware(['api', 'auth:sanctum', 'active.api.user', 'abilities:reference:read', 'api.permission:demographics read']);
});

test('all protected API endpoints declare an API permission', function () {
    foreach (app('router')->getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'api/') || $route->uri() === 'api/login') {
            continue;
        }
        $middleware = collect($route->gatherMiddleware());
        expect($middleware->contains('auth:sanctum'))->toBeTrue($route->uri());
        $guard = $middleware->first(fn ($name) => str_starts_with($name, 'api.permission:'));
        expect($guard)->not->toBeNull($route->uri());
        expect(ApiPermissionService::permissions())->toContain(substr($guard, strlen('api.permission:')));
    }
});

test('API roles grant access without web permission leakage and revocation takes effect', function () {
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo(Permission::findOrCreate('demographics read', 'web'));
    Sanctum::actingAs($user, ['reference:read']);
    $this->getJson('/api/guard-probe')->assertForbidden();
    $role = Role::findOrCreate('API Reference Reader', 'api');
    $role->givePermissionTo(Permission::findByName('demographics read', 'api'));
    $user->assignRole($role);
    Sanctum::actingAs($user->fresh(), ['reference:read']);
    $this->getJson('/api/guard-probe')->assertOk();
    $user->removeRole($role);
    Sanctum::actingAs($user->fresh(), ['reference:read']);
    $this->getJson('/api/guard-probe')->assertForbidden();
});

test('API permissions do not replace authentication token abilities or active status', function () {
    $this->getJson('/api/guard-probe')->assertUnauthorized();
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo(Permission::findByName('demographics read', 'api'));
    Sanctum::actingAs($user, []);
    $this->getJson('/api/guard-probe')->assertForbidden();
    $user->update(['status' => 'I']);
    Sanctum::actingAs($user, ['reference:read']);
    $this->getJson('/api/guard-probe')->assertForbidden();
});

test('the exact active administrator retains API access', function () {
    $user = User::factory()->create(['status' => 'A', 'email' => 'admin@referral.doh.gov.ph']);
    Sanctum::actingAs($user, ['reference:read']);
    $this->getJson('/api/guard-probe')->assertOk();
});

test('API role permissions can be assigned through the existing administration workflow', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(Permission::findOrCreate('role assign', 'web'));
    $manager->givePermissionTo(Permission::findOrCreate('user assign', 'web'));
    $role = Role::findOrCreate('API Operator', 'api');
    $permission = Permission::findByName('demographics read', 'api');
    $this->actingAs($manager)->getJson('/permission-has-role?role_id='.$role->id.'&is_include=1')
        ->assertOk()->assertJsonPath('filter_options.guards', ['api']);
    $this->patchJson('/assign-permissions/'.$role->id, ['permissionids' => [$permission->id]])
        ->assertOk()->assertJsonPath('success', true);
    $user = User::factory()->create();
    $this->patchJson('/users/assign-roles/'.$user->id, ['roleids' => [$role->id]])->assertOk();
    expect($user->fresh()->checkPermissionTo('demographics read', 'api'))->toBeTrue()
        ->and($user->fresh()->checkPermissionTo('demographics read', 'web'))->toBeFalse();
});
