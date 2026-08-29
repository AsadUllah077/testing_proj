<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payment_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('run_reference')->unique(); // e.g. VPR-2026-0001
            $table->date('payment_date');
            $table->string('status')->default('draft'); // draft|confirmed|processed
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('payment_date');
        });

        Schema::create('vendor_payment_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payment_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->decimal('amount_to_pay', 15, 2);
            $table->decimal('bill_total', 15, 2);
            $table->decimal('bill_remaining', 15, 2);
            $table->timestamps();

            $table->index('vendor_payment_run_id');
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_run_items');
        Schema::dropIfExists('vendor_payment_runs');
    }
};
