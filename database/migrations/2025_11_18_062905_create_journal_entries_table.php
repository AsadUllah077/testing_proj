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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
             // Foreign Keys
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            // Core Fields
            $table->string('unique_no')->nullable();     // Auto-generated journal no.
            $table->text('narration')->nullable();
            $table->date('date');                       // Correct type is DATE

            $table->integer('item')->nullable();         // If selected from system items
            $table->string('manual_item')->nullable();  // If user types item name

            $table->text('description')->nullable();

            // Debit / Credit values
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
