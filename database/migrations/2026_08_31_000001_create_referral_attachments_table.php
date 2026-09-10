<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('referral_attachments')) {
            // Older MySQL/MariaDB installations limit InnoDB indexes to 767
            // bytes. The failed CREATE may leave the table behind because DDL
            // is not transactional, so safely finish that partial migration.
            DB::statement('alter table `referral_attachments` modify `path` varchar(191) not null');

            if (! $this->hasIndex('referral_attachments', 'referral_attachments_path_unique')) {
                DB::statement('alter table `referral_attachments` add unique `referral_attachments_path_unique` (`path`)');
            }

            return;
        }

        Schema::create('referral_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('LogID', 100)->index();
            $table->string('disk', 50);
            $table->string('path', 191)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_attachments');
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::selectOne(
            'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
            [$table, $index]
        ) !== null;
    }
};
