<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ref_facilities')) {
            return;
        }
        Schema::table('ref_facilities', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ref_facilities')) {
            return;
        }
        Schema::table('ref_facilities', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude']));
    }
};
