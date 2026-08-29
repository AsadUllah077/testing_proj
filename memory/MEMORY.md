# ERP v4 Project Memory

## Project Overview
- Laravel ERP at `d:/xampp/htdocs/erp_v4/enterprise-erp-v4`
- Branch: `shayan-dev`
- Full accounting module: 30+ controllers, 20+ models, 9 reports

## Key Architecture
- Auth layout: `layouts.auth`
- Account routes file: `routes/account.php` (prefix: `account.`)
- Models use `BelongsToCompany` trait for multi-tenancy
- `ChartOfAccount` has static helpers: `getAccountsPayableId()`, `getAccountsReceivableId()`, `getPurchaseTaxAccountId()`, `getSalesTaxPayableId()`
- `AccountingPeriod::findPeriodForDate($date)` - find period for a given date
- Transactions use fields: `account_id`, `debit`, `credit`, `type`, `reference_id`, `unique_no`, `vendor_id`/`customer_id`

## Completed Work (Session)

### Bug Fixes (DashboardController)
1. Moved `$monthlyCOGSData` above `$monthlyNetCashFlowData` loop (was undefined)
2. Removed dead `$netChangeLast30Raw` query
3. Fixed bank account orWhere scope (moved into closure with category=Assets)
4. Removed duplicate `$daysDiff` calculation

### Automation Commands Created
- `app/Console/Commands/SendOverdueInvoiceReminders.php` — `invoices:send-overdue-reminders`
- `app/Console/Commands/AutoLockAccountingPeriods.php` — `accounting:auto-lock-periods`
- `app/Console/Commands/GenerateRecurringInvoices.php` — `invoices:generate-recurring`
- All registered in `routes/console.php` scheduler

### Recurring Invoices (Full Feature)
- Migration: `2026_03_25_000001_create_recurring_invoices_table.php` ✅ migrated
- Models: `RecurringInvoice`, `RecurringInvoiceItem`
- Controller: `RecurringInvoiceController` (index/create/store/edit/update/destroy/toggleStatus)
- Routes: `account.recurring-invoice.*` prefix
- Views: `resources/views/pages/account/recurring-invoice/` (index, create, edit, _form)

### Debit Notes (Full Feature)
- Migration: `2026_03_25_000002_create_debit_notes_table.php` ✅ migrated
- Models: `DebitNote`, `DebitNoteItem`
- Controller: `DebitNoteController` (index/create/store/show/edit/update/destroy/getVendorBills)
- Routes: `account.debit-note.*` prefix (8 routes)
- Views: `resources/views/pages/account/debit-note/` (index, create, edit, show, _form)
- Journal: DR AP, CR Expense per item, CR Input Tax per item
- Unique number format: `DRN{Ym}{seq:4}` e.g. DRN2026030001

## Patterns
- Blade forms: `_form.blade.php` shared partial, `$editing` flag, `$dn` for edit model
- JS totals: recalcRow() + recalcTotals() pattern with `.item-qty/.item-price/.item-disc/.item-tax`
- Toast notifications: Alpine.js `x-data/x-show/x-init setTimeout` bottom-right
- Route model binding used throughout

## Navigation Structure
- Main sidebar: `resources/views/components/aside.blade.php` — add entries in `@if (Request::routeIs('account.*'))` block
- Sub-tabs per page (used via `config('modules.sale')` etc.): `config/modules.php`
- Recurring Invoices: in `aside.blade.php` (after Sale Invoice) + `modules.php` `sale` array
- Debit Notes: in `aside.blade.php` (after Bill) + `modules.php` `bill` array

## Email Setup
- `OverdueInvoiceReminderMail` — `app/Mail/OverdueInvoiceReminderMail.php`
- `CustomerStatementMail` — `app/Mail/CustomerStatementMail.php`
  - Template: `resources/views/emails/customer-statement.blade.php`
  - Command: `account:send-customer-statements {--customer=} {--min-balance=0.01} {--dry-run}`
  - Scheduled: 1st of each month at 08:00 in `routes/console.php`
  - Due date derived as invoice_date + 30 days (Net-30, no due_date column on Sale)

### Budget vs Actual (Full Feature)
- Migration: `2026_03_26_000001_create_budgets_table.php` ✅ migrated
- Model: `Budget` (BelongsToCompany, unique on company+period+account)
- Controller: `BudgetController` (index/upsert/destroy/report)
- Routes: `account.budget.*` (index, upsert, destroy, report)
- Views: `resources/views/pages/account/budget/` (index with debounced AJAX save, report with progress bars)
- Nav: in `aside.blade.php` under `account.reports.view` permission

### Fixed Asset Register (Full Feature)
- Migration: `2026_03_26_000002_create_fixed_assets_table.php` ✅ migrated
- Model: `FixedAsset` — `monthlyDepreciation()`, `isFullyDepreciated()`, `generateAssetNumber()` (FA-{Ym}-{seq:4})
- Command: `PostAssetDepreciation` (`assets:post-depreciation {--month=} {--dry-run}`)
  - DR Depreciation Expense, CR Accumulated Depreciation per asset per month
  - unique_no: `DEP-{asset_number}-{Ym}`
  - Scheduled: last day of month at 02:00
- Controller: `FixedAssetController` (full CRUD + buildSchedule())
- Routes: `account.fixed-asset.*` (full CRUD)
- Views: `resources/views/pages/account/fixed-asset/` (index, create, edit, show, _form)
- Nav: in `aside.blade.php` under `account.journal-entries.view` permission

### Cash Flow Forecasting (Full Feature)
- Controller: `CashFlowForecastController` (`account.cash-flow-forecast` GET)
  - Current cash: computed from transactions (initial_balance + debits - credits on bank/cash accounts)
  - AR inflows: Sales.remaining_amount, due = date + 30 days
  - AP outflows: Bills.remaining_amount, due = date + 30 days
  - Weekly buckets, running balance
  - Horizon: 30/60/90/180 days (default 90)
- View: `resources/views/pages/account/cash-flow-forecast/index.blade.php`
  - Chart.js bar+line combo: inflows/outflows bars, running balance line
- Nav: in `aside.blade.php` under `account.reports.view` permission

## Important Notes
- `chart_of_accounts` has NO `book_balance` column — compute dynamically via transactions
- book_balance formula: `initial_balance + SUM(debit) - SUM(credit)` from transactions table

## Pending / Next Features
- (none pending from current roadmap)
