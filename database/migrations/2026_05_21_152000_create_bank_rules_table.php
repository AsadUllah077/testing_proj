<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bank rules store auto-learned payee → account mappings.
     * When a user creates a transaction from a bank statement line and selects
     * a contra account, the system remembers that mapping so future lines
     * with the same payee auto-fill the account (Xero-style).
     */
    public function up(): void
    {
        Schema::create('bank_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->string('payee');                          // Normalized payee text
            $table->unsignedBigInteger('contra_account_id');  // The account selected
            $table->unsignedInteger('usage_count')->default(1);
            $table->timestamps();

            // One rule per payee per bank account per company
            $table->unique(['company_id', 'bank_account_id', 'payee'], 'bank_rules_unique');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
            $table->foreign('contra_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');

            $table->index(['company_id', 'bank_account_id'], 'bank_rules_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_rules');
    }
};
