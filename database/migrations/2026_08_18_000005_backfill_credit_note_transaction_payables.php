<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->whereIn('type', ['Credit Note', 'CreditNoteRefund'])
            ->whereNotNull('customer_id')
            ->where(function ($query) {
                $query->whereNull('payable_type')->orWhereNull('payable_id');
            })
            ->update([
                'payable_type' => 'customer',
                'payable_id' => DB::raw('customer_id'),
            ]);
    }

    public function down(): void
    {
        DB::table('transactions')
            ->whereIn('type', ['Credit Note', 'CreditNoteRefund'])
            ->where('payable_type', 'customer')
            ->update(['payable_type' => null, 'payable_id' => null]);
    }
};
