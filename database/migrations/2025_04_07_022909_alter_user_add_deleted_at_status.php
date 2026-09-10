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
        $columns = DB::getDriverName() === 'mysql'
            ? array_map(
                static fn (object $column): string => $column->column_name,
                DB::select('select column_name from information_schema.columns where table_schema = database() and table_name = ?', ['users'])
            )
            : Schema::getColumnListing('users');

        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (! in_array('deleted_at', $columns, true)) {
                $table->softDeletes(); // Adds deleted_at for soft deletes
            }

            if (! in_array('status', $columns, true)) {
                $table->char('status', 1);
            }
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
