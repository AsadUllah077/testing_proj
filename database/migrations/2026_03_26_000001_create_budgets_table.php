<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            // One budget entry per account per period per company
            $table->unique(['company_id', 'accounting_period_id', 'account_id'], 'budgets_unique');
            $table->index(['company_id', 'accounting_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
