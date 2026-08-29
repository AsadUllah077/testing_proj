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
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('accounting_period_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('unique_no')->nullable();
            $table->string('item')->nullable();
            $table->string('manual_item')->nullable();
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('kg')->nullable();
            $table->decimal('price')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->cascadeOnDelete();
            $table->decimal('total')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Optional: Add indexes for better performance
            $table->index('bill_id');
            $table->index('company_id');
            $table->index('accounting_period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
