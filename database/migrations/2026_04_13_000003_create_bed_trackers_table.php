<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bed_trackers')) {
            return;
        }

        Schema::create('bed_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('facility_hfhudcode', 25);
            $table->string('bed_type', 100);
            $table->unsignedInteger('total_beds')->default(0);
            $table->unsignedInteger('occupied_beds')->default(0);
            $table->unsignedInteger('reserved_beds')->default(0);
            $table->char('status', 1)->default('A');
            $table->text('remarks')->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('facility_hfhudcode');
            $table->index('status');
            $table->unique(['facility_hfhudcode', 'bed_type'], 'bed_trackers_facility_bed_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_trackers');
    }
};
