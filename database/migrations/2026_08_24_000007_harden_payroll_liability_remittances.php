<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'payroll.liability.export',
        'payroll.liability.approve',
        'payroll.liability.reverse',
    ];

    public function up(): void
    {
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->decimal('paid_amount', 18, 4)->default(0)->after('paid_at');
            $table->index(['paid_at', 'pay_item_id'], 'payslip_items_paid_item_idx');
        });

        Schema::create('payroll_remittance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statutory_body_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('submitted');
            $table->date('payment_date');
            $table->decimal('total_amount', 18, 4);
            $table->string('currency_code', 3)->nullable();
            $table->string('payee');
            $table->string('external_reference')->nullable();
            $table->string('filing_reference')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('reversal_reference')->nullable()->unique();
            $table->timestamps();
            $table->index(['company_id', 'status', 'payment_date'], 'payroll_remit_company_status_date_idx');
        });

        Schema::create('payroll_remittance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_remittance_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('pay_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('liability_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('original_amount', 18, 4);
            $table->decimal('amount', 18, 4);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['payroll_remittance_batch_id', 'payslip_item_id'], 'payroll_remit_batch_item_unique');
            $table->index(['company_id', 'payslip_item_id', 'status'], 'payroll_remit_item_status_idx');
        });

        DB::table('payslip_items')->whereNotNull('paid_at')->update(['paid_amount' => DB::raw('amount')]);

        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now(), 'deleted_at' => null]
            );
        }

        $viewPermission = DB::table('permissions')->where('name', 'payroll.liability.view')->value('id');
        $createPermission = DB::table('permissions')->where('name', 'payroll.liability.create')->value('id');
        $viewRoles = $viewPermission ? DB::table('role_permissions')->where('permission_id', $viewPermission)->pluck('role_id') : collect();
        $createRoles = $createPermission ? DB::table('role_permissions')->where('permission_id', $createPermission)->pluck('role_id') : collect();

        foreach ($this->permissions as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            $roles = $name === 'payroll.liability.export' ? $viewRoles : $createRoles;
            foreach ($roles as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_remittance_allocations');
        Schema::dropIfExists('payroll_remittance_batches');
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->dropIndex('payslip_items_paid_item_idx');
            $table->dropColumn('paid_amount');
        });

        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
