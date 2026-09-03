<?php

use App\Http\Controllers\ReferralPatientInfoController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    foreach (['referral_patientdemo', 'referral_patientinfo', 'ref_region', 'ref_province', 'ref_city', 'ref_barangay'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('referral_patientinfo', function (Blueprint $table) {
        $table->string('LogID')->primary();
        $table->string('patientFirstName');
        $table->string('patientMiddlename')->nullable();
        $table->string('patientLastName');
        $table->date('patientBirthDate')->nullable();
        $table->string('patientSex')->nullable();
    });

    Schema::create('referral_patientdemo', function (Blueprint $table) {
        $table->string('LogID')->primary();
        $table->string('patientStreetAddress')->nullable();
        $table->string('patientRegCode')->nullable();
        $table->string('patientProvCode')->nullable();
        $table->string('patientMundCode')->nullable();
        $table->string('patientBrgyCode')->nullable();
    });

    Schema::create('ref_region', function (Blueprint $table) {
        $table->string('regcode')->primary();
        $table->string('regname');
    });
    Schema::create('ref_province', function (Blueprint $table) {
        $table->string('provcode')->primary();
        $table->string('provname');
    });
    Schema::create('ref_city', function (Blueprint $table) {
        $table->string('citycode')->primary();
        $table->string('cityname');
    });
    Schema::create('ref_barangay', function (Blueprint $table) {
        $table->string('bgycode')->primary();
        $table->string('bgyname');
    });
});

test('patient profile handles missing demographic reference records', function () {
    DB::table('referral_patientinfo')->insert([
        'LogID' => 'RHU-5601081726093603',
        'patientFirstName' => 'Test',
        'patientLastName' => 'Patient',
        'patientBirthDate' => null,
        'patientSex' => 'F',
    ]);
    DB::table('referral_patientdemo')->insert([
        'LogID' => 'RHU-5601081726093603',
        'patientStreetAddress' => 'Sample Street',
        'patientRegCode' => 'MISSING-REGION',
        'patientProvCode' => 'MISSING-PROVINCE',
        'patientMundCode' => 'MISSING-CITY',
        'patientBrgyCode' => 'MISSING-BARANGAY',
    ]);

    $response = app(ReferralPatientInfoController::class)->show(base64_encode('RHU-5601081726093603'));

    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data['profile']['age'])->toBeNull()
        ->and($data['demographics'])->toBe([
            'street' => 'Sample Street',
            'region' => '',
            'province' => '',
            'city' => '',
            'barangay' => '',
        ]);
});
