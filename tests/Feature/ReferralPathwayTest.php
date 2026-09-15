<?php

use App\Models\User;
use App\Services\ReferralPathwayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Schema::create('referral_information', function ($table) {
        $table->string('LogID', 50)->primary();
        foreach (['fhudFrom', 'fhudTo', 'referralReason', 'remarks', 'referringProvider', 'referringProviderContactNumber', 'referralContactPerson',
            'referralContactPersonDesignation', 'otherReasons', 'refferalDate', 'refferalTime', 'logDate', 'created_at', 'calledDate', 'status'] as $column) $table->string($column)->nullable();
    });
    Schema::create('ref_facilities', function ($table) {
        $table->string('hfhudcode')->primary(); $table->string('facility_name'); $table->string('emr_id'); $table->string('status');
    });
    Schema::create('referral_track', function ($table) {
        $table->string('LogID')->primary(); $table->string('receivedDate')->nullable(); $table->string('admDate')->nullable(); $table->string('dischDate')->nullable();
    });
    foreach (['referral_patientinfo', 'referral_patientdemo', 'referral_clinical'] as $name) {
        Schema::create($name, function ($table) {
            $table->string('LogID')->primary(); $table->string('findings')->nullable();
        });
        DB::table($name)->insert(['LogID' => 'ORIGINAL', 'findings' => 'Original details']);
    }
    foreach (['A', 'B', 'C', 'D'] as $code) DB::table('ref_facilities')->insert(['hfhudcode' => $code, 'facility_name' => 'Facility '.$code, 'emr_id' => $code, 'status' => 'A']);
    DB::table('referral_information')->insert(['LogID' => 'ORIGINAL', 'fhudFrom' => 'A', 'fhudTo' => 'B', 'remarks' => 'Original referral', 'refferalDate' => '2026-09-14', 'refferalTime' => '10:00:00']);
});

function pathwayPayload(string $logId = 'ORIGINAL', string $destination = 'C'): array
{
    return ['LogID' => $logId, 'request_id' => (string) Str::uuid(), 'facility_to' => $destination,
        'reason' => \App\Helpers\ReferralHelper::getReferralReasons()[0]['code'], 'remarks' => 'Needs specialist care',
        'referring_provider' => 'Test Clinician', 'contact_number' => '09123456789', 'clinical_update' => 'Current findings'];
}

function pathwayUser(string $facility): User
{
    return User::factory()->create(['status' => 'A', 'access_type' => 'HOSP', 'access_id' => $facility]);
}

test('forwarding builds a complete ordered journey and preserves every previous leg', function () {
    $service = app(ReferralPathwayService::class);
    $b = pathwayUser('B');
    DB::table('referral_track')->insert(['LogID' => 'ORIGINAL', 'receivedDate' => '2026-09-14 11:00:00', 'admDate' => '2026-09-14 12:00:00']);
    $second = $service->forward($b, pathwayPayload());
    $third = $service->forward(pathwayUser('C'), pathwayPayload($second['LogID'], 'D'));
    $journey = $service->journey(pathwayUser('D'), $third['LogID']);
    expect($journey['root_LogID'])->toBe('ORIGINAL')->and($journey['current_LogID'])->toBe($third['LogID']);
    expect(array_column($journey['transactions'], 'status'))->toBe(['FORWARDED', 'FORWARDED', 'PENDING']);
    expect(array_column(array_column($journey['transactions'], 'source'), 'code'))->toBe(['A', 'B', 'C']);
    expect(array_column(array_column($journey['transactions'], 'destination'), 'code'))->toBe(['B', 'C', 'D']);
    expect($journey['transactions'][0]['admitted_at'])->toBe('2026-09-14 12:00:00');
    $this->assertDatabaseHas('referral_information', ['LogID' => 'ORIGINAL', 'fhudFrom' => 'A', 'fhudTo' => 'B', 'remarks' => 'Original referral']);
    $this->assertDatabaseHas('referral_clinical', ['LogID' => 'ORIGINAL', 'findings' => 'Original details']);
    $this->assertDatabaseHas('referral_clinical', ['LogID' => $second['LogID'], 'findings' => 'Current findings']);
    expect($service->journey($b, 'ORIGINAL')['transactions'][0]['clinical']['findings'])->toBe('Original details');
});

test('forwarding retries are idempotent and reject a reused key with different details', function () {
    $user = pathwayUser('B'); $payload = pathwayPayload(); $service = app(ReferralPathwayService::class);
    $first = $service->forward($user, $payload);
    expect($service->forward($user, $payload))->toBe($first);
    $this->assertDatabaseCount('referral_information', 2);
    $user->givePermissionTo(Permission::findOrCreate('incoming forward', 'web'));
    $this->actingAs($user)->postJson('/referrals/pathway', array_replace($payload, ['facility_to' => 'D']))->assertConflict();
    $this->postJson('/referrals/pathway', pathwayPayload())->assertConflict();
});

test('only current receiver can forward and invalid destinations are rejected', function () {
    $user = pathwayUser('A');
    $user->givePermissionTo(Permission::findOrCreate('incoming forward', 'web'));
    $this->actingAs($user)->postJson('/referrals/pathway', pathwayPayload())->assertForbidden();
    $user->update(['access_id' => 'B']);
    $this->postJson('/referrals/pathway', pathwayPayload('ORIGINAL', 'B'))->assertUnprocessable();
    DB::table('ref_facilities')->where('hfhudcode', 'C')->update(['status' => 'I']);
    $this->postJson('/referrals/pathway', pathwayPayload())->assertUnprocessable();
    $this->assertDatabaseCount('referral_information', 1);
});

test('failed clinical cloning rolls back new referral and journey rows', function () {
    DB::table('referral_patientinfo')->delete();
    $user = pathwayUser('B'); $user->givePermissionTo(Permission::findOrCreate('incoming forward', 'web'));
    $this->actingAs($user)->postJson('/referrals/pathway', pathwayPayload())->assertConflict();
    $this->assertDatabaseCount('referral_information', 1);
    $this->assertDatabaseCount('referral_pathway_steps', 0);
});

test('cancelled and discharged referrals cannot be forwarded', function () {
    $user = pathwayUser('B'); $user->givePermissionTo(Permission::findOrCreate('incoming forward', 'web'));
    DB::table('referral_track')->insert(['LogID' => 'ORIGINAL', 'dischDate' => '2026-09-15 12:00:00']);
    $this->actingAs($user)->postJson('/referrals/pathway', pathwayPayload())->assertConflict();
    DB::table('referral_track')->delete();
    DB::table('referral_cancellations')->insert(['LogID' => 'ORIGINAL', 'cancelled_by' => $user->id, 'reason' => 'Cancelled', 'cancelled_at' => now()]);
    $this->postJson('/referrals/pathway', pathwayPayload())->assertConflict();
});

test('journey data is protected and requires an authorized referral participant', function () {
    $user = pathwayUser('D');
    $this->actingAs($user)->getJson('/referrals/pathway?LogID=ORIGINAL')->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('incoming journey', 'web'));
    $this->getJson('/referrals/pathway?LogID=ORIGINAL')->assertForbidden();
    $user->update(['access_id' => 'B']);
    $this->getJson('/referrals/pathway?LogID=ORIGINAL')->assertOk()->assertJsonPath('can_forward', false);
});
