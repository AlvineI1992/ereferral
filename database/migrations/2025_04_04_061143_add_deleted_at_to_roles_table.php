<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes(); // This will add the deleted_at column
        });
    }
    
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('deleted_at'); // This will remove the column
        });
    }
    
};
