<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function userManagerWithPermissions(array $permissions): User
{
    $user = User::factory()->create([
        'status' => 'A',
    ]);

    foreach ($permissions as $permissionName) {
        Permission::findOrCreate($permissionName, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('authorized users can create managed user accounts', function () {
    $manager = userManagerWithPermissions(['user create']);

    $response = $this->actingAs($manager)->postJson(route('user.store'), [
        'name' => 'Managed Account',
        'email' => 'managed@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => true,
        'access_type' => 'EMR',
        'access_id' => '12',
    ]);

    $response
        ->assertCreated()
        ->assertJson([
            'message' => 'User created successfully.',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'managed@example.com',
        'status' => 'A',
        'access_type' => 'EMR',
        'access_id' => '12',
    ]);
});

test('authorized users can update managed user accounts', function () {
    $manager = userManagerWithPermissions(['user edit']);

    $managedUser = User::factory()->create([
        'status' => 'A',
        'access_type' => 'EMR',
        'access_id' => '10',
    ]);

    $response = $this->actingAs($manager)->putJson(route('user.update', $managedUser->id), [
        'name' => 'Updated User',
        'email' => $managedUser->email,
        'password' => '',
        'password_confirmation' => '',
        'status' => false,
        'access_type' => 'CHD',
        'access_id' => '01',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'User updated successfully.',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $managedUser->id,
        'name' => 'Updated User',
        'status' => 'I',
        'access_type' => 'CHD',
        'access_id' => '01',
    ]);
});

test('authorized users can soft delete other user accounts', function () {
    $manager = userManagerWithPermissions(['user delete']);

    $managedUser = User::factory()->create([
        'status' => 'A',
    ]);

    $response = $this->actingAs($manager)->deleteJson(route('user.destroy', $managedUser->id));

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'User deleted successfully.',
        ]);

    $this->assertSoftDeleted('users', [
        'id' => $managedUser->id,
    ]);
});

test('signed in users cannot delete their own account from user management', function () {
    $manager = userManagerWithPermissions(['user delete']);

    $response = $this->actingAs($manager)->deleteJson(route('user.destroy', $manager->id));

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'You cannot delete your own account while you are signed in.',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $manager->id,
    ]);
});
