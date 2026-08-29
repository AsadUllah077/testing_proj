<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'account.organization-settings.users.view',
        'account.organization-settings.users.create',
        'account.organization-settings.users.edit',
        'account.organization-settings.users.delete',
        'account.organization-settings.users.export',
        'account.dashboard.layout.edit',
        'account.dashboard.balance-sync',
        'account.dashboard.payroll-post',
        'account.bank-rules.view',
        'account.bank-rules.create',
        'account.bank-rules.edit',
        'account.bank-rules.delete',
        'account.ai-suggestion.view',
        'account.duplicate-detection.view',
        'account.anomaly-detection.view',
        'account.anomaly-detection.execute',
        'account.credit-note.edit',
        'account.credit-note.delete',
        'account.credit-note.export',
        'account.invoice-setting.create',
        'account.invoice-setting.delete',
        'account.organization-profile.view',
        'account.organization-profile.edit',
        'account.currency-setting.view',
        'account.chart-of-account.export',
        'account.chart-of-account.import',
        'account.chart-of-account.bulk-delete',
        'account.chart-of-account.bulk-archive',
        'account.chart-of-account.bulk-restore',
        'account.chart-of-account.bulk-edit',
        'account.recurring-invoice.view',
        'account.recurring-invoice.create',
        'account.recurring-invoice.edit',
        'account.recurring-invoice.delete',
        'account.recurring-invoice.toggle-status',
        'account.recurring-bill.view',
        'account.recurring-bill.create',
        'account.recurring-bill.edit',
        'account.recurring-bill.delete',
        'account.recurring-bill.toggle-status',
        'account.debit-note.view',
        'account.debit-note.create',
        'account.debit-note.show',
        'account.debit-note.edit',
        'account.debit-note.delete',
        'account.debit-note.allocate',
        'account.debit-note.refund',
        'account.debit-note.export',
        'account.budget.view',
        'account.budget.edit',
        'account.budget.delete',
        'account.budget.report',
        'account.fixed-asset.view',
        'account.fixed-asset.create',
        'account.fixed-asset.show',
        'account.fixed-asset.edit',
        'account.fixed-asset.delete',
        'account.cash-flow-forecast.view',
        'account.liability.view',
        'account.liability.create',
        'account.tax-period.view',
        'account.tax-period.create',
        'account.tax-period.show',
        'account.tax-period.export',
        'account.tax-period.settle',
        'account.tax-period.delete',
        'account.tax-period.payment',
        'account.bank-reconciliation.export',
        'account.bank-reconciliation.spend-money',
        'account.bank-reconciliation.receive-money',
        'account.bank-reconciliation.create-line',
        'account.bank-reconciliation.edit',
        'account.bank-reconciliation.unreconcile',
        'account.bank-reconciliation.delete',
        'account.transaction-to-invoice.view',
        'account.payment-reminder.view',
        'account.payment-reminder.send',
        'account.partial-payment.view',
        'account.partial-payment.create',
        'account.partial-payment.show',
        'account.partial-payment.confirm',
        'account.partial-payment.export',
        'account.invoice-allocation.view',
        'account.invoice-allocation.create',
        'account.approval.view',
        'account.approval.submit',
        'account.approval.approve',
        'account.approval.reject',
        'account.customer-statement.view',
        'account.customer-statement.export',
        'account.customer-statement.email',
        'account.vendor-statement.view',
        'account.vendor-statement.export',
        'account.vendor-statement.email',
        'account.vendor-payment-run.view',
        'account.vendor-payment-run.create',
        'account.vendor-payment-run.show',
        'account.vendor-payment-run.confirm',
        'account.vendor-payment-run.delete',
        'account.vendor-payment-run.export',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
            );
        }

        $roleId = DB::table('roles')->whereRaw('LOWER(name) = ?', ['accountant manager'])->value('id');
        if ($roleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('name', [
                    'account.organization-settings.users.view',
                    'account.organization-settings.users.create',
                    'account.organization-settings.users.edit',
                    'account.organization-settings.users.delete',
                    'account.organization-settings.users.export',
                ])
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
