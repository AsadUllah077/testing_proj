<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ========== HOME MODULE ==========
            ['name' => 'home.dashboard.view'],
            ['name' => 'home.companies.view'],
            ['name' => 'home.companies.create'],
            ['name' => 'home.companies.edit'],
            ['name' => 'home.companies.delete'],
            ['name' => 'home.companies.show'],

            // ========== HRM MODULE ==========
            ['name' => 'hrm.dashboard.view'],
            ['name' => 'hrm.reports.view'],
            ['name' => 'hrm.reports.export'],

            // Warnings
            ['name' => 'hrm.warnings.view'],
            ['name' => 'hrm.warnings.create'],
            ['name' => 'hrm.warnings.edit'],
            ['name' => 'hrm.warnings.delete'],

            // Resignations
            ['name' => 'hrm.resignations.view'],
            ['name' => 'hrm.resignations.create'],
            ['name' => 'hrm.resignations.edit'],
            ['name' => 'hrm.resignations.delete'],

            // Complaints
            ['name' => 'hrm.complaints.view'],
            ['name' => 'hrm.complaints.create'],
            ['name' => 'hrm.complaints.edit'],
            ['name' => 'hrm.complaints.delete'],

            // Surveys
            ['name' => 'hrm.surveys.view'],
            ['name' => 'hrm.surveys.create'],
            ['name' => 'hrm.surveys.edit'],
            ['name' => 'hrm.surveys.delete'],
            ['name' => 'hrm.surveys.participate'],
            ['name' => 'hrm.surveys.analytics'],

            // Employee
            ['name' => 'hrm.employee.view'],
            ['name' => 'hrm.employee.create'],
            ['name' => 'hrm.employee.edit'],
            ['name' => 'hrm.employee.delete'],
            ['name' => 'hrm.employee.show'],
            ['name' => 'hrm.employee.change-status'],

            // Employee Type
            ['name' => 'hrm.employee-type.view'],
            ['name' => 'hrm.employee-type.create'],
            ['name' => 'hrm.employee-type.edit'],
            ['name' => 'hrm.employee-type.delete'],
            ['name' => 'hrm.employee-type.show'],

            // Department
            ['name' => 'hrm.department.view'],
            ['name' => 'hrm.department.create'],
            ['name' => 'hrm.department.edit'],
            ['name' => 'hrm.department.delete'],
            ['name' => 'hrm.department.show'],

            // Leave Types
            ['name' => 'hrm.leave-types.view'],
            ['name' => 'hrm.leave-types.create'],
            ['name' => 'hrm.leave-types.edit'],
            ['name' => 'hrm.leave-types.delete'],
            ['name' => 'hrm.leave-types.show'],

            // Leave
            ['name' => 'hrm.leave.view'],
            ['name' => 'hrm.leave.create'],
            ['name' => 'hrm.leave.edit'],
            ['name' => 'hrm.leave.delete'],
            ['name' => 'hrm.leave.show'],
            ['name' => 'hrm.leave.approve'],
            ['name' => 'hrm.leave.calculate-days'],
            ['name' => 'hrm.leave.get-balance'],
            ['name' => 'hrm.leave.update-status'],
            ['name' => 'hrm.leave.calendar.view'],

            // Attendance
            ['name' => 'hrm.attendance.view'],
            ['name' => 'hrm.attendance.create'],
            ['name' => 'hrm.attendance.edit'],
            ['name' => 'hrm.attendance.delete'],
            ['name' => 'hrm.attendance.show'],
            ['name' => 'hrm.attendance.import'],
            ['name' => 'hrm.attendance.export'],
            ['name' => 'hrm.attendance.bulk-checkin'],
            ['name' => 'hrm.attendance.bulk-checkout'],

            // ========== ACCOUNT MODULE ==========
            ['name' => 'account.dashboard.view'],
            ['name' => 'account.kpi.view'],

            // Sales
            ['name' => 'account.sale.view'],
            ['name' => 'account.sale.create'],
            ['name' => 'account.sale.edit'],
            ['name' => 'account.sale.delete'],
            ['name' => 'account.sale.show'],
            ['name' => 'account.sale.export'],
            ['name' => 'account.sale.export-pdf'],
            ['name' => 'account.sale.import'],
            ['name' => 'account.sale.record-payment'],
            ['name' => 'account.sale.transaction.update'],

            // Sale Transactions
            ['name' => 'account.sale-transaction.view'],

            // Bill Transactions
            ['name' => 'account.bill-transaction.view'],

            // Purchase Orders
            ['name' => 'account.purchase-order.view'],
            ['name' => 'account.purchase-order.create'],
            ['name' => 'account.purchase-order.edit'],
            ['name' => 'account.purchase-order.delete'],
            ['name' => 'account.purchase-order.show'],
            ['name' => 'account.purchase-order.export'],
            ['name' => 'account.purchase-order.export-pdf'],

            // Purchase Order Quotations
            ['name' => 'account.purchase-order-quotation.view'],
            ['name' => 'account.purchase-order-quotation.create'],
            ['name' => 'account.purchase-order-quotation.edit'],
            ['name' => 'account.purchase-order-quotation.delete'],
            ['name' => 'account.purchase-order-quotation.show'],
            ['name' => 'account.purchase-order-quotation.export'],
            ['name' => 'account.purchase-order-quotation.export-pdf'],
            ['name' => 'account.purchase-order-quotation.record-payment'],

            // Credit Notes
            ['name' => 'account.credit-note.view'],
            ['name' => 'account.credit-note.create'],
            ['name' => 'account.credit-note.show'],
            ['name' => 'account.credit-note.record-payment'],
            ['name' => 'account.credit-note.transaction.update'],

            // Bills
            ['name' => 'account.bill-invoice.view'],
            ['name' => 'account.bill-invoice.create'],
            ['name' => 'account.bill-invoice.edit'],
            ['name' => 'account.bill-invoice.delete'],
            ['name' => 'account.bill-invoice.show'],
            ['name' => 'account.bill-invoice.export'],
            ['name' => 'account.bill-invoice.export-pdf'],
            ['name' => 'account.bill-invoice.import'],
            ['name' => 'account.bill-invoice.record-payment'],
            ['name' => 'account.bill-invoice.transaction.update'],

            // Organization Settings
            ['name' => 'account.organization-settings.view'],
            ['name' => 'account.invoice-setting.view'],
            ['name' => 'account.invoice-setting.edit'],

            // Accounting Period
            ['name' => 'account.accounting-period.view'],
            ['name' => 'account.accounting-period.create'],
            ['name' => 'account.accounting-period.edit'],
            ['name' => 'account.accounting-period.delete'],
            ['name' => 'account.accounting-period.show'],
            ['name' => 'account.accounting-period.update-status'],

            // Chart of Accounts
            ['name' => 'account.chart-of-account.view'],
            ['name' => 'account.chart-of-account.create'],
            ['name' => 'account.chart-of-account.edit'],
            ['name' => 'account.chart-of-account.delete'],
            ['name' => 'account.chart-of-account.show'],

            // Opening Balance Conversion
            ['name' => 'account.opening-balance-conversion.view'],
            ['name' => 'account.opening-balance-conversion.create'],
            ['name' => 'account.opening-balance-conversion.edit'],
            ['name' => 'account.opening-balance-conversion.validate'],
            ['name' => 'account.opening-balance-conversion.post'],
            ['name' => 'account.opening-balance-conversion.reverse'],
            ['name' => 'account.opening-balance-conversion.import'],

            // Journal Entries
            ['name' => 'account.journal-entries.view'],
            ['name' => 'account.journal-entries.create'],
            ['name' => 'account.journal-entries.edit'],
            ['name' => 'account.journal-entries.delete'],
            ['name' => 'account.journal-entries.show'],
            ['name' => 'account.journal-entries.reverse'],

            // Bank Reconciliation
            ['name' => 'account.bank-reconciliation.view'],
            ['name' => 'account.bank-reconciliation.upload'],
            ['name' => 'account.bank-reconciliation.report'],
            ['name' => 'account.bank-reconciliation.create-transaction'],
            ['name' => 'account.bank-reconciliation.match'],
            ['name' => 'account.bank-reconciliation.transfer'],

            // Reports
            ['name' => 'account.reports.view'],
            ['name' => 'account.reports.journal'],
            ['name' => 'account.reports.ledger'],
            ['name' => 'account.reports.trial-balance'],
            ['name' => 'account.reports.balance-sheet'],
            ['name' => 'account.reports.profit-loss'],
            ['name' => 'account.reports.cash-flow'],
            ['name' => 'account.reports.aged-payable'],
            ['name' => 'account.reports.aged-receivable'],
            ['name' => 'account.reports.audit-trail'],

            // Customers
            ['name' => 'account.customers.view'],
            ['name' => 'account.customers.create'],
            ['name' => 'account.customers.edit'],
            ['name' => 'account.customers.delete'],
            ['name' => 'account.customers.bulk-delete'],
            ['name' => 'account.customers.store-inline'],

            // Vendors
            ['name' => 'account.vendors.view'],
            ['name' => 'account.vendors.create'],
            ['name' => 'account.vendors.edit'],
            ['name' => 'account.vendors.delete'],
            ['name' => 'account.vendors.bulk-delete'],
            ['name' => 'account.vendors.store-inline'],
            ['name' => 'account.vendors.export'],

            // Tax Rates
            ['name' => 'account.tax-rate.view'],
            ['name' => 'account.tax-rate.create'],
            ['name' => 'account.tax-rate.edit'],
            ['name' => 'account.tax-rate.delete'],

            // ========== USER MANAGEMENT MODULE ==========
            ['name' => 'users.dashboard.view'],

            // Users
            ['name' => 'users.user.view'],
            ['name' => 'users.user.create'],
            ['name' => 'users.user.edit'],
            ['name' => 'users.user.delete'],
            ['name' => 'users.user.show'],
            ['name' => 'users.user.export'],

            // Roles
            ['name' => 'users.role.view'],
            ['name' => 'users.role.create'],
            ['name' => 'users.role.edit'],
            ['name' => 'users.role.delete'],
            ['name' => 'users.role.show'],

            // Permissions
            ['name' => 'users.permission.view'],
            ['name' => 'users.permission.create'],
            ['name' => 'users.permission.edit'],
            ['name' => 'users.permission.delete'],

            // Role Permissions
            ['name' => 'users.role-permission.view'],
            ['name' => 'users.role-permission.assign'],

            // Accounting module user and role management
            ['name' => 'account.organization-settings.users.view'],
            ['name' => 'account.organization-settings.users.create'],
            ['name' => 'account.organization-settings.users.edit'],
            ['name' => 'account.organization-settings.users.delete'],
            ['name' => 'account.organization-settings.users.export'],
            ['name' => 'account.organization-settings.roles.create'],
            ['name' => 'account.organization-settings.roles.edit'],

            // ========== PAYROLL MODULE ==========
            ['name' => 'payroll.dashboard.view'],
            ['name' => 'payroll.organization-settings.users.view'],
            ['name' => 'payroll.organization-settings.users.create'],
            ['name' => 'payroll.organization-settings.users.edit'],
            ['name' => 'payroll.organization-settings.users.delete'],
            ['name' => 'payroll.organization-settings.users.export'],
            ['name' => 'payroll.organization-settings.roles.create'],
            ['name' => 'payroll.organization-settings.roles.edit'],

            // Control Center and Operations
            ['name' => 'payroll.control-center.view'],
            ['name' => 'payroll.operations.view'],
            ['name' => 'payroll.operations.scenario.create'],
            ['name' => 'payroll.operations.increment-letter.export'],

            // Employee Pay
            ['name' => 'payroll.employee-pay.view'],
            ['name' => 'payroll.employee-pay.edit'],
            ['name' => 'payroll.employee-pay.show'],
            ['name' => 'payroll.employee-pay.export'],

            // Pay Run
            ['name' => 'payroll.pay-run.view'],
            ['name' => 'payroll.pay-run.create'],
            ['name' => 'payroll.pay-run.edit'],
            ['name' => 'payroll.pay-run.delete'],
            ['name' => 'payroll.pay-run.show'],
            ['name' => 'payroll.pay-run.approve'],
            ['name' => 'payroll.pay-run.transactions.view'],
            ['name' => 'payroll.pay-run.export'],
            ['name' => 'payroll.pay-run.post'],
            ['name' => 'payroll.pay-run.notify-accounting'],
            ['name' => 'payroll.pay-run.pay'],
            ['name' => 'payroll.pay-run.validate'],

            // Payslips
            ['name' => 'payroll.payslip.view'],
            ['name' => 'payroll.payslip.export'],
            ['name' => 'payroll.payslip.email'],
            ['name' => 'payroll.payslip.edit'],

            // Pay Items
            ['name' => 'payroll.pay-item.view'],
            ['name' => 'payroll.pay-item.create'],
            ['name' => 'payroll.pay-item.edit'],
            ['name' => 'payroll.pay-item.delete'],

            // Salary Advances
            ['name' => 'payroll.salary-advance.view'],
            ['name' => 'payroll.salary-advance.create'],
            ['name' => 'payroll.salary-advance.edit'],
            ['name' => 'payroll.salary-advance.delete'],
            ['name' => 'payroll.salary-advance.approve'],
            ['name' => 'payroll.salary-advance.issue'],

            // Salary Benchmarks
            ['name' => 'payroll.salary-benchmark.view'],
            ['name' => 'payroll.salary-benchmark.create'],
            ['name' => 'payroll.salary-benchmark.edit'],
            ['name' => 'payroll.salary-benchmark.delete'],

            // Tax Brackets
            ['name' => 'payroll.tax-bracket.view'],
            ['name' => 'payroll.tax-bracket.create'],
            ['name' => 'payroll.tax-bracket.edit'],
            ['name' => 'payroll.tax-bracket.delete'],
            ['name' => 'payroll.tax-bracket.import'],

            // Statutory Bodies
            ['name' => 'payroll.statutory-body.view'],
            ['name' => 'payroll.statutory-body.create'],
            ['name' => 'payroll.statutory-body.edit'],
            ['name' => 'payroll.statutory-body.delete'],

            // Liabilities
            ['name' => 'payroll.liability.view'],
            ['name' => 'payroll.liability.create'],
            ['name' => 'payroll.liability.edit'],
            ['name' => 'payroll.liability.delete'],

            // Reports
            ['name' => 'payroll.reports.view'],
            ['name' => 'payroll.reports.export'],

            // Statutory Compliance
            ['name' => 'payroll.compliance.view'],
            ['name' => 'payroll.compliance.manage'],

            // ========== TASKS MODULE ==========
            ['name' => 'tasks.view'],
            ['name' => 'tasks.create'],
            ['name' => 'tasks.edit'],
            ['name' => 'tasks.delete'],

            // ========== PRODUCT MODULE ==========
            ['name' => 'product.dashboard.view'],

            // ========== WAREHOUSE MODULE ==========
            ['name' => 'warehouse.dashboard.view'],
            ['name' => 'warehouse.dashboard.stats'],
            ['name' => 'warehouse.dashboard.chart-data'],
            ['name' => 'warehouse.activity-logs.view'],
            ['name' => 'warehouse.barcode.view'],
            ['name' => 'warehouse.barcode.generate'],
            ['name' => 'warehouse.barcode.delete'],
            ['name' => 'warehouse.barcode.bulk-delete'],
            ['name' => 'warehouse.barcode.print'],
            ['name' => 'warehouse.barcode.print-pdf'],
            ['name' => 'warehouse.barcode.print-view'],
            ['name' => 'warehouse.barcode.scan'],
            ['name' => 'warehouse.barcode.export-pdf'],
            ['name' => 'warehouse.barcode.export-excel'],
            ['name' => 'warehouse.barcode.mark-as-used'],
            ['name' => 'warehouse.barcode.bulk-mark-as-used'],
            ['name' => 'warehouse.master-data.view'],
            ['name' => 'warehouse.master-data.category.view'],
            ['name' => 'warehouse.master-data.category.create'],
            ['name' => 'warehouse.master-data.category.edit'],
            ['name' => 'warehouse.master-data.category.delete'],
            ['name' => 'warehouse.master-data.category.bulk-delete'],
            ['name' => 'warehouse.master-data.locations.view'],
            ['name' => 'warehouse.master-data.locations.create'],
            ['name' => 'warehouse.master-data.locations.edit'],
            ['name' => 'warehouse.master-data.locations.delete'],
            ['name' => 'warehouse.master-data.locations.bulk-delete'],
            ['name' => 'warehouse.master-data.dispositions.view'],
            ['name' => 'warehouse.master-data.dispositions.create'],
            ['name' => 'warehouse.master-data.dispositions.edit'],
            ['name' => 'warehouse.master-data.dispositions.delete'],
            ['name' => 'warehouse.master-data.dispositions.bulk-delete'],
            ['name' => 'warehouse.master-data.workflow-stages.view'],
            ['name' => 'warehouse.master-data.workflow-stages.create'],
            ['name' => 'warehouse.master-data.workflow-stages.edit'],
            ['name' => 'warehouse.master-data.workflow-stages.delete'],
            ['name' => 'warehouse.master-data.workflow-stages.bulk-delete'],
            ['name' => 'warehouse.master-data.scrap-types.view'],
            ['name' => 'warehouse.master-data.scrap-types.create'],
            ['name' => 'warehouse.master-data.scrap-types.edit'],
            ['name' => 'warehouse.master-data.scrap-types.delete'],
            ['name' => 'warehouse.master-data.scrap-types.bulk-delete'],
            ['name' => 'warehouse.master-data.scrap-category.view'],
            ['name' => 'warehouse.master-data.scrap-category.create'],
            ['name' => 'warehouse.master-data.scrap-category.edit'],
            ['name' => 'warehouse.master-data.scrap-category.delete'],
            ['name' => 'warehouse.master-data.scrap-category.bulk-delete'],
            ['name' => 'warehouse.products.view'],
            ['name' => 'warehouse.products.create'],
            ['name' => 'warehouse.products.show'],
            ['name' => 'warehouse.products.edit'],
            ['name' => 'warehouse.products.delete'],
            ['name' => 'warehouse.products.history'],
            ['name' => 'warehouse.products.action'],
            ['name' => 'warehouse.products.catalog-search'],
            ['name' => 'warehouse.products.check-unique-id'],
            ['name' => 'warehouse.products.bulk-delete'],
            ['name' => 'warehouse.products.bulk-status-change'],
            ['name' => 'warehouse.products.bulk-add-part'],
            ['name' => 'warehouse.products.bulk-extract-part'],
            ['name' => 'warehouse.products.bulk-move-location'],
            ['name' => 'warehouse.products.bulk-assign-tech'],
            ['name' => 'warehouse.products.extract-part'],
            ['name' => 'warehouse.products.process-extract-part'],
            ['name' => 'warehouse.products.restore'],
            ['name' => 'warehouse.products.qrcode'],
            ['name' => 'warehouse.products.print-qrcode'],
            ['name' => 'warehouse.products.export-pdf'],
            ['name' => 'warehouse.products.export-excel'],
            ['name' => 'warehouse.product-issue.view'],
            ['name' => 'warehouse.product-issue.create'],
            ['name' => 'warehouse.product-issue.edit'],
            ['name' => 'warehouse.product-issue.delete'],
            ['name' => 'warehouse.product-issue.return'],
            ['name' => 'warehouse.product-issue.export-pdf'],
            ['name' => 'warehouse.product-issue.export-excel'],
            ['name' => 'warehouse.technicians.view'],
            ['name' => 'warehouse.technicians.delete'],
            ['name' => 'warehouse.technicians.bulk-assign'],
            ['name' => 'warehouse.technicians.bulk-delete'],
            ['name' => 'warehouse.technicians.bulk-complete'],
            ['name' => 'warehouse.technicians.start-task'],
            ['name' => 'warehouse.technicians.retry-task'],
            ['name' => 'warehouse.technicians.complete-task'],
            ['name' => 'warehouse.technicians.submit-result'],
            ['name' => 'warehouse.technicians.submit-visual'],
            ['name' => 'warehouse.technicians.submit-functional'],
            ['name' => 'warehouse.technicians.submit-general'],
            ['name' => 'warehouse.technicians.view-result'],
            ['name' => 'warehouse.technicians.export-pdf'],
            ['name' => 'warehouse.technicians.export-excel'],
            ['name' => 'warehouse.parts.view'],
            ['name' => 'warehouse.parts.create'],
            ['name' => 'warehouse.parts.show'],
            ['name' => 'warehouse.parts.edit'],
            ['name' => 'warehouse.parts.delete'],
            ['name' => 'warehouse.parts.add-to-product'],
            ['name' => 'warehouse.parts.transfer'],
            ['name' => 'warehouse.parts.movements'],
            ['name' => 'warehouse.parts.bulk-delete'],
            ['name' => 'warehouse.parts.export-pdf'],
            ['name' => 'warehouse.parts.export-excel'],
            ['name' => 'warehouse.gate-pass.view'],
            ['name' => 'warehouse.gate-pass.create'],
            ['name' => 'warehouse.gate-pass.show'],
            ['name' => 'warehouse.gate-pass.edit'],
            ['name' => 'warehouse.gate-pass.delete'],
            ['name' => 'warehouse.gate-pass.bulk-delete'],
            ['name' => 'warehouse.gate-pass.search-products'],
            ['name' => 'warehouse.gate-pass.qc-approval'],
            ['name' => 'warehouse.gate-pass.security-approval'],
            ['name' => 'warehouse.gate-pass.export-pdf'],
            ['name' => 'warehouse.gate-pass.export-excel'],
            ['name' => 'warehouse.scrap.view'],
            ['name' => 'warehouse.scrap.create'],
            ['name' => 'warehouse.scrap.show'],
            ['name' => 'warehouse.scrap.edit'],
            ['name' => 'warehouse.scrap.delete'],
            ['name' => 'warehouse.scrap.bulk-delete'],
            ['name' => 'warehouse.scrap.export-pdf'],
            ['name' => 'warehouse.scrap.export-excel'],
            ['name' => 'warehouse.scrap.export-csv'],
            ['name' => 'warehouse.progress-sheet.view'],
            ['name' => 'warehouse.progress-sheet.create'],
            ['name' => 'warehouse.progress-sheet.edit'],
            ['name' => 'warehouse.progress-sheet.delete'],
            ['name' => 'warehouse.progress-sheet.bulk-delete'],
            ['name' => 'warehouse.progress-sheet.export-pdf'],
            ['name' => 'warehouse.progress-sheet.export-csv'],
            ['name' => 'warehouse.scan.view'],
            ['name' => 'warehouse.scan.process'],
            ['name' => 'warehouse.scan.logs'],

            // ========== PROJECT MODULE ==========
            ['name' => 'project.dashboard.view'],
            ['name' => 'project.projects.view'],
            ['name' => 'project.projects.create'],
            ['name' => 'project.projects.edit'],
            ['name' => 'project.projects.delete'],
            ['name' => 'project.clients.view'],
            ['name' => 'project.clients.create'],
            ['name' => 'project.clients.edit'],
            ['name' => 'project.clients.delete'],
            ['name' => 'project.teams.view'],

            // ========== TICKET MODULE ==========
            ['name' => 'ticket.dashboard.view'],
            ['name' => 'ticket.tickets.view'],
            ['name' => 'ticket.tickets.create'],
            ['name' => 'ticket.tickets.edit'],
            ['name' => 'ticket.tickets.delete'],
            ['name' => 'ticket.tickets.assigned'],

            // ========== QMS MODULE ==========
            ['name' => 'qms.dashboard.view'],
            ['name' => 'qms.document.view'],
            ['name' => 'qms.document.create'],
            ['name' => 'qms.document.edit'],
            ['name' => 'qms.document.delete'],
            ['name' => 'qms.sop.view'],
            ['name' => 'qms.policy.view'],
            ['name' => 'qms.training.view'],
            ['name' => 'qms.capa.view'],

            // ========== MASS MAILER MODULE ==========
            ['name' => 'mailer.view'],
            ['name' => 'mailer.send'],
            ['name' => 'mailer.templates.view'],
            ['name' => 'mailer.contacts.view'],

            // ========== CHAT MODULE ==========
            ['name' => 'chat.view'],

            // ========== SYSTEM MODULE ==========
            ['name' => 'dashboard.view'],
            ['name' => 'home.dashboard.view'],
            ['name' => 'activity.logs.view'],
            ['name' => 'export.data'],
            ['name' => 'company.switch'],

            // ========== API SPECIFIC PERMISSIONS ==========
            ['name' => 'api.attendance.checkin'],
            ['name' => 'api.attendance.checkout'],
            ['name' => 'api.leave.request'],
            ['name' => 'api.leave.approve'],
            ['name' => 'api.leave.reject'],
            ['name' => 'api.leave.view_all'],
            ['name' => 'api.report.individual'],
            ['name' => 'api.report.all'],
            ['name' => 'api.announcement.view'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
