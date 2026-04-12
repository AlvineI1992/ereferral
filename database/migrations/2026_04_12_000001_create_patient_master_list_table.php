<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('patient_master_list')) {
            Schema::create('patient_master_list', function (Blueprint $table) {
                $table->id();
                $table->string('legacy_log_id', 50)->nullable()->unique();
                $table->string('family_id', 20)->nullable();
                $table->string('phic_number', 255)->nullable();
                $table->string('case_number', 50)->nullable();
                $table->string('last_name')->nullable();
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('suffix', 10)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('sex', 10)->nullable();
                $table->string('contact_number', 50)->nullable();
                $table->string('religion', 100)->nullable();
                $table->string('blood_type', 10)->nullable();
                $table->string('blood_type_rh', 10)->nullable();
                $table->string('civil_status', 50)->nullable();
                $table->string('street_address')->nullable();
                $table->string('barangay_code', 10)->nullable();
                $table->string('city_code', 10)->nullable();
                $table->string('province_code', 10)->nullable();
                $table->string('region_code', 10)->nullable();
                $table->string('zip_code', 10)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['last_name', 'first_name', 'birth_date'], 'patient_master_lookup_idx');
                $table->index('phic_number');
                $table->index('family_id');
            });
        }

        $this->backfillLegacyPatients();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_master_list');
    }

    private function backfillLegacyPatients(): void
    {
        if (! Schema::hasTable('referral_patientinfo')) {
            return;
        }

        $hasDemoTable = Schema::hasTable('referral_patientdemo');

        $query = DB::table('referral_patientinfo as info')
            ->orderBy('info.LogID')
            ->select([
                'info.LogID as legacy_log_id',
                'info.FamilyID as family_id',
                'info.phicNum as phic_number',
                'info.caseNum as case_number',
                'info.patientLastName as last_name',
                'info.patientFirstName as first_name',
                'info.patientMiddlename as middle_name',
                'info.patientSuffix as suffix',
                'info.patientBirthDate as birth_date',
                'info.patientSex as sex',
                'info.patientContactNumber as contact_number',
                'info.patientReligion as religion',
                'info.patientBloodType as blood_type',
                'info.patientBloodTypeRH as blood_type_rh',
                'info.patientCivilStatus as civil_status',
                DB::raw($hasDemoTable ? 'demo.patientStreetAddress as street_address' : 'NULL as street_address'),
                DB::raw($hasDemoTable ? 'demo.patientBrgyCode as barangay_code' : 'NULL as barangay_code'),
                DB::raw($hasDemoTable ? 'demo.patientMundCode as city_code' : 'NULL as city_code'),
                DB::raw($hasDemoTable ? 'demo.patientProvCode as province_code' : 'NULL as province_code'),
                DB::raw($hasDemoTable ? 'demo.patientRegCode as region_code' : 'NULL as region_code'),
                DB::raw($hasDemoTable ? 'demo.patientZipCode as zip_code' : 'NULL as zip_code'),
            ]);

        if ($hasDemoTable) {
            $query->leftJoin('referral_patientdemo as demo', 'demo.LogID', '=', 'info.LogID');
        }

        $query->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('patient_master_list')->updateOrInsert(
                    ['legacy_log_id' => $this->normalizeValue($row->legacy_log_id)],
                    [
                        'family_id' => $this->normalizeValue($row->family_id),
                        'phic_number' => $this->normalizeValue($row->phic_number),
                        'case_number' => $this->normalizeValue($row->case_number),
                        'last_name' => $this->normalizeValue($row->last_name),
                        'first_name' => $this->normalizeValue($row->first_name),
                        'middle_name' => $this->normalizeValue($row->middle_name),
                        'suffix' => $this->normalizeValue($row->suffix),
                        'birth_date' => $this->normalizeDate($row->birth_date),
                        'sex' => $this->normalizeValue($row->sex),
                        'contact_number' => $this->normalizeValue($row->contact_number),
                        'religion' => $this->normalizeValue($row->religion),
                        'blood_type' => $this->normalizeValue($row->blood_type),
                        'blood_type_rh' => $this->normalizeValue($row->blood_type_rh),
                        'civil_status' => $this->normalizeValue($row->civil_status),
                        'street_address' => $this->normalizeValue($row->street_address),
                        'barangay_code' => $this->normalizeValue($row->barangay_code),
                        'city_code' => $this->normalizeValue($row->city_code),
                        'province_code' => $this->normalizeValue($row->province_code),
                        'region_code' => $this->normalizeValue($row->region_code),
                        'zip_code' => $this->normalizeValue($row->zip_code),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $normalized = $this->normalizeValue($value);

        if ($normalized === null) {
            return null;
        }

        $timestamp = strtotime($normalized);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
};
