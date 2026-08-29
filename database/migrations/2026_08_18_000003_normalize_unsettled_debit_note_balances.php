<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Notes inserted after the original allocation migration may have all
        // usage fields at their defaults. Preserve bill balances and make the
        // note available for an explicit allocation or supplier refund.
        DB::table('debit_notes')
            ->where('total', '>', 0)
            ->where('allocated_amount', 0)
            ->where('refunded_amount', 0)
            ->where('remaining_amount', 0)
            ->update([
                'remaining_amount' => DB::raw('total'),
            ]);
    }

    public function down(): void
    {
        // This is a data correction and cannot be safely inferred in reverse.
    }
};
