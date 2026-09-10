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
        if (! Schema::hasTable('referral_information')) {
            return;
        }

        $columns = $this->existingColumns('referral_information');

        $this->withoutZeroDateRestrictions(function () use ($columns): void {
            Schema::table('referral_information', function (Blueprint $table) use ($columns) {
                if (! in_array('updated_at', $columns, true)) {
                    $table->timestamp('updated_at')->nullable(); // Ensures updated_at exists
                }

                if (! in_array('deleted_at', $columns, true)) {
                    $table->softDeletes(); // Adds deleted_at for soft deletes
                }
                if (! in_array('status', $columns, true)) {
                    $table->char('status', 1)->nullable();
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('referral_information')) {
            return;
        }

        $columns = $this->existingColumns('referral_information');

        Schema::table('referral_information', function (Blueprint $table) use ($columns) {
            if (in_array('updated_at', $columns, true)) {
                $table->dropColumn('updated_at');
            }

            if (in_array('deleted_at', $columns, true)) {
                $table->dropSoftDeletes();
            }

            if (in_array('emr_id', $columns, true)) {
                $table->dropForeign(['emr_id']);
                $table->dropColumn('emr_id');
            }
        });
    }

    private function existingColumns(string $table): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return Schema::getColumnListing($table);
        }

        return array_map(
            static fn (object $column): string => $column->column_name,
            DB::select(
                'select column_name from information_schema.columns where table_schema = database() and table_name = ?',
                [$table]
            )
        );
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
