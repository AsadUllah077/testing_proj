<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('source_system')->default('XERO');
            $table->date('conversion_date');
            $table->date('closing_date')->nullable();
            $table->string('fiscal_year')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->text('notes')->nullable();
            $table->decimal('total_debit', 18, 4)->default(0);
            $table->decimal('total_credit', 18, 4)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'reference']);
        });

        Schema::create('opening_balance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_batch_id')->constrained('opening_balance_batches')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->text('description')->nullable();
            $table->string('source_account_code')->nullable();
            $table->string('source_account_name')->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'account_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'opening_balance_batch_id')) {
                $table->foreign('opening_balance_batch_id')->references('id')->on('opening_balance_batches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'opening_balance_batch_id')) {
                $table->dropForeign(['opening_balance_batch_id']);
            }
        });

        Schema::dropIfExists('opening_balance_lines');
        Schema::dropIfExists('opening_balance_batches');
    }
};
