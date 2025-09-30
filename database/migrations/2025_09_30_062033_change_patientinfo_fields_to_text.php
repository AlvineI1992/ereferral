<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referral_patientinfo', function (Blueprint $table) {
      
            $table->text('phicNum')->change();
            $table->text('patientLastName')->change();
            $table->text('patientFirstName')->change();
            $table->text('patientMiddlename')->change();
            $table->text('patientSex')->change();
            $table->text('patientBirthDate')->change();
            $table->text('patientContactNumber')->change();
            $table->text('patientReligion')->change();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_patientinfo', function (Blueprint $table) {
          
            $table->string('phicNum', 255)->change();
            $table->string('patientLastName', 255)->change();
            $table->string('patientFirstName', 255)->change();
            $table->string('patientMiddlename', 255)->change();
            $table->string('patientSex', 50)->change();
            $table->string('patientBirthDate', 50)->change();
            $table->string('patientContactNumber', 50)->change();
            $table->string('patientReligion', 100)->change();
        });
    }
};
