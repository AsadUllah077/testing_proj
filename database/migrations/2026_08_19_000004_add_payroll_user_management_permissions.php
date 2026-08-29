<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'payroll.organization-settings.users.view',
        'payroll.organization-settings.users.create',
        'payroll.organization-settings.users.edit',
        'payroll.organization-settings.users.delete',
        'payroll.organization-settings.users.export',
        'payroll.organization-settings.roles.create',
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
            $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
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
