<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('unique_no')->nullable();        // DRN202603xxxx
            $table->string('ref_no')->nullable();
            $table->string('bill_unique_no')->nullable();   // denormalized from linked bill
            $table->date('date');
            $table->text('note')->nullable();

            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // 0 = draft, 1 = confirmed
            $table->tinyInteger('status')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'date']);
            $table->index('vendor_id');
            $table->index('bill_id');
        });

        Schema::create('debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_items');
        Schema::dropIfExists('debit_notes');
    }
};
