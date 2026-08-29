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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->cascadeOnDelete();
            $table->integer('default_sale_tax_rate_id')->nullable();
            $table->integer('default_purchase_tax_rate_id')->nullable();
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('tax_period', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
