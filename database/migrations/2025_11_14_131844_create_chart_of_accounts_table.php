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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('account_number');
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->enum('category', [
                'Assets',
                'Liabilities',
                'Equity',
                'Revenue',
                'COGS',
                'Expenses'
            ]);
            $table->enum('type', ['Group', 'Account'])->default('Account');
            $table->boolean('is_system')->default(false);
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
