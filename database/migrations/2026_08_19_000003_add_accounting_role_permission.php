<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permission = 'account.organization-settings.roles.create';

    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission],
            ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
        );

        $roleId = DB::table('roles')->whereRaw('LOWER(name) = ?', ['accountant manager'])->value('id');
        $permissionId = DB::table('permissions')->where('name', $this->permission)->value('id');
        if ($roleId && $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => now(), 'created_at' => now(), 'deleted_at' => null]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', $this->permission)->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
