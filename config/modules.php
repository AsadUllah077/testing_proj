<?php

return [
    'sale' => [
        ['name' => 'Sale Invoice', 'route' => 'account.sale-invoice.index', 'is_active' => 'account.sale-invoice.*'],
        ['name' => 'Sale Transaction', 'route' => 'account.sale.sale-transaction.index', 'is_active' => 'account.sale.sale-transaction.*'],
        ['name' => 'Credit Notes', 'route' => 'account.sale.credit-notes.index', 'is_active' => 'account.sale.credit-notes.*'],
        ['name' => 'Recurring Invoices', 'route' => 'account.recurring-invoice.index', 'is_active' => 'account.recurring-invoice.*'],
        // ['name' => 'Sale Report', 'route' => 'account.sale.report.index', 'is_active' => 'account.sale.report.*'],
        ['name' => 'Manage Customer', 'route' => 'account.sale.customers.index', 'is_active' => 'account.sale.customers.*'],
        ['name' => 'Download Sales', 'route' => 'account.sale-download.index', 'is_active' => 'account.sale-download.*'],
    ],

    'bill' => [
        ['name' => 'Bill', 'route' => 'account.bill-invoice.index', 'is_active' => 'account.bill-invoice.*'],
        ['name' => 'Bill Transaction', 'route' => 'account.bill.bill-transaction.index', 'is_active' => 'account.bill.bill-transaction.*'],
        ['name' => 'Debit Notes', 'route' => 'account.debit-note.index', 'is_active' => 'account.debit-note.*'],
        // ['name' => 'Bill Report', 'route' => 'account.bill-report.index', 'is_active' => 'account.bill-report.index'],
        ['name' => 'Manage Vendors', 'route' => 'account.bill.vendors.index', 'is_active' => 'account.bill.vendors.*'],
        ['name' => 'Download Bills', 'route' => 'account.bill-download.index', 'is_active' => 'account.bill-download.*'],
    ],
    'purchase-order' => [
        ['name' => 'Purchase Order', 'route' => 'account.purchase-order.index', 'is_active' => ['account.purchase-order.index', 'account.purchase-order.create', 'account.purchase-order.edit', 'account.purchase-order.show', 'account.purchase-order.audit-logs']],
        ['name' => 'List Of Purchase Order', 'route' => 'account.purchase-order.order.list', 'is_active' => 'account.purchase-order.order.list'],
        ['name' => 'Goods Receiving Notes', 'route' => 'account.purchase-order-quotation.good-recieve', 'is_active' => 'account.purchase-order-quotation.good-recieve'],
        ['name' => 'Reports', 'route' => 'account.purchase-order-quotation.reports', 'is_active' => 'account.purchase-order-quotation.reports'],
        ['name' => 'Manage Vendors', 'route' => 'account.bill.vendors.index', 'is_active' => 'account.bill.vendors.*'],
    ],

    'organization_settings' => [
        ['name' => 'Accounting Period', 'route' => 'account.organization-settings.accounting-period.index', 'is_active' => 'account.organization-settings.accounting-period.*'],
        ['name' => 'Chart of Account', 'route' => 'account.organization-settings.account.index', 'is_active' => 'account.organization-settings.account.*'],
        ['name' => 'Tax Rate', 'route' => 'account.organization-settings.tax_rate.index', 'is_active' => 'account.organization-settings.tax_rate.*'],
        // ['name' => 'Currency Setting', 'route' => 'account.organization-settings.currency.index', 'is_active' => 'account.organization-settings.currency.*']
    ],

    'reports' => [
        ['name' => 'Journal', 'route' => 'account.report.journal', 'is_active' => 'account.report.journal'],
        ['name' => 'Ledger', 'route' => 'account.report.ledger', 'is_active' => 'account.report.ledger'],
        ['name' => 'Trial Balance', 'route' => 'account.report.trail-balance', 'is_active' => 'account.report.trail-balance'],
        ['name' => 'Balance Sheet', 'route' => 'account.report.balance-sheet', 'is_active' => 'account.report.balance-sheet'],
        ['name' => 'Profit and loss', 'route' => 'account.report.profit-loss', 'is_active' => 'account.report.profit-loss'],
        ['name' => 'Cash Flow', 'route' => 'account.report.cash-flow', 'is_active' => 'account.report.cash-flow'],
        ['name' => 'Cash Flow (Direct)', 'route' => 'account.report.cash-flow-direct', 'is_active' => 'account.report.cash-flow-direct'],
        // ['name' => 'Aged Payable', 'route' => 'account.report.aged-payable', 'is_active' => 'account.report.aged-payable'],
        // ['name' => 'Aged Receivable', 'route' => 'account.report.aged-receivable', 'is_active' => 'account.report.aged-receivable'],
        ['name' => 'Aged Payable', 'route' => 'account.report.vendor-aged-payable', 'is_active' => 'account.report.vendor-aged-payable'],
        ['name' => 'Aged Receivable', 'route' => 'account.report.customer-aged-receivable', 'is_active' => 'account.report.customer-aged-receivable'],
    ],

    'organization' => [
        ['name' => 'Employee Type', 'route' => 'hrm.employee-type.index', 'is_active' => 'hrm.employee-type.*'],
        ['name' => 'Department', 'route' => 'hrm.department.index', 'is_active' => 'hrm.department.*'],
    ],
    'leave-management' => [
        ['name' => 'Leave Calendar', 'route' => 'hrm.calendar', 'is_active' => 'hrm.calendar'],
        ['name' => 'Leave Types', 'route' => 'hrm.leave-types.index', 'is_active' => 'hrm.leave-types.*'],
        ['name' => 'Pending Requests', 'route' => 'hrm.leave.pending', 'is_active' => 'hrm.leave.pending'],
        ['name' => 'Leave', 'route' => 'hrm.leave.index', 'is_active' => 'hrm.leave.index'],
    ],
    'recruitment' => [
        [
            'name' => 'Dashboard',
            'route' => 'hrm.recruitment.dashboard',
            'is_active' => 'hrm.recruitment.dashboard',
        ],
        [
            'name' => 'Jobs',
            'route' => 'hrm.jobs.index',
            'is_active' => 'hrm.jobs.*',
        ],
        [
            'name' => 'Candidates',
            'route' => 'hrm.candidates.index',
            'is_active' => 'hrm.candidates.*',
        ],

        [
            'name' => 'Interview Calendar',
            'route' => 'hrm.candidates.calendar.data',
            'is_active' => 'hrm.candidates.calendar.data',
        ],
    ],
    'masterData' => [
        [
            'name' => 'Category',
            'route' => 'warehouse.master-data.category.index',
            'is_active' => 'warehouse.master-data.category',
        ],
        [
            'name' => 'Locations',
            'route' => 'warehouse.master-data.locations.index',
            'is_active' => 'warehouse.master-data.locations.index',
        ],
        [
            'name' => 'Dispositions',
            'route' => 'warehouse.master-data.dispositions.index',
            'is_active' => 'warehouse.master-data.dispositions.index',
        ],
        [
            'name' => 'Workflow Stages',
            'route' => 'warehouse.master-data.workflow-stages.index',
            'is_active' => 'warehouse.master-data.workflow-stages.index',
        ],
        [
            'name' => 'Scrap Types',
            'route' => 'warehouse.master-data.scrap-types.index',
            'is_active' => 'warehouse.master-data.scrap-types.index',
        ],
        [
            'name' => 'Scrap Category',
            'route' => 'warehouse.master-data.scrap-category.index',
            'is_active' => 'warehouse.master-data.scrap-category.index',
        ],
    ],

    'logistics' => [
        'inbound' => [
            'name' => 'Inbound',
            'route' => 'logistics.manage-booking.index.inbound',
            'is_active' => 'logistics.manage-booking.index.inbound',
        ],
        'outbound' => [
            'name' => 'Outbound',
            'route' => 'logistics.manage-booking.index.outbound',
            'is_active' => 'logistics.manage-booking.index.outbound',
        ],
        'return' => [
            'name' => 'Return',
            'route' => 'logistics.manage-booking.index.return',
            'is_active' => 'logistics.manage-booking.index.return',
        ],
        'gd' => [
            'name' => 'Custom & GD',
            'route' => 'logistics.gd.index',
            'is_active' => 'logistics.gd.*',
        ],
    ],

    'manage-booking' => [
        [
            'name' => 'Booking List',
            'route' => 'logistics.manage-booking.v2.index',
            'is_active' => 'logistics.manage-booking.v2.*',
        ],
        [
            'name' => 'Documents',
            'route' => 'logistics.manage-booking.v2.documents',
            'is_active' => 'logistics.manage-booking.v2.documents',
        ],
    ],

    'lc' => [
        [
            'name' => 'Lc List',
            'route' => 'logistics.lc.index',
            'is_active' => 'logistics.lc.*',
        ],
        [
            'name' => 'Bank Contracts',
            'route' => 'logistics.lc.bank-contracts.index',
            'is_active' => 'logistics.lc.bank-contracts.*',
        ],
        [
            'name' => 'Bank Charges',
            'route' => 'logistics.lc.bank-charges.index',
            'is_active' => 'logistics.lc.bank-charges.*',
        ],
        [
            'name' => 'Payments',
            'route' => 'logistics.lc.payments.index',
            'is_active' => 'logistics.lc.payments.*',
        ],
        [
            'name' => 'Landed Cost',
            'route' => 'logistics.lc.landed-cost',
            'is_active' => 'logistics.lc.landed-cost',
        ],
    ],
    'gd' => [
        [
            'name' => 'GD List',
            'route' => 'logistics.gd.index',
            'is_active' => 'logistics.gd.*',
        ],
        [
            'name' => 'Exam',
            'route' => 'logistics.gd.exam',
            'is_active' => 'logistics.gd.exam',
        ],
        [
            'name' => 'Calculator',
            'route' => 'logistics.gd.calculator',
            'is_active' => 'logistics.gd.calculator',
        ],
    ],

    'carriers' => [
        [
            'name' => 'Carriers',
            'route' => 'logistics.carriers.v2.index',
            'is_active' => 'logistics.carriers.v2.*',
        ],
        [
            'name' => 'Rate Card',
            'route' => 'logistics.carriers.rate-card.ocean.index',
            'is_active' => 'logistics.carriers.rate-card.*',
        ],
        [
            'name' => 'SlA Contracts',
            'route' => 'logistics.carriers.sla-contract.index',
            'is_active' => 'logistics.carriers.sla-contract.*',
        ],
        [
            'name' => 'Lane Management',
            'route' => 'logistics.carriers.lane-management.index',
            'is_active' => 'logistics.carriers.lane-management.*',
        ],
    ],
];
