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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('reference_id')->nullable();
            $table->string('unique_no')->nullable();
            $table->string('account_label')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('date')->nullable();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit')->nullable();
            $table->decimal('credit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
