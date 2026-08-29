<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissionsByRole = [
        'Accountant Manager' => 'account.organization-settings.roles.edit',
        'HR Admin' => 'payroll.organization-settings.roles.edit',
    ];

    public function up(): void
    {
        foreach ($this->permissionsByRole as $roleName => $permissionName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permissionName],
                ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
            );

            $roleId = DB::table('roles')->whereRaw('LOWER(name) = ?', [strtolower($roleName)])->value('id');
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
            if ($roleId && $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', array_values($this->permissionsByRole))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
