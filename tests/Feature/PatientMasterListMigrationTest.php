<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('patient master list migration backfills legacy patient tables', function () {
    Schema::dropIfExists('patient_master_list');
    Schema::dropIfExists('referral_patientdemo');
    Schema::dropIfExists('referral_patientinfo');

    Schema::create('referral_patientinfo', function (Blueprint $table) {
        $table->string('LogID', 50)->primary();
        $table->string('FamilyID', 20)->nullable();
        $table->string('phicNum')->nullable();
        $table->integer('caseNum')->nullable();
        $table->string('patientLastName')->nullable();
        $table->string('patientFirstName')->nullable();
        $table->string('patientSuffix', 5)->nullable();
        $table->string('patientMiddlename')->nullable();
        $table->text('patientBirthDate')->nullable();
        $table->text('patientSex')->nullable();
        $table->text('patientContactNumber')->nullable();
        $table->text('patientReligion')->nullable();
        $table->string('patientBloodType', 5)->nullable();
        $table->string('patientBloodTypeRH', 5)->nullable();
        $table->char('patientCivilStatus', 1)->nullable();
    });

    Schema::create('referral_patientdemo', function (Blueprint $table) {
        $table->string('LogID', 50)->primary();
        $table->string('patientStreetAddress')->nullable();
        $table->string('patientBrgyCode', 10)->nullable();
        $table->string('patientMundCode', 6)->nullable();
        $table->string('patientProvCode', 4)->nullable();
        $table->string('patientRegCode', 2)->nullable();
        $table->string('patientZipCode', 5)->nullable();
    });

    DB::table('referral_patientinfo')->insert([
        'LogID' => 'LOG-1001',
        'FamilyID' => 'FAM-88',
        'phicNum' => '1234567890',
        'caseNum' => 44,
        'patientLastName' => 'Dela Cruz',
        'patientFirstName' => 'Juan',
        'patientSuffix' => 'Jr',
        'patientMiddlename' => 'Santos',
        'patientBirthDate' => '1990-01-02',
        'patientSex' => 'M',
        'patientContactNumber' => '09171234567',
        'patientReligion' => 'Catholic',
        'patientBloodType' => 'O',
        'patientBloodTypeRH' => '+',
        'patientCivilStatus' => 'S',
    ]);

    DB::table('referral_patientdemo')->insert([
        'LogID' => 'LOG-1001',
        'patientStreetAddress' => '123 Sample Street',
        'patientBrgyCode' => '0123456789',
        'patientMundCode' => '012345',
        'patientProvCode' => '0123',
        'patientRegCode' => '01',
        'patientZipCode' => '1000',
    ]);

    $migrationPath = collect(glob(database_path('migrations/*_create_patient_master_list_table.php')))->first();

    expect($migrationPath)->not->toBeNull();

    $migration = require $migrationPath;
    $migration->up();

    expect(Schema::hasTable('patient_master_list'))->toBeTrue();
    expect(DB::table('patient_master_list')->count())->toBe(1);

    $this->assertDatabaseHas('patient_master_list', [
        'legacy_log_id' => 'LOG-1001',
        'family_id' => 'FAM-88',
        'phic_number' => '1234567890',
        'case_number' => '44',
        'last_name' => 'Dela Cruz',
        'first_name' => 'Juan',
        'middle_name' => 'Santos',
        'suffix' => 'Jr',
        'birth_date' => '1990-01-02',
        'sex' => 'M',
        'contact_number' => '09171234567',
        'religion' => 'Catholic',
        'blood_type' => 'O',
        'blood_type_rh' => '+',
        'civil_status' => 'S',
        'street_address' => '123 Sample Street',
        'barangay_code' => '0123456789',
        'city_code' => '012345',
        'province_code' => '0123',
        'region_code' => '01',
        'zip_code' => '1000',
    ]);
});
