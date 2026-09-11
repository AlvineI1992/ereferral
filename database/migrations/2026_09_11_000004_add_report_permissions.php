<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $source = DB::table('permissions')->where('name', 'incoming list')->where('guard_name', 'web')->value('id');
            foreach (['referral report list', 'diagnosis heatmap list'] as $name) {
                $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
                if ($id) {
                    continue;
                }
                $id = DB::table('permissions')->insertGetId(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                if (! $source) {
                    continue;
                }
                foreach (['role_has_permissions', 'model_has_permissions'] as $table) {
                    foreach (DB::table($table)->where('permission_id', $source)->get() as $assignment) {
                        DB::table($table)->insertOrIgnore([...((array) $assignment), 'permission_id' => $id]);
                    }
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', ['referral report list', 'diagnosis heatmap list'])->pluck('id');
        DB::transaction(function () use ($ids) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
