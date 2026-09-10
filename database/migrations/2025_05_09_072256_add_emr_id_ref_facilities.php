<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ref_facilities') || $this->hasColumn('ref_facilities', 'emr_id')) {
            return;
        }

        Schema::table('ref_facilities', function (Blueprint $table) {
            $table->string('emr_id')->nullable()->after('status'); // adjust position if needed
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ref_facilities') || ! $this->hasColumn('ref_facilities', 'emr_id')) {
            return;
        }

        Schema::table('ref_facilities', function (Blueprint $table) {
            $table->dropColumn('emr_id');
        });
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        return DB::selectOne(
            'select 1 from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
            [$table, $column]
        ) !== null;
    }
};
