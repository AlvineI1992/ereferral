<?php

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Schema::create('ref_facilities', function (Blueprint $table) {
        $table->string('hfhudcode')->primary();
        $table->string('facility_name')->nullable();
    });

    DB::table('ref_facilities')->insert([
        ['hfhudcode' => 'DOH000000000000001', 'facility_name' => 'Origin Hospital'],
        ['hfhudcode' => 'DOH000000000000002', 'facility_name' => 'Receiving Hospital'],
    ]);

    Sanctum::actingAs(User::factory()->create());
});

test('referrals can be created from the web referral form', function () {
    $mock = Mockery::mock(ReferralService::class);
    $mock->shouldReceive('refer_patient')
        ->once()
        ->withArgs(function (array $payload) {
            expect($payload['patient']['civil_status'])->toBe('S');

            return true;
        })
        ->andReturn([
            'code' => 'REF-UNIT-001',
            'message' => 'Referral successfully transmitted',
        ]);

    $this->app->instance(ReferralService::class, $mock);

    $response = $this->post(route('referral.store'), validReferralPayload());

    $response
        ->assertStatus(303)
        ->assertRedirect('/incoming');
});

test('referral creation validates required handoff fields', function () {
    $mock = Mockery::mock(ReferralService::class);
    $mock->shouldNotReceive('refer_patient');
    $this->app->instance(ReferralService::class, $mock);

    $response = $this->from('/referrals/create')->post(route('referral.store'), array_merge(
        validReferralPayload(),
        ['contactPerson' => '', 'diagnosis' => '']
    ));

    $response
        ->assertRedirect('/referrals/create')
        ->assertSessionHasErrors(['contactPerson', 'diagnosis']);
});

function validReferralPayload(): array
{
    return [
        'patientFirstName' => 'Juan',
        'patientMiddleName' => 'Santos',
        'patientLastName' => 'Dela Cruz',
        'patientSuffix' => 'Jr',
        'patientBirthDate' => '1990-01-01',
        'patientSex' => 'M',
        'patientCivilStatus' => 'Single',
        'patientContactNumber' => '09171234567',
        'familyNumber' => 'FAM-001',
        'caseNumber' => 'CASE-001',
        'phicNumber' => 'PHIC-001',
        'religion' => 'Catholic',
        'bloodType' => 'O',
        'bloodRh' => '+',
        'patientStreetAddress' => '123 Sample Street',
        'region' => '01',
        'province' => '0128',
        'city' => '012801',
        'barangay' => '012801001',
        'zipcode' => '1000',
        'calledDate' => '2026-04-11T08:00',
        'refferalDate' => '2026-04-11T09:30',
        'referringFacility' => 'DOH000000000000001',
        'referralFacility' => 'DOH000000000000002',
        'transactionCode' => 'REF-PREVIEW-001',
        'typeOfReferral' => 'TRANS',
        'referralCategory' => 'ER',
        'referralReason' => 'SEFTA',
        'otherReferralReason' => '',
        'contactPerson' => 'Receiving Nurse',
        'contactDesignation' => 'ER Nurse',
        'referralContactNumber' => '09179998888',
        'referralRemarks' => 'Requires urgent transfer.',
        'diagnosis' => 'Acute appendicitis',
        'chiefComplaint' => 'Severe abdominal pain',
        'clinicalHistory' => 'Pain started 8 hours ago.',
        'findings' => 'Tenderness on right lower quadrant.',
        'providerFirstName' => 'Ana',
        'providerMiddleName' => 'R',
        'providerLastName' => 'Mendoza',
        'providerSuffix' => '',
        'bp' => '120/80',
        'temp' => '37.0',
        'hr' => '88',
        'rr' => '18',
        'o2Sats' => '98%',
        'weight' => '65',
        'height' => '170',
    ];
}
