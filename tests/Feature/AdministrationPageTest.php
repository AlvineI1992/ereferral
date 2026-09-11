<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('user management resolves the case-sensitive Inertia page', function () {
    Permission::findOrCreate('user list', 'web');
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo('user list');

    $this->actingAs($user)
        ->get(route('user.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('users/Index'));
});

test('role management resolves the case-sensitive Inertia page', function () {
    Permission::findOrCreate('role list', 'web');
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo('role list');

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('roles/Index'));
});

test('navigation does not expose an appointment link without a registered route', function () {
    Permission::findOrCreate('user list', 'web');
    Permission::findOrCreate('appointment list', 'web');
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo(['user list', 'appointment list']);

    $this->actingAs($user)
        ->get(route('user.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.navigation.appointments', false));
});

test('creating a role redirects to the registered role index route', function () {
    Permission::findOrCreate('role create', 'web');
    $user = User::factory()->create(['status' => 'A']);
    $user->givePermissionTo('role create');

    $this->actingAs($user)
        ->post(route('roles.store'), [
            'name' => 'referral coordinator',
            'guard_name' => 'web',
        ])
        ->assertRedirectToRoute('roles.index')
        ->assertSessionHas('success', 'Role created successfully.');
});
