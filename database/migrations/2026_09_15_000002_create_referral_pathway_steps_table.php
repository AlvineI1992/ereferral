<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_pathway_steps', function (Blueprint $table) {
            $table->string('log_id', 50)->primary();
            $table->string('root_log_id', 50)->index();
            $table->string('parent_log_id', 50)->nullable()->unique();
            $table->unsignedInteger('sequence');
            $table->uuid('request_id')->nullable()->unique();
            $table->char('request_hash', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->longText('snapshot');
            $table->timestamp('created_at');
            $table->unique(['root_log_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_pathway_steps');
    }
};
