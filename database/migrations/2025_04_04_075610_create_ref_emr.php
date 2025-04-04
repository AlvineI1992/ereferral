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
        Schema::create('ref_emr', function (Blueprint $table) {
            $table->id('emr_id'); // Auto-increment primary key
            $table->text('emr_name')->nullable();
            $table->char('status', 1)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps(); // Adds created_at and updated_at
            $table->softDeletes(); // Adds deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_emr');
    }
};
