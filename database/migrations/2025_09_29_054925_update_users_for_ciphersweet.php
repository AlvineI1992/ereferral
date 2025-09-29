<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->change(); // Encrypted but stays string
            $table->string('name')->change();  // Encrypted but stays string
        
            // Add column for blind index
            $table->string('name_index')->nullable()->index();
        });

        

        
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert email back to original length (you may need to adjust this based on your original schema)
            $table->string('email', 255)->change();

            // Drop the blind index
            $table->dropColumn('email_index');
        });
    }
};
