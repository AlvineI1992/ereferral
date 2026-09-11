<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('api users without a complete access scope cannot authenticate', function (?string $accessType, ?string $accessId) {
    $user = User::factory()->create([
        'access_type' => $accessType,
        'access_id' => $accessId,
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnauthorized()
        ->assertExactJson(['error' => 'Unauthorized']);

    expect($user->tokens()->count())->toBe(0);
})->with([
    'missing access type' => [null, 'FAC-001'],
    'missing access id' => ['HOSP', null],
    'missing both access fields' => [null, null],
]);

test('api users with a complete access scope can authenticate', function () {
    $user = User::factory()->create([
        'access_type' => 'HOSP',
        'access_id' => 'FAC-001',
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonStructure(['token']);

    expect($user->tokens()->count())->toBe(1);
});

test('the administrator account can authenticate through the api without an access scope', function () {
    $user = User::factory()->create([
        'email' => 'admin@referral.doh.gov.ph',
        'access_type' => null,
        'access_id' => null,
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonStructure(['token']);

    expect($user->tokens()->count())->toBe(1);
});
