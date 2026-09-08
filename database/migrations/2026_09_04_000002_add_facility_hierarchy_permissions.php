<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION_MAP = [
        'facility create' => 'facility hierarchy create',
        'facility list' => 'facility hierarchy list',
        'facility edit' => 'facility hierarchy edit',
        'facility delete' => 'facility hierarchy delete',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::PERMISSION_MAP as $existingPermission => $newPermission) {
                $permissionId = DB::table('permissions')->where('name', $newPermission)->value('id');

                if (! $permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'name' => $newPermission,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $roleIds = DB::table('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('permissions.name', $existingPermission)
                    ->pluck('role_has_permissions.role_id');

                foreach ($roleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_values(self::PERMISSION_MAP))->delete();
    }
};
