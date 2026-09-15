<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_cancellations', function (Blueprint $table) {
            $table->string('LogID')->primary();
            $table->unsignedBigInteger('cancelled_by');
            $table->text('reason');
            $table->timestamp('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_cancellations');
    }
};
