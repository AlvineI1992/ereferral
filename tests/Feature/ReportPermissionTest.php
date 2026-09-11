<?php

use App\Models\User;
use App\Services\ReferralFacilityReportService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('incoming access alone no longer grants either report', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('incoming list', 'web'));
    $this->actingAs($user);
    foreach (['/reports/referrals-by-facility', '/reports/referrals-by-facility/data', '/reports/referrals-by-facility/patients', '/reports/referrals-by-facility/csv', '/reports/diagnosis-heatmap', '/reports/diagnosis-heatmap/data'] as $url) {
        $this->getJson($url)->assertForbidden();
    }
});

test('each report permission independently controls its page and navigation', function ($permission, $path, $otherPath, $referral, $heatmap) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    $this->actingAs($user)->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.navigation.reports', true)
        ->where('auth.user.navigation.referralReport', $referral)
        ->where('auth.user.navigation.diagnosisHeatmap', $heatmap));
    $this->getJson($otherPath)->assertForbidden();
    $user->revokePermissionTo($permission);
    $this->getJson($path)->assertForbidden();
})->with([
    ['referral report list', '/reports/referrals-by-facility', '/reports/diagnosis-heatmap', true, false],
    ['diagnosis heatmap list', '/reports/diagnosis-heatmap', '/reports/referrals-by-facility', false, true],
]);

test('referral data request accepts its own permission without incoming access', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('referral report list', 'web'));
    $this->mock(ReferralFacilityReportService::class, fn ($mock) => $mock->shouldReceive('report')->once()->andReturn(['data' => []]));
    $this->actingAs($user)->getJson('/reports/referrals-by-facility/data')->assertOk()->assertExactJson(['data' => []]);
});

test('report migration preserves role and direct grants without restoring later revocations', function () {
    $migration = require database_path('migrations/2026_09_11_000004_add_report_permissions.php');
    $migration->down();
    $incoming = Permission::findOrCreate('incoming list', 'web');
    $role = Role::findOrCreate('report-reader', 'web');
    $role->givePermissionTo($incoming);
    $user = User::factory()->create();
    $user->givePermissionTo($incoming);
    $migration->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['referral report list', 'diagnosis heatmap list'] as $permission) {
        expect($user->fresh()->hasDirectPermission($permission))->toBeTrue();
        expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();
    }
    $user->revokePermissionTo('diagnosis heatmap list');
    $migration->up();
    expect($user->fresh()->hasDirectPermission('diagnosis heatmap list'))->toBeFalse();
    expect(DB::table('permissions')->whereIn('name', ['referral report list', 'diagnosis heatmap list'])->count())->toBe(2);
});
