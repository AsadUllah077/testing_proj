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
        Schema::table('visual_inspections', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['inspected_by']);
            $table->foreign('inspected_by')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('visual_inspections', function (Blueprint $table) {
            // Drop the employees foreign key
            $table->dropForeign(['inspected_by']);
            $table->foreign('inspected_by')->references('id')->on('users');
        });
    }
};
