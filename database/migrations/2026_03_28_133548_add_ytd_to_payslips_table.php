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
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('ytd_gross', 15, 2)->default(0)->after('net_pay');
            $table->decimal('ytd_deductions', 15, 2)->default(0)->after('ytd_gross');
            $table->decimal('ytd_net', 15, 2)->default(0)->after('ytd_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['ytd_gross', 'ytd_deductions', 'ytd_net']);
        });
    }
};
