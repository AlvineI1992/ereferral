<?php

use App\Models\RefEmrModel;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function emrUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permissionName) {
        Permission::findOrCreate($permissionName, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('authorized users can create an emr provider without a uuid column', function () {
    expect(Schema::hasColumn('ref_emr', 'uuid'))->toBeFalse();

    $user = emrUserWithPermissions(['provider create']);

    $response = $this->actingAs($user)->post(route('emr.store'), [
        'emr_name' => 'Alpha Provider',
        'status' => true,
        'remarks' => 'Primary instance',
    ]);

    $response->assertRedirect(route('emr.index'));

    $this->assertDatabaseHas('ref_emr', [
        'emr_name' => 'Alpha Provider',
        'status' => '1',
        'remarks' => 'Primary instance',
    ]);
});

test('authorized users can update an existing emr provider', function () {
    $user = emrUserWithPermissions(['provider edit']);

    $provider = RefEmrModel::create([
        'emr_name' => 'Legacy Provider',
        'status' => 1,
        'remarks' => 'Old note',
    ]);

    $response = $this->actingAs($user)->put(route('emr.update', $provider->emr_id), [
        'emr_name' => 'Modern Provider',
        'status' => false,
        'remarks' => 'Updated note',
    ]);

    $response->assertRedirect(route('emr.index'));

    $this->assertDatabaseHas('ref_emr', [
        'emr_id' => $provider->emr_id,
        'emr_name' => 'Modern Provider',
        'status' => '0',
        'remarks' => 'Updated note',
    ]);
});

test('authorized users can soft delete an emr provider', function () {
    $user = emrUserWithPermissions(['provider delete']);

    $provider = RefEmrModel::create([
        'emr_name' => 'Disposable Provider',
        'status' => 1,
        'remarks' => null,
    ]);

    $response = $this->actingAs($user)->delete(route('emr.destroy', $provider->emr_id));

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Provider deleted successfully.',
        ]);

    $this->assertSoftDeleted('ref_emr', [
        'emr_id' => $provider->emr_id,
    ]);
});
