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
         Schema::table('product_assignments', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['technician_id']);
            $table->foreign('technician_id')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_assignments', function (Blueprint $table) {
            // Drop the employees foreign key
            $table->dropForeign(['technician_id']);
            $table->foreign('technician_id')->references('id')->on('users');
        });
    }
};
