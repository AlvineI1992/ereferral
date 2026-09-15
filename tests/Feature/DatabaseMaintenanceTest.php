<?php

use App\Models\User;
use App\Services\DatabaseMaintenanceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['cache.stores.file.path' => storage_path('framework/cache/maintenance-tests')]);
    Cache::store('file')->forget('database-maintenance-history');
});

afterEach(function () {
    Cache::store('file')->forget('database-maintenance-history');
});

test('database maintenance denies non administrators on all routes', function () {
    $this->actingAs(User::factory()->create(['status' => 'A']));
    $this->get('/admin/database-maintenance')->assertForbidden();
    $this->getJson('/admin/database-maintenance/status')->assertForbidden();
    $this->postJson('/admin/database-maintenance', ['action' => 'migrate', 'confirmation' => true])->assertForbidden();
});

test('administrator can preview pending migrations and approved seeders', function () {
    $this->actingAs(User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A']));
    $this->get('/admin/database-maintenance')->assertOk();
    $this->getJson('/admin/database-maintenance/status')->assertOk()->assertJsonCount(2, 'seeders');
    $this->postJson('/admin/database-maintenance', ['action' => 'migrate:fresh', 'confirmation' => true])->assertUnprocessable();
    $this->postJson('/admin/database-maintenance', ['action' => 'seed', 'seeder' => 'DatabaseSeeder', 'confirmation' => true])->assertUnprocessable();
    $this->postJson('/admin/database-maintenance', ['action' => 'migrate'])->assertUnprocessable();
});

test('approved seeder executes with fixed arguments and records results', function () {
    Artisan::shouldReceive('call')->once()->with('db:seed', ['--force' => true, '--no-interaction' => true, '--class' => Database\Seeders\ApiPermissionSeeder::class])->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn('Seed complete.');
    $result = app(DatabaseMaintenanceService::class)->run('seed', 'api-permissions', 1);
    expect($result['status'])->toBe('completed')->and($result['output'])->toBe('Seed complete.');
    expect(Cache::store('file')->get('database-maintenance-history')[0]['actor_id'])->toBe(1);
});

test('migration failure is recorded and releases the execution lock', function () {
    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true, '--no-interaction' => true])->andReturn(1);
    expect(app(DatabaseMaintenanceService::class)->run('migrate', null, 1)['status'])->toBe('failed');
    $handle = fopen(storage_path('framework/database-maintenance.lock'), 'c');
    expect(flock($handle, LOCK_EX | LOCK_NB))->toBeTrue();
    flock($handle, LOCK_UN); fclose($handle);
});

test('concurrent maintenance cannot execute another command', function () {
    $handle = fopen(storage_path('framework/database-maintenance.lock'), 'c');
    flock($handle, LOCK_EX);
    Artisan::shouldReceive('call')->never();
    try {
        $this->actingAs(User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A']));
        $this->postJson('/admin/database-maintenance', ['action' => 'migrate', 'confirmation' => true])->assertConflict();
    } finally { flock($handle, LOCK_UN); fclose($handle); }
});
