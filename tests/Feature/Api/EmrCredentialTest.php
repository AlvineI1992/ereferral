<?php

use App\Models\User;
use App\Services\EmrCredentialService;
use App\Services\ReferralAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('valid credentials return only untracked referrals for the requested provider facility', function () {
    Schema::create('ref_facilities', function ($table) {
        $table->string('hfhudcode')->primary();
        $table->string('emr_id');
        $table->string('facility_name');
    });
    Schema::create('referral_information', function ($table) {
        $table->string('LogID')->primary();
        $table->string('fhudFrom');
        $table->string('fhudTo');
        $table->string('refferalDate');
        $table->string('refferalTime');
    });
    Schema::create('referral_track', fn ($table) => $table->string('LogID')->primary());
    Schema::create('referral_patientinfo', function ($table) {
        foreach (['LogID', 'patientFirstName', 'patientMiddlename', 'patientLastName', 'patientSuffix', 'patientSex'] as $field) {
            $table->string($field);
        }
    });
    DB::table('ref_facilities')->insert([
        ['hfhudcode' => 'FAC-1', 'emr_id' => '1', 'facility_name' => 'Example One'],
        ['hfhudcode' => 'FAC-2', 'emr_id' => '2', 'facility_name' => 'Example Two'],
    ]);
    foreach (['pending', 'tracked', 'foreign'] as $id) {
        DB::table('referral_information')->insert(['LogID' => $id, 'fhudFrom' => 'FAC-2', 'fhudTo' => $id === 'foreign' ? 'FAC-2' : 'FAC-1', 'refferalDate' => '2026-09-11', 'refferalTime' => '12:00:00']);
        DB::table('referral_patientinfo')->insert(['LogID' => $id, 'patientFirstName' => 'Example', 'patientMiddlename' => '', 'patientLastName' => 'Patient', 'patientSuffix' => 'NOTAP', 'patientSex' => 'F']);
    }
    DB::table('referral_track')->insert(['LogID' => 'tracked']);
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $token = app(EmrCredentialService::class)->generate($user);
    Sanctum::actingAs($user, ['referrals:read']);
    $response = $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertOk()->assertJsonCount(1)->assertJsonPath('0.LogID', 'pending');
    $schema = \App\OpenApi\Examples\ReferralListResponse::schema()->toArray();
    expect($schema['type'])->toBe('array');
    expect(array_keys($response->json('0')))->toBe(array_keys($schema['items']['properties']));
    expect($schema['items']['properties']['LogID']['type'])->toBe('string');
    $this->getJson('/api/get-referral-list/FAC-2', ['X-EMR-Token' => $token])->assertForbidden();
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => ' '.$token.' '])->assertOk()->assertJsonCount(1);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
    $this->getJson('/api/get-referral-list/FAC-2', ['X-EMR-Token' => $token])->assertForbidden();
    DB::table('ref_facilities')->where('hfhudcode', 'FAC-1')->update(['emr_id' => '2']);
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertForbidden();
});

test('editors generate hashed credentials once and can revoke them', function () {
    Permission::findOrCreate('user edit', 'web');
    $editor = User::factory()->create();
    $editor->givePermissionTo('user edit');
    $provider = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $url = "/users/{$provider->id}/emr-credential";
    $this->actingAs($editor)->getJson($url)->assertOk()->assertExactJson(['saved' => false, 'active' => false, 'generated_at' => null]);
    $generated = $this->postJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $token = $generated->json('emr_id_token');
    expect($generated->json('token'))->toBe($token);
    $status = $this->getJson($url)->assertOk()->assertJsonPath('saved', true)->assertJsonPath('active', true);
    expect(array_keys($status->json()))->toBe(['saved', 'active', 'generated_at']);
    $provider->update(['access_id' => '2']);
    $this->getJson($url)->assertOk()->assertJsonPath('active', false);
    $provider->update(['access_id' => '1']);
    expect($token)->toMatch('/^emr_[a-f0-9]{64}$/');
    expect(DB::table('emr_credentials')->value('token_hash'))->toBe(hash('sha256', $token));
    expect(app(EmrCredentialService::class)->resolve($provider, $token))->toBe('1');
    $this->deleteJson($url)->assertOk();
    $this->getJson($url)->assertOk()->assertJsonPath('saved', false)->assertJsonPath('active', false);
    expect(fn () => app(EmrCredentialService::class)->resolve($provider, $token))->toThrow(HttpException::class);
});

test('credentials reject other accounts old tokens and changed assignments', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $other = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $service = app(EmrCredentialService::class);
    $old = $service->generate($user);
    $token = $service->generate($user);
    expect(fn () => $service->resolve($user, $old))->toThrow(HttpException::class);
    expect(fn () => $service->resolve($other, $token))->toThrow(HttpException::class);
    $user->update(['access_id' => '2']);
    expect(fn () => $service->resolve($user, $token))->toThrow(HttpException::class);
    $user->update(['access_id' => '1', 'status' => 'I']);
    expect(fn () => $service->resolve($user, $token))->toThrow(HttpException::class);
});

test('credential management requires edit permission and an active provider account', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => null, 'access_id' => null]);
    $url = "/users/{$user->id}/emr-credential";
    $this->actingAs($user)->postJson($url)->assertForbidden();
    $this->getJson($url)->assertForbidden();
    $this->deleteJson($url)->assertForbidden();
    Permission::findOrCreate('user edit', 'web');
    $user->givePermissionTo('user edit');
    $this->postJson($url)->assertUnprocessable();
});

test('referral list rejects absent numeric and foreign credentials before accessing patient data', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    Sanctum::actingAs($user, ['referrals:read']);
    $this->getJson('/api/get-referral-list/FAC-1')->assertForbidden();
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => '1'])->assertForbidden();
    $this->getJson('/api/get-referral-list/FAC-1/1')->assertNotFound();
    $other = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $token = app(EmrCredentialService::class)->generate($other);
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertForbidden();
    expect(DB::table('audits')->where('event', 'accessed')->get()->toJson())->not->toContain($token);
});

test('valid EMR credentials still require facility authorization and bearer abilities', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $token = app(EmrCredentialService::class)->generate($user);
    Sanctum::actingAs($user, []);
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertForbidden();
    Sanctum::actingAs($user, ['referrals:read']);
    $this->mock(ReferralAccessService::class, function ($mock) {
        $mock->shouldReceive('authorizeFacility')->once()->andThrow(new AccessDeniedHttpException);
    });
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertForbidden();
});

test('credential failures return actionable JSON without debug traces', function () {
    config(['app.debug' => true]);
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    Sanctum::actingAs($user, ['referrals:read']);
    $this->getJson('/api/get-referral-list/FAC-1')->assertForbidden()->assertExactJson([
        'message' => 'Missing X-EMR-Token header. Generate an EMR token on Users and paste it into X-EMR-Token.',
    ]);
    foreach (['1', 'Bearer emr_'.str_repeat('a', 64), '"emr_'.str_repeat('a', 64).'"'] as $token) {
        $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => $token])->assertForbidden()->assertExactJson([
            'message' => 'Invalid X-EMR-Token format. Paste only the generated emr_ token, without quotes or a Bearer prefix. Numeric EMR IDs are not accepted.',
        ]);
    }
    $this->getJson('/api/get-referral-list/FAC-1', ['X-EMR-Token' => 'emr_'.str_repeat('a', 64)])->assertForbidden()->assertExactJson([
        'message' => 'Invalid EMR credential. Use the latest generated token with the bearer token for the same EMR-provider account.',
    ]);
});
