<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'payroll.control-center.view',
        'payroll.operations.view',
        'payroll.operations.scenario.create',
        'payroll.operations.increment-letter.export',
        'payroll.salary-advance.view',
        'payroll.salary-advance.create',
        'payroll.salary-advance.edit',
        'payroll.salary-advance.delete',
        'payroll.salary-advance.approve',
        'payroll.salary-advance.issue',
        'payroll.pay-run.transactions.view',
        'payroll.pay-run.export',
        'payroll.pay-run.post',
        'payroll.pay-run.notify-accounting',
        'payroll.pay-run.pay',
        'payroll.pay-run.validate',
        'payroll.payslip.view',
        'payroll.payslip.export',
        'payroll.payslip.email',
        'payroll.payslip.edit',
        'payroll.employee-pay.export',
        'payroll.tax-bracket.import',
        'payroll.compliance.view',
        'payroll.compliance.manage',
        'payroll.reports.export',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
            );
        }

        $roleId = DB::table('roles')->whereRaw('LOWER(name) = ?', ['hr admin'])->value('id');
        if ($roleId) {
            $permissionIds = DB::table('permissions')
                ->where('name', 'like', 'payroll.%')
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
