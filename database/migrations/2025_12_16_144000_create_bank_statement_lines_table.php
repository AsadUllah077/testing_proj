<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('bank_statement_lines');

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            
            $table->date('date');
            $table->decimal('amount', 15, 2); // Positive for deposits, negative for withdrawals
            $table->string('payee')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            
            // Link to a system transaction when reconciled
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Status of the line
            $table->enum('status', ['Unreconciled', 'Reconciled', 'Deleted'])->default('Unreconciled');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
