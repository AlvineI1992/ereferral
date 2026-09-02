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
        if (Schema::hasTable('referral_information')) {
            Schema::table('referral_information', function (Blueprint $table) {
                if (! $this->hasIndex('referral_information', 'idx_ref_info_fhud_to_refferal_date')) {
                    $table->index(['fhudTo', 'refferalDate'], 'idx_ref_info_fhud_to_refferal_date');
                }

                if (! $this->hasIndex('referral_information', 'idx_ref_info_refferal_date')) {
                    $table->index('refferalDate', 'idx_ref_info_refferal_date');
                }

                if (! $this->hasIndex('referral_information', 'idx_ref_info_referral_category')) {
                    $table->index('referralCategory', 'idx_ref_info_referral_category');
                }

                if (! $this->hasIndex('referral_information', 'idx_ref_info_referral_reason')) {
                    $table->index('referralReason', 'idx_ref_info_referral_reason');
                }
            });
        }

        if (Schema::hasTable('referral_patientdemo')) {
            Schema::table('referral_patientdemo', function (Blueprint $table) {
                if (! $this->hasIndex('referral_patientdemo', 'idx_ref_demo_patient_prov_code')) {
                    $table->index('patientProvCode', 'idx_ref_demo_patient_prov_code');
                }

                if (! $this->hasIndex('referral_patientdemo', 'idx_ref_demo_patient_mund_code')) {
                    $table->index('patientMundCode', 'idx_ref_demo_patient_mund_code');
                }

                if (! $this->hasIndex('referral_patientdemo', 'idx_ref_demo_patient_brgy_code')) {
                    $table->index('patientBrgyCode', 'idx_ref_demo_patient_brgy_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('referral_information')) {
            Schema::table('referral_information', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'referral_information', 'idx_ref_info_fhud_to_refferal_date');
                $this->dropIndexIfExists($table, 'referral_information', 'idx_ref_info_refferal_date');
                $this->dropIndexIfExists($table, 'referral_information', 'idx_ref_info_referral_category');
                $this->dropIndexIfExists($table, 'referral_information', 'idx_ref_info_referral_reason');
            });
        }

        if (Schema::hasTable('referral_patientdemo')) {
            Schema::table('referral_patientdemo', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'referral_patientdemo', 'idx_ref_demo_patient_prov_code');
                $this->dropIndexIfExists($table, 'referral_patientdemo', 'idx_ref_demo_patient_mund_code');
                $this->dropIndexIfExists($table, 'referral_patientdemo', 'idx_ref_demo_patient_brgy_code');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$databaseName, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if ($this->hasIndex($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }
};
