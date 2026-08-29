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
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->decimal('allocated_amount', 18, 4)
                ->default(0)
                ->after('total')
                ->comment('Amount of this debit note allocated to a linked bill.');

            $table->decimal('remaining_amount', 18, 4)
                ->default(0)
                ->after('allocated_amount')
                ->comment('Debit note amount still available as supplier credit.');
        });

        DB::statement(<<<'SQL'
            UPDATE debit_notes dn
            SET allocated_amount = LEAST(COALESCE(dn.total, 0), COALESCE(b.remaining_amount, 0)),
                remaining_amount = GREATEST(COALESCE(dn.total, 0) - LEAST(COALESCE(dn.total, 0), COALESCE(b.remaining_amount, 0)), 0)
            FROM bills b
            WHERE dn.bill_id = b.id
        SQL);

        DB::statement(<<<'SQL'
            UPDATE debit_notes
            SET remaining_amount = COALESCE(total, 0)
            WHERE bill_id IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE bills b
            SET remaining_amount = GREATEST(COALESCE(b.remaining_amount, 0) - x.allocated_amount, 0),
                approval_status = CASE
                    WHEN GREATEST(COALESCE(b.remaining_amount, 0) - x.allocated_amount, 0) <= 0 THEN 'paid'
                    ELSE b.approval_status
                END
            FROM (
                SELECT bill_id, SUM(allocated_amount) AS allocated_amount
                FROM debit_notes
                WHERE bill_id IS NOT NULL
                GROUP BY bill_id
            ) x
            WHERE b.id = x.bill_id
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn(['allocated_amount', 'remaining_amount']);
        });
    }
};
