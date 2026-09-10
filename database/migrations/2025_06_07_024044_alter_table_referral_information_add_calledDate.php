<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('referral_information') || $this->hasColumn('referral_information', 'calledDate')) {
            return;
        }

        $this->withoutZeroDateRestrictions(function (): void {
            Schema::table('referral_information', function (Blueprint $table) {
                $table->timestamp('calledDate')->nullable()->after('refferalDate');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
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

    private function withoutZeroDateRestrictions(callable $callback): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $callback();

            return;
        }

        $originalMode = (string) DB::scalar('select @@session.sql_mode');
        $compatibleMode = implode(',', array_filter(
            explode(',', $originalMode),
            static fn (string $mode): bool => ! in_array($mode, ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE'], true)
        ));

        DB::unprepared('set session sql_mode = '.DB::getPdo()->quote($compatibleMode));

        try {
            $callback();
        } finally {
            DB::unprepared('set session sql_mode = '.DB::getPdo()->quote($originalMode));
        }
    }
};
