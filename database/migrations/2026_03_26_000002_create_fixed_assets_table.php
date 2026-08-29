<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('asset_number')->unique();   // FA-202603-0001
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();     // equipment, vehicle, building, furniture, intangible

            $table->date('purchase_date');
            $table->date('in_service_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('depreciation_method')->default('straight_line'); // straight_line | declining_balance

            $table->decimal('current_book_value', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->date('last_depreciation_date')->nullable();

            $table->string('status')->default('active'); // active | fully_depreciated | disposed
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
