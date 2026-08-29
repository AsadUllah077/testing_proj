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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('date')->nullable();
            $table->string('ref_no')->nullable();
            $table->decimal('sub_total')->nullable();
            $table->text('note')->nullable();
            $table->decimal('tax')->nullable();
            $table->decimal('total')->nullable();
            $table->decimal('paid_amount')->nullable();
            $table->decimal('remaining_amount')->nullable();
            $table->integer('status')->nullable()->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
