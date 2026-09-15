<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Enums\Permissions;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call(ApiPermissionSeeder::class);

        // Get all static method names defined in Permissions class
        $classMethods = get_class_methods(Permissions::class);

        foreach ($classMethods as $method) {
            $permissions = Permissions::$method(); // e.g. Permissions::dashboard()

            foreach ($permissions as $permission) {
                // Save each permission name
                Permission::firstOrCreate([
                    'name' => $permission[0],
                    'guard_name' => 'web',
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
