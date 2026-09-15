<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $manager = User::factory()->create();
    foreach (['role list', 'permission list', 'role edit', 'permission edit', 'permission create'] as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $manager->givePermissionTo(['role list', 'permission list', 'role edit', 'permission edit', 'permission create']);
    $this->actingAs($manager);
});

test('permission classification filters exact modules and actions together', function () {
    Permission::findOrCreate('facility create', 'web');
    Permission::findOrCreate('facility hierarchy create', 'web');
    Permission::findOrCreate('facility hierarchy list', 'web');
    Permission::findOrCreate('facility hierarchy create', 'api');
    $this->getJson('/permission/list?module=facility%20hierarchy&action=create&guard=web')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'facility hierarchy create')
        ->assertJsonPath('data.0.classification.0', 'facility hierarchy')
        ->assertJsonPath('data.0.action', 'create');
});

test('roles are classified by assigned modules and support unconfigured filtering', function () {
    $permission = Permission::findOrCreate('facility hierarchy create', 'web');
    $role = Role::findOrCreate('Directory Operator', 'web');
    $role->givePermissionTo($permission);
    Role::findOrCreate('Directory Empty', 'web');
    $this->getJson('/roles/list?module=facility%20hierarchy&assignment=assigned&search=Directory')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $role->id)
        ->assertJsonPath('data.0.classification.0', 'facility hierarchy')->assertJsonPath('data.0.assignment_count', 1);
    $this->getJson('/roles/list?assignment=unassigned&search=Directory')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Directory Empty');
    $this->getJson('/permission/list?assignment=assigned&module=facility%20hierarchy')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.assignment_count', 1);
});

test('directory filters validate bounds and paginate deterministically', function (string $path) {
    $this->getJson($path.'?per_page=1000')->assertUnprocessable();
    $this->getJson($path.'?sort=invalid')->assertUnprocessable();
    $this->getJson($path.'?page=0')->assertUnprocessable();
    $this->getJson($path.'?search=does-not-exist')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('last_page', 1);
})->with(['/roles/list', '/permission/list']);

test('directory endpoints retain list permission checks', function (string $path) {
    $this->actingAs(User::factory()->create())->getJson($path)->assertForbidden();
})->with(['/roles/list', '/permission/list']);

test('directory forms persist guard edits and return redirects', function () {
    $role = Role::findOrCreate('Editable Role', 'web');
    $this->put('/roles/update/'.$role->id, ['name' => 'Edited Role', 'guard_name' => 'api'])
        ->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'guard_name' => 'api']);
    $permission = Permission::findOrCreate('editable list', 'web');
    $this->put('/permission/update/'.$permission->id, ['name' => 'edited list', 'guard_name' => 'api'])
        ->assertRedirect(route('permission.index'));
    $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'guard_name' => 'api']);
    $this->post('/permission/store', ['name' => 'directory export', 'guard_name' => 'api'])
        ->assertRedirect(route('permission.index'));
    $this->assertDatabaseHas('permissions', ['name' => 'directory export', 'guard_name' => 'api']);
});
