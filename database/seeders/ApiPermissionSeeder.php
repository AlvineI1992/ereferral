<?php

namespace Database\Seeders;

use App\Services\ApiPermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ApiPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function () {
            foreach (ApiPermissionService::ENDPOINTS as $endpoint) {
                $permission = Permission::findOrCreate($endpoint['permission'], 'api');
                if (! $endpoint['legacy']) continue;
                $legacy = Permission::where('guard_name', 'api')->where('name', $endpoint['legacy'])->first();
                if (! $legacy) {
                    continue;
                }
                foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                    $table = config('permission.table_names.'.$pivot);
                    $permissionKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
                    DB::table($table)->where($permissionKey, $legacy->id)->orderBy($permissionKey)
                        ->chunk(500, function ($grants) use ($table, $permissionKey, $permission) {
                            $rows = $grants->map(function ($grant) use ($permissionKey, $permission) {
                                $row = (array) $grant;
                                $row[$permissionKey] = $permission->id;
                                return $row;
                            })->all();
                            DB::table($table)->insertOrIgnore($rows);
                        });
                }
            }
            Permission::where('guard_name', 'api')
                ->whereIn('name', array_unique(array_column(ApiPermissionService::ENDPOINTS, 'legacy')))
                ->get()->each->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
