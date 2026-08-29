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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('date')->nullable();
            $table->string('ref_no')->nullable();
            $table->text('note')->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('tax', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->decimal('remaining_amount', 15, 2)->nullable();
            $table->integer('status')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better performance
            $table->index('company_id');
            $table->index('accounting_period_id');
            $table->index('customer_id');
            $table->index('account_id');
            $table->index('currency_id');
            $table->index('unique_no');
            $table->index('invoice_no');
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
