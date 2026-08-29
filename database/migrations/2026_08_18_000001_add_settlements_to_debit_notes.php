<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->decimal('refunded_amount', 18, 4)
                ->default(0)
                ->after('allocated_amount')
                ->comment('Amount received back from the supplier into a bank/cash account.');
        });

        Schema::create('debit_note_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->date('allocation_date');
            $table->decimal('amount', 18, 4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'allocation_date']);
            $table->index(['debit_note_id', 'bill_id']);
        });

        Schema::create('debit_note_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_no')->unique();
            $table->date('refund_date');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'refund_date']);
            $table->index('debit_note_id');
        });

        DB::table('debit_notes')
            ->whereNotNull('company_id')
            ->whereNotNull('bill_id')
            ->where('allocated_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($notes) {
                $rows = [];

                foreach ($notes as $note) {
                    $rows[] = [
                        'company_id' => $note->company_id,
                        'debit_note_id' => $note->id,
                        'bill_id' => $note->bill_id,
                        'allocation_date' => $note->date,
                        'amount' => $note->allocated_amount,
                        'created_by' => $note->created_by,
                        'created_at' => $note->created_at,
                        'updated_at' => $note->updated_at,
                    ];
                }

                DB::table('debit_note_allocations')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_refunds');
        Schema::dropIfExists('debit_note_allocations');

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });
    }
};
