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
          Schema::table('general_task_results', function (Blueprint $table) {
            // Update technician_id foreign key
            $table->dropForeign(['technician_id']);
            $table->foreign('technician_id')->references('id')->on('employees')->onDelete('cascade');

            // Update submitted_by foreign key
            $table->dropForeign(['submitted_by']);
            $table->foreign('submitted_by')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('general_task_results', function (Blueprint $table) {
            // Revert technician_id foreign key
            $table->dropForeign(['technician_id']);
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('cascade');

            // Revert submitted_by foreign key
            $table->dropForeign(['submitted_by']);
            $table->foreign('submitted_by')->references('id')->on('users');
        });
    }
};
