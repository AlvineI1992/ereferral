<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Schema::create('referral_information', function ($table) {
        $table->string('LogID')->primary(); $table->string('fhudFrom'); $table->string('fhudTo');
    });
    Schema::create('referral_track', fn ($table) => $table->string('LogID')->primary());
    Schema::create('ref_facilities', function ($table) {
        $table->string('hfhudcode')->primary(); $table->string('emr_id');
    });
    DB::table('referral_information')->insert(['LogID' => 'REF-1', 'fhudFrom' => 'SOURCE', 'fhudTo' => 'DEST']);
    DB::table('ref_facilities')->insert([['hfhudcode' => 'SOURCE', 'emr_id' => '1'], ['hfhudcode' => 'DEST', 'emr_id' => '2']]);
});

function cancellationUser(string $provider = '1'): User
{
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => $provider]);
    $user->givePermissionTo(Permission::findOrCreate('referral cancel', 'api'));
    Sanctum::actingAs($user, ['referrals:write']);
    return $user;
}

test('source EMR cancels once and preserves the original cancellation on retries', function () {
    $user = cancellationUser();
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'Patient opted not to proceed.'])
        ->assertOk()->assertJsonPath('data.status', 'CANCELLED')->assertJsonPath('data.cancelled_by', $user->id);
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'Retry'])
        ->assertOk()->assertJsonPath('data.reason', 'Patient opted not to proceed.');
    $this->assertDatabaseCount('referral_cancellations', 1);
    $this->assertDatabaseHas('referral_information', ['LogID' => 'REF-1']);
});

test('destination EMR cannot cancel a source referral', function () {
    cancellationUser('2');
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'Wrong provider'])->assertForbidden();
    $this->assertDatabaseCount('referral_cancellations', 0);
});

test('received referrals cannot be cancelled and reasons are required', function () {
    cancellationUser();
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => ' '])->assertUnprocessable();
    DB::table('referral_track')->insert(['LogID' => 'REF-1']);
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'Too late'])->assertConflict();
});

test('cancelled referrals cannot be received or admitted', function () {
    $user = cancellationUser();
    $user->givePermissionTo(Permission::findOrCreate('referral receive', 'api'));
    $user->givePermissionTo(Permission::findOrCreate('referral admit', 'api'));
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'Cancelled'])->assertOk();
    $this->postJson('/api/received', ['LogID' => 'REF-1'])->assertConflict();
    $this->postJson('/api/admit', ['LogID' => 'REF-1'])->assertConflict();
});

test('cancel requires its own endpoint permission', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'EMR', 'access_id' => '1']);
    $user->givePermissionTo(Permission::findOrCreate('patient referral create', 'api'));
    Sanctum::actingAs($user, ['referrals:write']);
    $this->postJson('/api/cancel-referral', ['LogID' => 'REF-1', 'reason' => 'No grant'])->assertForbidden();
});

test('web cancellation requires web permission and shares cancellation rules', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'HOSP', 'access_id' => 'SOURCE']);
    $this->actingAs($user)->postJson('/referrals/cancel', ['LogID' => 'REF-1', 'reason' => 'Web cancellation'])->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('incoming cancel', 'web'));
    $this->postJson('/referrals/cancel', ['LogID' => 'REF-1', 'reason' => ' '])->assertUnprocessable();
    $this->postJson('/referrals/cancel', ['LogID' => 'REF-1', 'reason' => 'Web cancellation'])
        ->assertOk()->assertJsonPath('data.status', 'CANCELLED');
    $this->assertDatabaseHas('referral_cancellations', ['LogID' => 'REF-1', 'cancelled_by' => $user->id]);
});

test('web permission does not let destination users cancel referrals', function () {
    $user = User::factory()->create(['status' => 'A', 'access_type' => 'HOSP', 'access_id' => 'DEST']);
    $user->givePermissionTo(Permission::findOrCreate('incoming cancel', 'web'));
    $this->actingAs($user)->postJson('/referrals/cancel', ['LogID' => 'REF-1', 'reason' => 'Destination request'])->assertForbidden();
});
