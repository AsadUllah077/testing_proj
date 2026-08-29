<?php
// app/Helpers/PermissionHelper.php

if (!function_exists('canViewModule')) {
    /**
     * Check if user can view a module
     */
    function canViewModule($module)
    {
        $user = auth('web')->user();
        if (!$user) {
            return false;
        }

        // Super admin check
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $modulePermissions = [
            'home' => ['home.dashboard.view'],
            'users' => ['users.dashboard.view', 'users.user.view', 'users.role.view', 'users.permission.view'],
            'accounting' => [
                'account.dashboard.view',
                'account.sale.view',
                'account.bill-invoice.view',
                'account.purchase-order.view',
                'account.journal-entries.view',
                'account.reports.view'
            ],
            'hrm' => [
                'hrm.dashboard.view',
                'hrm.employee.view',
                'hrm.leave.view',
                'hrm.attendance.view'
            ],
            'qms' => ['qms.dashboard.view'],
            'members' => ['members.view'],
            'third-party' => ['third-party.view'],
            'product' => [
                'product.dashboard.view',
                'warehouse.dashboard.view',
                'warehouse.products.view',
                'warehouse.master-data.view',
                'warehouse.barcode.view',
                'warehouse.technicians.view',
                'warehouse.parts.view',
                'warehouse.gate-pass.view',
                'warehouse.scrap.view',
                'warehouse.progress-sheet.view',
                'warehouse.scan.view',
                'warehouse.product-issue.view',
            ],
            'project' => ['project.view'],
            'mailer' => ['mailer.view'],
            'fileshare' => ['fileshare.view'],
            'tech-support' => ['tech-support.view'],
            'chat' => ['chat.view'],
            'activity-logs' => ['activity.logs.view'],
        ];

        if (!isset($modulePermissions[$module])) {
            return false;
        }

        return $user->hasAnyPermission($modulePermissions[$module]);
    }
}
