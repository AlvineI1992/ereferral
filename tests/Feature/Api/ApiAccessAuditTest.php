<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('api access is audited without storing credentials', function () {
    $user = User::factory()->create([
        'access_type' => 'HOSP',
        'access_id' => 'FAC-001',
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $audit = DB::table('audits')->where('event', 'accessed')->latest('id')->first();
    $details = json_decode($audit->new_values, true, flags: JSON_THROW_ON_ERROR);

    expect($audit)
        ->not->toBeNull()
        ->and($audit->user_id)->toBe($user->id)
        ->and($audit->tags)->toBe('api-access')
        ->and($details)->toMatchArray([
            'method' => 'POST',
            'path' => '/api/login',
            'status_code' => 200,
        ])
        ->and($audit->new_values)->not->toContain('password');
});
