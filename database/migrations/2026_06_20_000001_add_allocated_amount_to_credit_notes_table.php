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
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->decimal('allocated_amount', 18, 4)
                ->default(0)
                ->after('paid_amount')
                ->comment('Amount of this credit note allocated to a linked sales invoice.');
        });

        DB::table('credit_notes')
            ->whereNotNull('sale_id')
            ->update([
                'allocated_amount' => DB::raw('COALESCE(total_after_discount, total, 0)'),
                'remaining_amount' => 0,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('allocated_amount');
        });
    }
};
