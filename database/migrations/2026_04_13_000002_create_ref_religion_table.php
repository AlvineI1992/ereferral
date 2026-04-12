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
        if (! Schema::hasTable('ref_religion')) {
            Schema::create('ref_religion', function (Blueprint $table) {
                $table->string('relcode', 50)->primary();
                $table->string('reldesc', 150);
                $table->char('relstat', 1)->default('A');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $rows = [
            ['relcode' => 'ROMAN_CATHOLIC', 'reldesc' => 'Roman Catholic', 'relstat' => 'A'],
            ['relcode' => 'ISLAM', 'reldesc' => 'Islam', 'relstat' => 'A'],
            ['relcode' => 'IGLESIA_NI_CRISTO', 'reldesc' => 'Iglesia ni Cristo', 'relstat' => 'A'],
            ['relcode' => 'SEVENTH_DAY_ADVENTIST', 'reldesc' => 'Seventh-day Adventist', 'relstat' => 'A'],
            ['relcode' => 'JEHOVAHS_WITNESSES', 'reldesc' => "Jehovah's Witnesses", 'relstat' => 'A'],
            ['relcode' => 'BORN_AGAIN_CHRISTIAN', 'reldesc' => 'Born Again Christian', 'relstat' => 'A'],
            ['relcode' => 'PROTESTANT', 'reldesc' => 'Protestant', 'relstat' => 'A'],
            ['relcode' => 'BAPTIST', 'reldesc' => 'Baptist', 'relstat' => 'A'],
            ['relcode' => 'METHODIST', 'reldesc' => 'Methodist', 'relstat' => 'A'],
            ['relcode' => 'AGLIPAYAN', 'reldesc' => 'Aglipayan', 'relstat' => 'A'],
            ['relcode' => 'OTHERS', 'reldesc' => 'Others', 'relstat' => 'A'],
        ];

        $now = now();

        foreach ($rows as $row) {
            DB::table('ref_religion')->updateOrInsert(
                ['relcode' => $row['relcode']],
                [
                    'reldesc' => $row['reldesc'],
                    'relstat' => $row['relstat'],
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_religion');
    }
};
