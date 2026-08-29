<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->nullable()->after('pay_item_id');
        });

        // Data migration: Parse existing overtime hours from description
        // Format: "Overtime (10 hrs @ 1,500.00/hr)"
        DB::table('payslip_items')
            ->where('description', 'like', 'Overtime (%')
            ->get()
            ->each(function ($item) {
                if (preg_match('/Overtime \((\d+(\.\d+)?)\s*hrs/', $item->description, $matches)) {
                    DB::table('payslip_items')
                        ->where('id', $item->id)
                        ->update(['quantity' => $matches[1]]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
