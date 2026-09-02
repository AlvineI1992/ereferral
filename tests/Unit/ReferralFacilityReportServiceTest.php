<?php

use App\Models\User;
use App\Services\ReferralFacilityReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::create('ref_emr', function (Blueprint $table) {
        $table->string('emr_id')->primary();
        $table->string('emr_name');
    });

    Schema::create('ref_facilities', function (Blueprint $table) {
        $table->string('hfhudcode')->primary();
        $table->string('facility_name');
        $table->string('facility_type')->nullable();
        $table->string('emr_id')->nullable();
        $table->string('region_code')->nullable();
    });

    Schema::create('ref_facilitytype', function (Blueprint $table) {
        $table->string('factype_code')->primary();
        $table->string('description');
    });

    Schema::create('referral_information', function (Blueprint $table) {
        $table->string('LogID')->primary();
        $table->string('fhudTo');
        $table->string('fhudFrom');
        $table->date('refferalDate');
        $table->time('refferalTime')->nullable();
    });

    Schema::create('referral_patientinfo', function (Blueprint $table) {
        $table->string('LogID')->primary();
        $table->string('patientFirstName')->nullable();
        $table->string('patientMiddlename')->nullable();
        $table->string('patientLastname')->nullable();
    });

    Schema::create('referral_track', function (Blueprint $table) {
        $table->string('LogID')->primary();
        $table->dateTime('receivedDate')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('referral_track');
    Schema::dropIfExists('referral_patientinfo');
    Schema::dropIfExists('referral_information');
    Schema::dropIfExists('ref_facilities');
    Schema::dropIfExists('ref_facilitytype');
    Schema::dropIfExists('ref_emr');
});

it('counts referrals sent and confirmed received by destination facility', function () {
    DB::table('ref_emr')->insert([
        ['emr_id' => 'EMR-1', 'emr_name' => 'Provider One'],
        ['emr_id' => 'EMR-2', 'emr_name' => 'Provider Two'],
    ]);

    DB::table('ref_facilities')->insert([
        ['hfhudcode' => 'FAC-A', 'facility_name' => 'Alpha Hospital', 'facility_type' => '1', 'emr_id' => 'EMR-1', 'region_code' => '01'],
        ['hfhudcode' => 'FAC-B', 'facility_name' => 'Beta Hospital', 'facility_type' => '1', 'emr_id' => 'EMR-2', 'region_code' => '02'],
        ['hfhudcode' => 'RHU-A', 'facility_name' => 'Alpha RHU', 'facility_type' => '17', 'emr_id' => 'EMR-1', 'region_code' => '01'],
    ]);
    DB::table('ref_facilitytype')->insert([
        ['factype_code' => '1', 'description' => 'Hospital'],
        ['factype_code' => '17', 'description' => 'Rural Health Unit'],
    ]);
    DB::table('referral_information')->insert([
        ['LogID' => 'R-1', 'fhudTo' => 'FAC-A', 'fhudFrom' => 'RHU-A', 'refferalDate' => '2026-09-01'],
        ['LogID' => 'R-2', 'fhudTo' => 'FAC-A', 'fhudFrom' => 'FAC-B', 'refferalDate' => '2026-09-02'],
        ['LogID' => 'R-3', 'fhudTo' => 'FAC-B', 'fhudFrom' => 'RHU-A', 'refferalDate' => '2026-09-02'],
        ['LogID' => 'R-OLD', 'fhudTo' => 'FAC-A', 'fhudFrom' => 'RHU-A', 'refferalDate' => '2026-08-31'],
    ]);
    DB::table('referral_track')->insert([
        ['LogID' => 'R-1', 'receivedDate' => '2026-09-01 10:00:00'],
        ['LogID' => 'R-2', 'receivedDate' => null],
        ['LogID' => 'R-3', 'receivedDate' => '2026-09-02 10:00:00'],
    ]);
    DB::table('referral_patientinfo')->insert([
        ['LogID' => 'R-1', 'patientFirstName' => 'Ana', 'patientMiddlename' => 'M', 'patientLastname' => 'Reyes'],
        ['LogID' => 'R-2', 'patientFirstName' => 'Ben', 'patientMiddlename' => null, 'patientLastname' => 'Santos'],
        ['LogID' => 'R-3', 'patientFirstName' => 'Cara', 'patientMiddlename' => null, 'patientLastname' => 'Cruz'],
    ]);

    $user = new User(['access_type' => 'HOSP', 'access_id' => 'FAC-A']);
    $report = app(ReferralFacilityReportService::class)->report([
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-02',
    ], $user);

    expect($report['summary'])->toMatchArray([
        'facilities' => 1,
        'rhu_facilities' => 1,
        'sent_count' => 2,
        'rhu_sent_count' => 1,
        'received_count' => 1,
        'pending_count' => 1,
        'receipt_rate' => 50.0,
    ])->and($report['data'][0]['facility_code'])->toBe('FAC-A')
        ->and($report['data'][0]['facility_type'])->toBe('Hospital')
        ->and($report['options']['referring_facilities'])->toHaveCount(2)
        ->and($report['options']['referral_facilities'])->toHaveCount(1)
        ->and($report['options']['providers'])->toBe([
            ['id' => 'EMR-1', 'name' => 'Provider One'],
        ])
        ->and($report['rhu_data'][0])->toMatchArray([
            'rhu_code' => 'RHU-A',
            'rhu_name' => 'Alpha RHU',
            'destination_count' => 1,
            'sent_count' => 1,
            'received_count' => 1,
            'pending_count' => 0,
            'receipt_rate' => 100.0,
        ]);

    $filteredReport = app(ReferralFacilityReportService::class)->report([
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-02',
        'referring_facility' => 'FAC-B',
        'referral_facility' => 'FAC-A',
    ], $user);

    expect($filteredReport['summary'])->toMatchArray([
        'sent_count' => 1,
        'rhu_sent_count' => 0,
        'received_count' => 0,
        'pending_count' => 1,
    ])->and($filteredReport['rhu_data'])->toBeEmpty();

    $providerFilteredReport = app(ReferralFacilityReportService::class)->report([
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-02',
        'provider' => 'EMR-1',
    ], null);

    expect($providerFilteredReport['summary'])->toMatchArray([
        'facilities' => 1,
        'sent_count' => 2,
        'received_count' => 1,
        'pending_count' => 1,
    ])->and($providerFilteredReport['data'][0]['facility_code'])->toBe('FAC-A')
        ->and($providerFilteredReport['filters']['provider'])->toBe('EMR-1');

    $patients = app(ReferralFacilityReportService::class)->patients([
        'date_from' => '2026-09-01',
        'date_to' => '2026-09-02',
        'provider' => 'EMR-1',
        'group_type' => 'rhu',
        'group_code' => 'RHU-A',
    ], $user);

    expect($patients['total'])->toBe(1)
        ->and($patients['data'][0])->toMatchArray([
            'log_id' => 'R-1',
            'patient_name' => 'Ana M Reyes',
            'referring_facility' => 'Alpha RHU',
            'referral_facility' => 'Alpha Hospital',
            'received' => true,
            'received_date' => 'Sep 1, 2026 10:00 AM',
        ]);
});
