<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_close_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained()->cascadeOnDelete();
            $table->string('task_key', 80);
            $table->boolean('is_completed')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'accounting_period_id', 'task_key'], 'accounting_close_task_unique');
        });

        Schema::create('month_end_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unique_no')->unique();
            $table->string('type', 40);
            $table->date('date');
            $table->string('narration');
            $table->json('lines');
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'accounting_period_id', 'status'], 'month_end_adjustment_lookup');
        });

        Schema::table('bank_rules', function (Blueprint $table) {
            $table->string('name')->nullable()->after('bank_account_id');
            $table->string('match_type', 20)->default('exact')->after('payee');
            $table->string('direction', 20)->default('both')->after('match_type');
            $table->decimal('min_amount', 18, 4)->nullable()->after('direction');
            $table->decimal('max_amount', 18, 4)->nullable()->after('min_amount');
            $table->unsignedInteger('priority')->default(100)->after('max_amount');
            $table->boolean('is_active')->default(true)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('bank_rules', function (Blueprint $table) {
            $table->dropColumn(['name', 'match_type', 'direction', 'min_amount', 'max_amount', 'priority', 'is_active']);
        });

        Schema::dropIfExists('month_end_adjustments');
        Schema::dropIfExists('accounting_close_tasks');
    }
};
