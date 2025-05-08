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
                // 1. Create user
            $user = User::create([
                'name' => 'admin',
                'email' => 'admin@referral.doh.gov.ph',
                'password' => bcrypt('Alvin1992!'),
                'status' => 'A'
            ]);

        
             $adminRole = Role::firstOrCreate(['name' => 'Admin']);

            // 3. Get all permissions
            $allPermissions = Permission::all();

            // 4. Give all permissions to Admin role
            $adminRole->syncPermissions($allPermissions);

            // 5. Assign the Admin role to the user
            $user->assignRole('Admin');;
    }
}
