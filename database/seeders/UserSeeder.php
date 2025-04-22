<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Status;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create(
            ['name' => "admin",'email' => "admin@admin.com",'password' => bcrypt('password'), 'status' => 'A']);

       $adminrole = Role::create(['name' => 'Admin']);

        $permissions = Permission::pluck('id','id')->all();

        $adminrole->syncPermissions($permissions);
        $adminrole->revokePermissionTo('permission-create');
        $adminrole->revokePermissionTo('permission-update');
        $adminrole->revokePermissionTo('permission-delete');

        $user->assignRole([$adminrole->id]);
    }
}
