<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Role::insert([
            ['name' => 'Super Admin'],
            ['name' => 'Admin'],
            ['name' => 'User'],
            ['name' => 'HR Admin'],
            ['name' => 'Employee'],
            ['name' => 'Accountant Manager'],
        ]);

        // Note: In a real scenario, we would use a helper or loop to assign permissions.
        // For this task, I'll ensure the HR Admin has the necessary API permissions.
        $hrAdmin = Role::where('name', 'HR Admin')->first();
        if ($hrAdmin) {
            $hrPermissions = \App\Models\Permission::whereIn('name', [
                'api.attendance.checkin',
                'api.attendance.checkout',
                'api.leave.request',
                'api.leave.approve',
                'api.leave.reject',
                'api.leave.view_all',
                'api.report.all',
                'api.announcement.view',
            ])->pluck('id')->toArray();
            $hrAdmin->permissions()->sync($hrPermissions);
        }

        $employee = Role::where('name', 'Employee')->first();
        if ($employee) {
            $empPermissions = \App\Models\Permission::whereIn('name', [
                'api.attendance.checkin',
                'api.attendance.checkout',
                'api.leave.request',
                'api.report.individual',
                'api.announcement.view',
            ])->pluck('id')->toArray();
            $employee->permissions()->sync($empPermissions);
        }

        $accountantManager = Role::where('name', 'Accountant Manager')->first();
        if ($accountantManager) {
            $accountantManager->permissions()->syncWithoutDetaching(
                \App\Models\Permission::where('name', 'like', 'account.organization-settings.users.%')
                    ->orWhere('name', 'like', 'account.organization-settings.roles.%')
                    ->pluck('id')
                    ->all()
            );
        }

        $hrAdmin = Role::where('name', 'HR Admin')->first();
        if ($hrAdmin) {
            $hrAdmin->permissions()->syncWithoutDetaching(
                \App\Models\Permission::where('name', 'like', 'payroll.%')
                    ->pluck('id')
                    ->all()
            );
        }

    }
}
