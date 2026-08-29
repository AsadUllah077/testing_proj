<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correct legacy/UAT rows where `total` contains the tax-inclusive
        // amount while `total_after_discount` incorrectly contains net sales.
        DB::table('credit_notes')
            ->whereRaw('ABS(COALESCE(total, 0) - (COALESCE(sub_total, 0) - COALESCE(discount, 0) + COALESCE(tax, 0))) <= 0.01')
            ->whereRaw('ABS(COALESCE(total_after_discount, 0) - (COALESCE(sub_total, 0) - COALESCE(discount, 0) + COALESCE(tax, 0))) > 0.01')
            ->update([
                'total_after_discount' => DB::raw('COALESCE(sub_total, 0) - COALESCE(discount, 0) + COALESCE(tax, 0)'),
            ]);

        Schema::create('credit_note_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->date('allocation_date');
            $table->decimal('amount', 18, 4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'allocation_date']);
            $table->index(['credit_note_id', 'sale_id']);
        });

        Schema::create('credit_note_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_no')->unique();
            $table->date('refund_date');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'refund_date']);
            $table->index('credit_note_id');
        });

        DB::table('credit_notes')
            ->whereNotNull('company_id')
            ->whereNotNull('sale_id')
            ->where('allocated_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($notes) {
                $rows = [];
                foreach ($notes as $note) {
                    $rows[] = [
                        'company_id' => $note->company_id,
                        'credit_note_id' => $note->id,
                        'sale_id' => $note->sale_id,
                        'allocation_date' => $note->date,
                        'amount' => $note->allocated_amount,
                        'created_by' => $note->created_by,
                        'created_at' => $note->created_at,
                        'updated_at' => $note->updated_at,
                    ];
                }
                DB::table('credit_note_allocations')->insert($rows);
            });

        DB::table('credit_notes')
            ->whereNotNull('company_id')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($notes) {
                $rows = [];
                foreach ($notes as $note) {
                    $rows[] = [
                        'company_id' => $note->company_id,
                        'credit_note_id' => $note->id,
                        'account_id' => $note->account_id,
                        'accounting_period_id' => $note->accounting_period_id,
                        'refund_no' => 'CRF-LEGACY-' . $note->id,
                        'refund_date' => $note->date,
                        'amount' => $note->paid_amount,
                        'description' => 'Legacy customer refund balance',
                        'created_by' => $note->created_by,
                        'created_at' => $note->created_at,
                        'updated_at' => $note->updated_at,
                    ];
                }
                DB::table('credit_note_refunds')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_refunds');
        Schema::dropIfExists('credit_note_allocations');
    }
};
