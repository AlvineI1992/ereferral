<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Schema::create('ref_facilities', function ($table) {
        $table->string('hfhudcode')->primary();
        foreach (['facility_name', 'facility_type', 'region_code', 'province_code', 'city_code', 'bgycode', 'fhudaddress', 'status'] as $column) {
            $table->string($column)->nullable();
        }
    });
    (require database_path('migrations/2026_09_11_000003_add_facility_coordinates.php'))->up();
    $user = User::factory()->create();
    foreach (['facility create', 'facility edit', 'facility list'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($user);
    $this->facilityPayload = ['hfhudcode' => 'TEST-MAP-1', 'facility_name' => 'Example Map Facility', 'factype_code' => '01', 'region' => '05', 'province' => '0562', 'city' => '056214', 'barangay' => '001', 'status' => true];
});

test('facility coordinates persist on create and edit and can be cleared', function () {
    $this->post('/facilities/store', [...$this->facilityPayload, 'latitude' => '12.9876543', 'longitude' => '124.1234567'])->assertRedirect();
    $this->assertDatabaseHas('ref_facilities', ['hfhudcode' => 'TEST-MAP-1', 'latitude' => 12.9876543, 'longitude' => 124.1234567]);
    $this->getJson('/facilities/info/TEST-MAP-1')->assertOk()->assertJsonPath('latitude', 12.9876543);
    $this->put('/facilities/update/TEST-MAP-1', [...$this->facilityPayload, 'latitude' => '-90', 'longitude' => '180'])->assertRedirect();
    $this->assertDatabaseHas('ref_facilities', ['latitude' => -90, 'longitude' => 180]);
    $this->put('/facilities/update/TEST-MAP-1', [...$this->facilityPayload, 'latitude' => '', 'longitude' => ''])->assertRedirect();
    $this->assertDatabaseHas('ref_facilities', ['latitude' => null, 'longitude' => null]);
});

test('facility coordinates reject invalid or incomplete pairs', function ($coordinates, $field) {
    $this->postJson('/facilities/store', [...$this->facilityPayload, ...$coordinates])->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('ref_facilities', 0);
})->with([
    [['latitude' => 91, 'longitude' => 124], 'latitude'],
    [['latitude' => 12, 'longitude' => -181], 'longitude'],
    [['latitude' => 12], 'longitude'],
    [['longitude' => 124], 'latitude'],
    [['latitude' => 'invalid', 'longitude' => 124], 'latitude'],
]);

test('facilities can still be registered without coordinates', function () {
    $this->post('/facilities/store', $this->facilityPayload)->assertRedirect();
    $this->assertDatabaseHas('ref_facilities', ['hfhudcode' => 'TEST-MAP-1', 'latitude' => null, 'longitude' => null]);
});
