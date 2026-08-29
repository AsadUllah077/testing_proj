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
        Schema::create('purchase_order_quotations', function (Blueprint $table) {
            $table->id();
             $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('order_date')->nullable();
            $table->date('departure_date')->nullable();
            $table->string('order_no')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('requested_by')->nullable();
            $table->text('note')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('total_discount', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('remaining_amount', 10, 2)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('delivery_instruction')->nullable();
            $table->integer('status')->default(0);
            $table->string('created_by')->nullable()->constrained()->onDelete('cascade');
            $table->string('updated_by')->nullable()->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_quotations');
    }
};
