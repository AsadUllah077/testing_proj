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
        Schema::create('sale_items', function (Blueprint $table) {
             $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('unique_no')->nullable();
            $table->string('item')->nullable();
            $table->string('manual_item')->nullable();
            $table->text('description')->nullable();
            $table->string('hs_code')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('kg')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->onDelete('cascade');
            $table->decimal('total', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better performance
            $table->index('company_id');
            $table->index('accounting_period_id');
            $table->index('sale_id');
            $table->index('unique_no');
            $table->index('item');
            $table->index('hs_code');
            $table->index('account_id');
            $table->index('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
