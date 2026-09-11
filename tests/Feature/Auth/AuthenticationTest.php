<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'access_type' => 'HOSP',
        'access_id' => 'FAC-001',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users without a complete access scope cannot authenticate', function (?string $accessType, ?string $accessId) {
    $user = User::factory()->create([
        'access_type' => $accessType,
        'access_id' => $accessId,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'Your account does not have an assigned access scope. Please contact an administrator.',
    ]);
})->with([
    'missing access type' => [null, 'FAC-001'],
    'missing access id' => ['HOSP', null],
    'missing both access fields' => [null, null],
    'blank access fields' => ['', ''],
]);

test('the administrator account can authenticate without an access scope', function () {
    $user = User::factory()->create([
        'email' => 'admin@referral.doh.gov.ph',
        'access_type' => null,
        'access_id' => null,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
