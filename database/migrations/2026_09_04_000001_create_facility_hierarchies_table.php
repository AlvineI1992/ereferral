<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_hierarchies', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->id();
            $table->string('facility_hfhudcode', 19)->unique();
            $table->string('parent_hfhudcode', 19)->nullable()->index();
            $table->string('level', 20)->index();
            $table->string('referral_network', 120)->index();
            $table->string('coverage_region_code', 10)->index();
            $table->string('coverage_province_code', 10)->nullable()->index();
            $table->text('coverage_notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('facility_hfhudcode')->references('hfhudcode')->on('ref_facilities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('parent_hfhudcode')->references('hfhudcode')->on('ref_facilities')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_hierarchies');
    }
};
