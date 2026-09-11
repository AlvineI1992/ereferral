<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Schema::create('ref_facilities', function ($table) {
        $table->id('fhud_seq');
        foreach (['hfhudcode', 'facility_name', 'region_code', 'facility_type', 'status', 'fhudaddress', 'emr_id'] as $column) {
            $table->string($column)->nullable();
        }
    });
    Schema::create('ref_region', function ($table) {
        $table->string('regcode');
        $table->string('regname');
    });
    Schema::create('ref_facilitytype', function ($table) {
        $table->string('factype_code');
        $table->string('description');
    });
    DB::table('ref_region')->insert([['regcode' => '05', 'regname' => 'Region V'], ['regcode' => '06', 'regname' => 'Region VI']]);
    foreach (['LOCAL' => '05', 'LEGACY' => '5', 'OUTSIDE' => '06'] as $code => $region) {
        DB::table('ref_facilities')->insert(['hfhudcode' => $code, 'facility_name' => $code, 'region_code' => $region, 'status' => 'A']);
    }
    $this->regional = User::factory()->create(['access_type' => 'CHD', 'access_id' => '05']);
    foreach (['facility list', 'facility create', 'facility edit', 'facility delete'] as $permission) {
        $this->regional->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($this->regional);
});

test('regional facility lists and search stay within the assigned region', function () {
    $this->getJson('/facility/list')->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, 'data');
    $this->getJson('/facility/list?search=OUTSIDE')->assertOk()->assertJsonPath('total', 0);
    $this->getJson('/facility/list?region=Region%20VI')->assertOk()->assertJsonPath('total', 0);
    $this->getJson('/facilities-list')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/facilities/info/LOCAL')->assertOk();
    $this->getJson('/facilities/info/OUTSIDE')->assertNotFound();
});

test('regional accounts cannot mutate facilities outside their assigned region', function () {
    $this->putJson('/facilities/update/OUTSIDE', ['region' => '06'])->assertNotFound();
    $this->deleteJson('/facilities/delete/OUTSIDE')->assertNotFound();
    $this->postJson('/facilities/store', ['region' => '06'])->assertForbidden();
    $this->putJson('/facilities/update/LOCAL', ['region' => '06'])->assertForbidden();
    $this->assertDatabaseHas('ref_facilities', ['hfhudcode' => 'OUTSIDE']);
});

test('regional accounts without an assignment see no facilities', function () {
    $this->regional->update(['access_id' => null]);
    $this->getJson('/facility/list')->assertOk()->assertJsonPath('total', 0);
    $this->getJson('/facilities-list')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/facilities/info/LOCAL')->assertNotFound();
});

test('unpadded regional assignments match padded facility codes', function () {
    $this->regional->update(['access_id' => '5']);
    $this->getJson('/facility/list')->assertOk()->assertJsonPath('total', 2);
});

test('nonregional account directory behavior is unchanged', function () {
    $this->regional->update(['access_type' => null, 'access_id' => null]);
    $this->getJson('/facility/list')->assertOk()->assertJsonPath('total', 3);
});
