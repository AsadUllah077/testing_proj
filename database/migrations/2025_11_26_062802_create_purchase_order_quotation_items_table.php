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
        Schema::create('purchase_order_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_quotation_id')
                ->constrained(
                    table: 'purchase_order_quotations',
                    indexName: 'po_quotation_items_id_foreign' // Shorter name here
                )
                ->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->onDelete('cascade');

            $table->string('unique_no')->nullable();
            $table->string('item')->nullable();
            $table->string('manual_item')->nullable();
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('kg')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('project')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->string('created_by')->nullable()->constrained()->onDelete('cascade');
            $table->string('updated_by')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_quotation_items');
    }
};
