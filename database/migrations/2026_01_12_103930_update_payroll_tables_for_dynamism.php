<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_items', function (Blueprint $table) {
            $table->string('calculation_type')->default('fixed'); // fixed, percentage
            $table->decimal('percentage_value', 10, 2)->nullable();
            $table->foreignId('base_item_id')->nullable()->constrained('pay_items')->onDelete('set null');
        });

        Schema::table('pay_runs', function (Blueprint $table) {
            $table->string('frequency')->default('monthly'); // weekly, fortnightly, monthly
        });

        Schema::table('employee_pay_structures', function (Blueprint $table) {
            $table->integer('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pay_items', function (Blueprint $table) {
            $table->dropForeign(['base_item_id']);
            $table->dropColumn(['calculation_type', 'percentage_value', 'base_item_id']);
        });

        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });

        Schema::table('employee_pay_structures', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
