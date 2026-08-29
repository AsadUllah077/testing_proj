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
        Schema::table('announcements', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['send_to_department_id']);
            $table->dropForeign(['send_to_employee_id']);

            // Change columns to text to store multiple IDs as JSON array
            $table->text('send_to_department_id')->nullable()->change();
            $table->text('send_to_employee_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // Re-revert to bigInteger (this might fail if data is not numeric, but down is usually best effort)
            $table->unsignedBigInteger('send_to_department_id')->nullable()->change();
            $table->unsignedBigInteger('send_to_employee_id')->nullable()->change();

            // Re-add constraints
            $table->foreign('send_to_department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('send_to_employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
