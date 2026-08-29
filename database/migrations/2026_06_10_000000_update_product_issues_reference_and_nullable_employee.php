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
        Schema::table('product_issues', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('product_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
            $table->string('reference')->nullable()->after('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_issues', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('product_issues', function (Blueprint $table) {
            $table->dropColumn('reference');
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
