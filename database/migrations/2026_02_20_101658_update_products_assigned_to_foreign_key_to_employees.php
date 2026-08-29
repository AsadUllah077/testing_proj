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
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        // Add new foreign key referencing employees table
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Drop the employees foreign key
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        // Restore the users foreign key
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }
};
