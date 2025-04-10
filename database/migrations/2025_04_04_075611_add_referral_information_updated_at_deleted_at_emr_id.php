<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referral_information', function (Blueprint $table) {
            if (!Schema::hasColumn('referral_information', 'updated_at')) {
                $table->timestamp('updated_at')->nullable(); // Ensures updated_at exists
            }

            if (!Schema::hasColumn('referral_information', 'deleted_at')) {
                $table->softDeletes(); // Adds deleted_at for soft deletes
            }
            if (!Schema::hasColumn('referral_information', 'updated_at')) {
                $table->char('status',1)->nullable(); // Ensures updated_at exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_information', function (Blueprint $table) {
            if (Schema::hasColumn('referral_information', 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            if (Schema::hasColumn('referral_information', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('referral_information', 'emr_id')) {
                $table->dropForeign(['emr_id']);
                $table->dropColumn('emr_id');
            }
        });
    }
};
