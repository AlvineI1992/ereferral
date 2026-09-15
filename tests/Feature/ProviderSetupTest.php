<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['provider create', 'user create', 'user assign', 'user edit'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

test('provider setup requires every step permission', function (string $missing) {
    $manager = User::factory()->create();
    $manager->givePermissionTo(array_values(array_diff(['provider create', 'user create', 'user assign', 'user edit'], [$missing])));
    $this->actingAs($manager)->get(route('provider.setup'))->assertForbidden();
})->with(['provider create', 'user create', 'user assign', 'user edit']);

test('provider setup creates a linked user and assigns roles', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['provider create', 'user create', 'user assign', 'user edit']);
    $this->actingAs($manager)->get(route('provider.setup'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Emr/SetupWizard'));

    $provider = $this->postJson(route('emr.store'), [
        'emr_name' => 'Wizard Provider', 'status' => true, 'remarks' => '',
    ])->assertCreated()->json('data');

    $this->postJson(route('emr.store'), [
        'emr_name' => 'Wizard Provider', 'status' => true, 'remarks' => '',
    ])->assertUnprocessable()->assertJsonValidationErrors('emr_name');

    $user = $this->postJson(route('user.store'), [
        'name' => 'Wizard User', 'email' => 'wizard@example.com',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        'status' => true, 'access_type' => 'EMR', 'access_id' => (string) $provider['emr_id'],
    ])->assertCreated()->json('data');

    $role = Role::findOrCreate('Provider Operator', 'web');
    $this->patchJson(route('user.assign', $user['id']), ['roleids' => [$role->id]])
        ->assertOk()->assertJsonPath('success', true);

    $saved = User::findOrFail($user['id']);
    expect($saved->access_type)->toBe('EMR')
        ->and((string) $saved->access_id)->toBe((string) $provider['emr_id'])
        ->and($saved->hasRole($role))->toBeTrue();

    $token = $this->postJson(route('users.emr-credential.store', $saved))
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('emr_id_token');
    expect($token)->toMatch('/^emr_[a-f0-9]{64}$/');
    $this->assertDatabaseHas('emr_credentials', [
        'user_id' => $saved->id, 'emr_id' => $provider['emr_id'], 'token_hash' => hash('sha256', $token),
    ]);
    $this->getJson(route('users.emr-credential.show', $saved))
        ->assertOk()->assertJsonPath('active', true)->assertJsonMissingPath('token');
});
