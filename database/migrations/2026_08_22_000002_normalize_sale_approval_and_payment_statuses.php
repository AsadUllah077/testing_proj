<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Payment state is now independent; legacy `paid` approval rows remain approved.
        DB::table('sales')
            ->where('approval_status', 'paid')
            ->update(['approval_status' => 'approved', 'payment_status' => 'paid']);
    }

    public function down(): void
    {
        // Intentionally not reversed: approval and payment represent different facts.
    }
};
