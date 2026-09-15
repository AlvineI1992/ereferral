<?php

use App\Models\User;
use App\Models\ReferralInformationModel;
use App\Models\RefFacilitiesModel;
use App\Services\ReferralAccessService;
use App\Services\FacilityRegionScope;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

test('the exact active administrator has every permission without role assignments', function () {
    $user = User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A']);
    foreach (['user list', 'incoming delete', 'future permission'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        expect($user->can($permission))->toBeTrue();
    }
    expect($user->roles)->toHaveCount(0);
    $this->withoutVite()->actingAs($user)->get('/users')->assertOk();
    $this->actingAs($user)->getJson('/admin/data-encryption/status')->assertOk();

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    $navigation = app(HandleInertiaRequests::class)->share($request)['auth']['user']['navigation'];
    foreach ($navigation as $key => $allowed) {
        if ($key !== 'appointments') {
            expect($allowed)->toBeTrue();
        }
    }
});

test('super administrator matching is exact and requires an active account', function ($email, $status, $allowed) {
    $user = User::factory()->create(['email' => $email, 'status' => $status]);
    Permission::findOrCreate('user delete', 'web');
    expect($user->isSuperAdministrator())->toBe($allowed)
        ->and($user->can('user delete'))->toBe($allowed);
})->with([
    [' ADMIN@REFERRAL.DOH.GOV.PH ', 'A', true],
    ['admin@referral.com', 'A', false],
    ['other@referral.doh.gov.ph', 'A', false],
    ['admin@referral.doh.gov.ph', 'I', false],
]);

test('administrator ignores assigned regional scope while ordinary accounts remain scoped', function () {
    $admin = User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A', 'access_type' => 'CHD', 'access_id' => '01']);
    $ordinary = User::factory()->create(['access_type' => 'CHD', 'access_id' => '01']);
    $service = app(ReferralAccessService::class);
    $query = ReferralInformationModel::query();
    $original = $query->toSql();
    $service->scopeReferrals($query, $admin);
    expect($query->toSql())->toBe($original);
    $service->scopeReferrals($query, $ordinary);
    expect($query->toSql())->not->toBe($original);
    $facilities = RefFacilitiesModel::query();
    $original = $facilities->toSql();
    app(FacilityRegionScope::class)->apply($facilities, $admin);
    app(FacilityRegionScope::class)->authorizeRegion($admin, '02');
    expect($facilities->toSql())->toBe($original);
});
