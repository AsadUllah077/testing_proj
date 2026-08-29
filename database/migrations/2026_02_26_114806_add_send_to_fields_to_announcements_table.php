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
            $table->string('send_to_type')->nullable()->after('content');

            $table->foreignId('send_to_department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->foreignId('send_to_employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['send_to_department_id']);
            $table->dropForeign(['send_to_employee_id']);
            $table->dropColumn(['send_to_type', 'send_to_department_id', 'send_to_employee_id']);
        });
    }
};
