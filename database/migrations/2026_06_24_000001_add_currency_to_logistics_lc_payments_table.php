<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_lc_payments', 'currency')) {
                $table->string('currency', 3)->default('PKR')->after('amount');
            }
        });

        DB::table('logistics_lc_payments')
            ->whereNull('currency')
            ->update(['currency' => 'PKR']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            if (Schema::hasColumn('logistics_lc_payments', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
