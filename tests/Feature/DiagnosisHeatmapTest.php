<?php

use App\Models\User;
use App\Services\DiagnosisHeatmapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Schema::create('ref_facilities', function ($table) {
        $table->string('hfhudcode')->primary();
        $table->string('facility_name');
        $table->string('emr_id');
        $table->string('region_code');
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
    });
    Schema::create('referral_information', function ($table) {
        $table->string('LogID')->primary();
        $table->string('fhudFrom');
        $table->string('fhudTo');
    });
    Schema::create('referral_track', function ($table) {
        $table->string('LogID');
        $table->string('diagnosis')->nullable();
        $table->dateTime('dischDate')->nullable();
    });
    DB::table('ref_facilities')->insert([
        ['hfhudcode' => 'A', 'facility_name' => 'Mapped Facility', 'emr_id' => '1', 'region_code' => '05', 'latitude' => 12.9, 'longitude' => 124.1],
        ['hfhudcode' => 'B', 'facility_name' => 'Unmapped Facility', 'emr_id' => '1', 'region_code' => '05', 'latitude' => null, 'longitude' => null],
        ['hfhudcode' => 'C', 'facility_name' => 'Other Provider', 'emr_id' => '2', 'region_code' => '06', 'latitude' => 10.7, 'longitude' => 122.5],
    ]);
    foreach ([['1', 'A', 'PNEUMONIA', '2026-09-05'], ['2', 'A', ' pneumonia ', '2026-09-06'], ['3', 'B', 'DENGUE', '2026-09-06'], ['4', 'C', 'PNEUMONIA', '2026-09-06'], ['5', 'A', null, '2026-09-06'], ['6', 'A', 'PNEUMONIA', null], ['7', 'A', 'PNEUMONIA', '2026-08-01']] as [$id, $facility, $diagnosis, $date]) {
        DB::table('referral_information')->insert(['LogID' => $id, 'fhudFrom' => 'A', 'fhudTo' => $facility]);
        DB::table('referral_track')->insert(['LogID' => $id, 'diagnosis' => $diagnosis, 'dischDate' => $date ? $date.' 12:00:00' : null]);
    }
    $this->filters = ['date_from' => '2026-09-01', 'date_to' => '2026-09-11'];
});

test('heat map counts only discharged final diagnoses within provider scope', function () {
    $user = User::factory()->create(['access_type' => 'EMR', 'access_id' => '1']);
    $report = app(DiagnosisHeatmapService::class)->report($this->filters, $user);
    expect($report['total'])->toBe(3)->and($report['mapped'])->toBe(2)->and($report['unmapped'])->toBe(1);
    expect($report['facilities']->pluck('code')->all())->toBe(['A', 'B']);
    expect($report['diagnoses']->pluck('diagnosis')->all())->toBe(['PNEUMONIA', 'DENGUE']);
    $filtered = app(DiagnosisHeatmapService::class)->report([...$this->filters, 'diagnosis' => 'pneumonia'], $user);
    expect($filtered['total'])->toBe(2);
});

test('heat map scopes hospital regional and unassigned users', function ($type, $id, $count) {
    $user = User::factory()->create(['access_type' => $type, 'access_id' => $id]);
    expect(app(DiagnosisHeatmapService::class)->report($this->filters, $user)['total'])->toBe($count);
})->with([['HOSP', 'A', 2], ['CHD', '06', 1], ['EMR', null, 0], [null, null, 0]]);

test('administrators see aggregate totals and duplicate tracks do not double count', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin', 'web'));
    DB::table('referral_track')->insert(['LogID' => '1', 'diagnosis' => 'PNEUMONIA', 'dischDate' => '2026-09-05 12:00:00']);
    expect(app(DiagnosisHeatmapService::class)->report($this->filters, $user)['total'])->toBe(4);
});

test('heat map routes require permission and validate date filters', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/reports/diagnosis-heatmap')->assertForbidden();
    $this->getJson('/reports/diagnosis-heatmap/data')->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('diagnosis heatmap list', 'web'));
    $this->get('/reports/diagnosis-heatmap')->assertOk();
    $this->getJson('/reports/diagnosis-heatmap/data?date_from=2026-09-11&date_to=2026-09-01')->assertUnprocessable();
    $response = $this->getJson('/reports/diagnosis-heatmap/data?date_from=2026-09-01&date_to=2026-09-11')->assertOk();
    expect($response->json('total'))->toBe(0)->and($response->json())->not->toHaveKeys(['patient', 'LogID']);
});
