<?php

use App\Services\PatientPiiEncryption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (PatientPiiEncryption::FIELDS as $name => $fields) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            foreach (Schema::getIndexes($name) as $index) {
                if (! $index['primary'] && array_intersect(array_map('strtolower', $index['columns']), array_map('strtolower', $fields))) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropIndex($index['name']));
                }
            }
            foreach ($fields as $field) {
                if (Schema::hasColumn($name, $field)) {
                    Schema::table($name, fn (Blueprint $table) => $table->text($field)->nullable()->change());
                }
            }
        }

        Schema::create('patient_pii_indexes', function (Blueprint $table) {
            $table->string('table_name', 32);
            $table->string('record_id', 50);
            $table->string('name', 32);
            $table->char('value', 32);
            $table->primary(['table_name', 'record_id', 'name'], 'patient_pii_primary');
            $table->index(['table_name', 'name', 'value'], 'patient_pii_lookup');
        });
        Schema::create('patient_encryption_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('status', 30)->default('inactive');
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedTinyInteger('conversion_table')->default(0);
            $table->string('last_record_id', 50)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('backup_path')->nullable();
            $table->string('backup_checksum', 64)->nullable();
            $table->timestamps();
        });
        DB::table('patient_encryption_settings')->insert(['enabled' => false, 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        throw new RuntimeException('Patient encryption storage requires a controlled restore; automatic rollback is disabled.');
    }
};
