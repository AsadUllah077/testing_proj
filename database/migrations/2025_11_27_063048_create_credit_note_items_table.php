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
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id(); // Foreign keys
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('credit_note_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');

            // String fields
            $table->string('sale_unique_no')->nullable();
            $table->string('unique_no')->nullable();
            $table->string('item')->nullable();
            $table->string('manual_item')->nullable();
            $table->string('hs_code')->nullable();

            // Text field
            $table->text('description')->nullable();

            // Integer fields
            $table->integer('quantity')->nullable()->default(0);
            $table->integer('kg')->nullable()->default(0);

            // Decimal fields
            $table->decimal('price', 15, 2)->nullable()->default(0);
            $table->decimal('total', 15, 2)->nullable()->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Optional: Add indexes for better performance
            $table->index('company_id');
            $table->index('credit_note_id');
            $table->index('account_id');
            $table->index('tax_id');
            $table->index('hs_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};
