<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->whereIn('type', ['DebitNote', 'DebitNoteRefund'])
            ->whereNotNull('vendor_id')
            ->where(function ($query) {
                $query->whereNull('payable_type')->orWhereNull('payable_id');
            })
            ->update([
                'payable_type' => 'vendor',
                'payable_id' => DB::raw('vendor_id'),
            ]);
    }

    public function down(): void
    {
        DB::table('transactions')
            ->whereIn('type', ['DebitNote', 'DebitNoteRefund'])
            ->where('payable_type', 'vendor')
            ->update([
                'payable_type' => null,
                'payable_id' => null,
            ]);
    }
};
