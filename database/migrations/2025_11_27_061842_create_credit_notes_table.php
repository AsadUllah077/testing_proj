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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
             // Foreign keys
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');

            // String fields
            $table->string('sale_unique_no')->nullable();
            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('ref_no')->nullable();

            // Date field
            $table->date('date')->nullable();

            // Decimal fields
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('tax', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->decimal('remaining_amount', 15, 2)->nullable();

            // Integer field
            $table->integer('status')->nullable()->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Optional: Add indexes for better performance
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('sale_id');
            $table->index('date');
            $table->index('status');
            $table->index('unique_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
