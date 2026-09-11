<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->text('email')->change();
        });

        Schema::table('data_encryption_settings', function (Blueprint $table) {
            $table->string('backup_path')->nullable();
            $table->string('backup_checksum', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('data_encryption_settings', function (Blueprint $table) {
            $table->dropColumn(['backup_path', 'backup_checksum']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->change();
            $table->unique('email');
        });
    }
};
