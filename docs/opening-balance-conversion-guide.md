# Opening Balance Conversion Guide

This document reviews the opening-balance changes and explains how to use them for the Xero-to-ERP migration.

## What Changed

### Chart of Accounts opening balances

The Chart of Accounts create/edit form no longer relies on category-based automatic debit/credit logic.

Old behavior:

- User entered `initial_balance`.
- System guessed debit/credit side from account category.
- Assets, Expenses and COGS were treated as debit-normal.
- Liabilities, Equity and Revenue were treated as credit-normal.
- The system posted against Opening Balance Equity.
- The account `initial_balance` field was reset to zero.

New behavior:

- User enters an explicit `Opening Balance Amount`.
- User must choose `Opening Balance Side`: `Debit` or `Credit`.
- User chooses or accepts the default `Opening Date`.
- Group accounts cannot receive opening-balance transactions.
- Revenue, Expenses and COGS opening balances are rejected.
- Opening Balance Equity is resolved for the current company.
- Existing account opening-balance entries are reversed before replacement.
- The ledger transactions are the source of truth.
- `initial_balance` is still present as a legacy/input field, but it is forced back to zero and should not be used for reports.

### Dedicated conversion module

A new conversion area was added:

```text
Accounting -> Conversion -> Opening Balances
```

Direct URL:

```text
/account/conversion/opening-balances
```

Route name:

```text
account.conversion.opening-balances.index
```

Batch detail route:

```text
/account/conversion/opening-balances/{batch_id}
```

Detail route name:

```text
account.conversion.opening-balances.show
```

This module creates balanced opening trial balance batches from Xero.

### Navigation

The accounting sidebar now shows:

```text
Conversion
└── Opening Balances
```

The menu item appears only if the user has:

```text
account.opening-balance-conversion.view
```

## Files Added

### Support constants

```text
app/Support/AccountingConstants.php
```

Centralizes strings such as:

- `OpeningBalance`
- `XERO_CONVERSION`
- `CHART_OF_ACCOUNT_OPENING`
- `Draft`
- `Validated`
- `Posted`
- `Reversed`
- `debit`
- `credit`

### Models

```text
app/Models/OpeningBalanceBatch.php
app/Models/OpeningBalanceLine.php
```

Relationships:

- `OpeningBalanceBatch` has many `OpeningBalanceLine`
- `OpeningBalanceBatch` has many `Transaction`
- `OpeningBalanceLine` belongs to `OpeningBalanceBatch`
- `OpeningBalanceLine` belongs to `ChartOfAccount`
- `Transaction` belongs to `OpeningBalanceBatch`

### Services

```text
app/Services/Account/OpeningBalanceService.php
app/Services/Account/OpeningBalanceConversionService.php
app/Services/Account/OpeningBalanceImportService.php
```

`OpeningBalanceService` handles single-account opening balances from Chart of Accounts.

`OpeningBalanceConversionService` handles conversion batch validation, posting and reversal.

`OpeningBalanceImportService` handles Xero trial balance spreadsheet parsing, account matching, duplicate mapping checks and Draft batch creation.

### Controller

```text
app/Http/Controllers/Account/OpeningBalanceConversionController.php
```

Handles:

- list batches
- create batch
- save draft
- edit draft/validated batch
- validate batch
- post batch
- reverse posted batch
- download template

### Export and import

```text
app/Exports/OpeningBalanceConversionTemplateExport.php
app/Imports/OpeningBalanceConversionImport.php
```

The export downloads a simple Xero opening balance template.

The import reads the completed template and saves a Draft conversion batch.

### Views

```text
resources/views/pages/account/conversion/opening-balances/index.blade.php
resources/views/pages/account/conversion/opening-balances/form.blade.php
```

### Migrations

```text
database/migrations/2026_07_30_000001_add_opening_balance_metadata_to_transactions.php
database/migrations/2026_07_30_000002_create_opening_balance_batches_tables.php
database/migrations/2026_07_30_000003_add_company_id_to_chart_of_accounts.php
database/migrations/2026_07_30_000004_backfill_chart_of_account_company_id.php
```

## Files Changed

```text
app/Http/Controllers/Account/OrganizationSetting/ChartOfAccountController.php
app/Http/Requests/OrganizationSetting/ChartOfAccountCreateRequest.php
app/Http/Requests/OrganizationSetting/ChartOfAccountUpdateRequest.php
app/Imports/ChartOfAccountImport.php
app/Models/ChartOfAccount.php
app/Models/Transaction.php
database/seeders/PermissionSeeder.php
resources/views/components/aside.blade.php
resources/views/pages/account/organization-settings/chart-of-account/create.blade.php
resources/views/pages/account/organization-settings/chart-of-account/edit.blade.php
routes/account.php
```

## Database Setup

Run the normal migration command:

```bash
php artisan migrate
```

During development, the full migration command was blocked by an unrelated older migration:

```text
2026_05_21_213624_create_logistics_trade_documents_table
```

The error was:

```text
relation "logistics_trade_documents" already exists
```

If that still happens, fix that older migration or mark it as already run. For testing only, these new migrations were applied by path:

```bash
php artisan migrate --path=database/migrations/2026_07_30_000001_add_opening_balance_metadata_to_transactions.php
php artisan migrate --path=database/migrations/2026_07_30_000003_add_company_id_to_chart_of_accounts.php
php artisan migrate --path=database/migrations/2026_07_30_000002_create_opening_balance_batches_tables.php
php artisan migrate --path=database/migrations/2026_07_30_000004_backfill_chart_of_account_company_id.php
```

Preferred production approach is to make the full `php artisan migrate` command clean.

## Permissions Setup

The permissions were added to `PermissionSeeder`.

Run:

```bash
php artisan db:seed --class=PermissionSeeder
```

Then assign these permissions to the correct accounting/admin role:

```text
account.opening-balance-conversion.view
account.opening-balance-conversion.create
account.opening-balance-conversion.edit
account.opening-balance-conversion.validate
account.opening-balance-conversion.post
account.opening-balance-conversion.reverse
account.opening-balance-conversion.import
```

Minimum permission to see the menu:

```text
account.opening-balance-conversion.view
```

## How to Access

From UI:

```text
Accounting -> Conversion -> Opening Balances
```

Direct URL:

```text
http://your-domain/account/conversion/opening-balances
```

If using `php artisan serve`:

```text
http://127.0.0.1:8000/account/conversion/opening-balances
```

## How to Use: Xero Opening Trial Balance Batch

Use this for the main migration from Xero closing balances.

Business dates:

```text
Closing date: 2026-06-30
Opening/conversion date: 2026-07-01
Fiscal year: 2026-2027
Source system: XERO
```

Steps:

1. Open `Accounting -> Conversion -> Opening Balances`.
2. Click `New Batch`.
3. Fill header fields:
   - `Reference`: example `XERO-OB-1-20260701`
   - `Source System`: `XERO`
   - `Closing Date`: `2026-06-30`
   - `Conversion Date`: `2026-07-01`
   - `Fiscal Year`: `2026-2027`
   - `Notes`: optional
4. Add account lines.
5. Select the ERP account for each Xero balance.
6. Enter either debit or credit for each line.
7. Do not enter debit and credit on the same line.
8. Ensure `Total Debit` equals `Total Credit`.
9. Click `Save Draft`.
10. From the batch list, click `Validate`.
11. If validation passes, click `Post`.

Posting creates rows in `transactions` with:

```text
type = OpeningBalance
source_system = XERO_CONVERSION
is_opening_balance = true
opening_balance_batch_id = the batch id
unique_no = the batch reference
```

## How to Use: Import Xero Opening Balance Template

Use this when the Xero trial balance is prepared in CSV/XLSX format.

Template columns:

```text
xero_account_code
xero_account_name
erp_account_number
erp_account_name
debit
credit
description
```

Steps:

1. Open `Accounting -> Conversion -> Opening Balances`.
2. Click `Template` and prepare the spreadsheet.
3. Keep `company_id` out of the file. The ERP uses the logged-in company.
4. Fill the import panel:
   - `Reference`
   - `Source`
   - `Closing Date`
   - `Conversion Date`
   - `Fiscal Year`
   - `File`
5. Click `Import Draft`.
6. If row errors exist, the batch is not created and all errors are shown.
7. If valid, the system creates a Draft batch and opens the edit screen.
8. Review imported rows.
9. Click `Validate`.
10. Click `Post`.

To inspect a batch without editing it, click the batch reference or `View` from the list page.

Import matching rules:

- ERP account is matched by `erp_account_number` first.
- If account number is blank or not matched, ERP account is matched by exact `erp_account_name`.
- Matching is scoped to the current company.
- Duplicate ERP account mappings in the same file are rejected.
- Each row must have either debit or credit greater than zero.
- Debit and credit cannot both be entered on the same row.
- Group accounts are rejected.
- Accounts Receivable and Accounts Payable control accounts are rejected to prevent double posting with outstanding invoice/bill imports.

The import always saves as Draft. It never posts transactions immediately.

## Example: Balanced Xero Import File

Assume these ERP accounts already exist for the current company:

```text
1000 - Main Bank Account
1100 - Inventory Asset
1500 - Fixed Assets Cost
1510 - Accumulated Depreciation
2100 - Bank Loan Payable
2200 - Wages Payable
3000 - Share Capital
3200 - Retained Earnings
```

Example spreadsheet rows:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
090,Business Bank Account,1000,Main Bank Account,25000,0,Xero bank book balance at 2026-06-30
630,Inventory Asset,1100,Inventory Asset,40000,0,Opening inventory value
710,Plant and Equipment,1500,Fixed Assets Cost,120000,0,Opening fixed asset cost
711,Accumulated Depreciation,1510,Accumulated Depreciation,0,30000,Opening accumulated depreciation
810,Bank Loan,2100,Bank Loan Payable,0,50000,Opening loan balance
802,Wages Payable,2200,Wages Payable,0,5000,Unpaid payroll at conversion
300,Share Capital,3000,Share Capital,0,70000,Opening share capital
320,Retained Earnings,3200,Retained Earnings,0,30000,Brought forward retained earnings
```

Totals:

```text
Total Debit  = 185,000
Total Credit = 185,000
Difference   = 0
```

This file can be imported because it is balanced and each row maps to one ERP posting account.

## Example: Import Form Values

On `Accounting -> Conversion -> Opening Balances`, fill the import panel like this:

```text
Reference       = XERO-OB-1-20260701
Source          = XERO
Closing Date    = 2026-06-30
Conversion Date = 2026-07-01
Fiscal Year     = 2026-2027
Notes           = Xero closing trial balance import
File            = your completed CSV/XLSX file
```

Then click:

```text
Import Draft
```

Expected result:

- A Draft batch is created.
- The edit screen opens.
- You review the mapped lines.
- You click `Validate`.
- You click `Post`.

## Example: Transactions Created After Posting

When the example batch is posted, the system creates one transaction row per imported line.

Example transaction metadata:

```text
unique_no                = XERO-OB-1-20260701
type                     = OpeningBalance
source_system            = XERO_CONVERSION
is_opening_balance       = true
opening_balance_batch_id = the posted batch id
date                     = 2026-07-01
accounting_period_id     = period containing 2026-07-01
company_id               = current company
```

Example accounting effect:

```text
DR Main Bank Account              25,000
DR Inventory Asset                40,000
DR Fixed Assets Cost             120,000
CR Accumulated Depreciation        30,000
CR Bank Loan Payable               50,000
CR Wages Payable                    5,000
CR Share Capital                   70,000
CR Retained Earnings               30,000
```

This is a balanced opening journal:

```text
Debit total  = 185,000
Credit total = 185,000
```

No extra Opening Balance Equity offset is needed for a balanced batch.

## Example: Import Errors

### Both debit and credit entered

Bad row:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
090,Business Bank Account,1000,Main Bank Account,25000,1000,Wrong row
```

Result:

```text
Row 2: enter either debit or credit greater than zero, not both.
```

### No debit or credit entered

Bad row:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
090,Business Bank Account,1000,Main Bank Account,0,0,Missing balance
```

Result:

```text
Row 2: enter either debit or credit greater than zero, not both.
```

### Account does not exist

Bad row:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
090,Business Bank Account,9999,Unknown Account,25000,0,Missing ERP account
```

Result:

```text
Row 2: ERP account '9999 Unknown Account' was not found for the current company.
```

### Duplicate ERP account mapping

Bad rows:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
090,Business Bank Account,1000,Main Bank Account,25000,0,First mapping
091,Second Bank Account,1000,Main Bank Account,5000,0,Duplicate mapping
```

Result:

```text
Row 3: duplicate mapping for ERP account '1000 - Main Bank Account'. First seen on row 2.
```

### Control account blocked

Bad row:

```text
xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description
610,Accounts Receivable,1200,Accounts Receivable,45000,0,AR control total
```

Result:

```text
Row 2: 'Accounts Receivable' is a control account. Import outstanding invoices/bills separately to prevent double posting.
```

## What Should Go in the Batch

For the first stage, use this batch for the opening trial balance accounts that can be posted as general ledger balances.

Good examples:

```text
Cash and bank book balances
Fixed asset cost
Accumulated depreciation
Inventory value if inventory quantities are not being imported separately yet
Loans
Accruals
Retained earnings
Share capital
Tax payable/refund balances
Payroll liability balances
```

Contra-account examples:

```text
Accumulated Depreciation -> Credit
Allowance for Doubtful Debts -> Credit
Inventory Provision -> Credit
Asset Impairment -> Credit
```

Liability examples:

```text
Accounts Payable control balance -> normally Credit
Payroll Tax Payable -> Credit
Wages Payable -> Credit
Loans Payable -> Credit
```

Asset examples:

```text
Bank -> Debit if positive book balance
Accounts Receivable control balance -> normally Debit
Employee Advances -> Debit
Tax Refund Receivable -> Debit
```

## Important Control Account Rule

The service currently blocks `ACCOUNTS_RECEIVABLE` and `ACCOUNTS_PAYABLE` control accounts in the batch validation.

Reason:

If individual outstanding invoices/bills will also be imported, posting the control total in the opening trial balance creates double counting.

Recommended approach:

```text
Accounts Receivable:
Import outstanding customer invoices separately.
The total outstanding invoices must equal the Xero AR trial balance.

Accounts Payable:
Import outstanding supplier bills separately.
The total outstanding bills must equal the Xero AP trial balance.
```

The detailed AR/AP import flows are not fully implemented in this stage.

## How to Reverse a Posted Batch

1. Open `Accounting -> Conversion -> Opening Balances`.
2. Click the batch reference or `View` to open the detail page.
3. Click `Reverse`.

The system creates reversal transaction lines instead of deleting history.

Reversal rows use:

```text
unique_no = REV-{original batch reference}
reversal_of_transaction_id = original transaction line id
reversal_of_unique_no = original unique_no
```

After reversal, the batch status becomes:

```text
Reversed
```

## How to Use: Single Chart of Account Opening Balance

This is for small manual account setup, not the main Xero trial balance conversion.

Steps:

1. Open `Accounting -> Organization Settings -> Chart of Account`.
2. Create or edit an account.
3. Set account type to `Account`.
4. Enter `Opening Balance Amount`.
5. Choose `Opening Balance Side`:
   - `Debit`
   - `Credit`
6. Choose `Opening Date`.
7. Save.

Rules:

- `Group` accounts cannot receive opening balances.
- `Revenue`, `Expenses` and `COGS` opening balances are blocked.
- Opening date must belong to an active, unlocked accounting period for the current company.
- Credits must be entered by choosing `Credit`, not by entering a negative number.

Examples:

```text
Bank account opening debit:
Amount = 10,000
Side = Debit

Loan payable opening credit:
Amount = 50,000
Side = Credit

Accumulated depreciation:
Amount = 15,000
Side = Credit
```

## Example: Single Account Opening Balance Entries

Single-account opening balances post with an automatic offset to Opening Balance Equity.

Use this only for small setup adjustments. For the full Xero trial balance, use the balanced conversion batch instead.

### Bank account debit opening

Form entry:

```text
Account              = 1000 - Main Bank Account
Opening Date         = 2026-07-01
Opening Amount       = 25,000
Opening Balance Side = Debit
```

Expected journal:

```text
DR Main Bank Account           25,000
CR Opening Balance Equity      25,000
```

### Loan payable credit opening

Form entry:

```text
Account              = 2100 - Bank Loan Payable
Opening Date         = 2026-07-01
Opening Amount       = 50,000
Opening Balance Side = Credit
```

Expected journal:

```text
DR Opening Balance Equity      50,000
CR Bank Loan Payable           50,000
```

### Accumulated depreciation credit opening

Form entry:

```text
Account              = 1510 - Accumulated Depreciation
Opening Date         = 2026-07-01
Opening Amount       = 30,000
Opening Balance Side = Credit
```

Expected journal:

```text
DR Opening Balance Equity      30,000
CR Accumulated Depreciation    30,000
```

This is why the explicit side matters. Accumulated depreciation is usually classified under Assets, but it needs a credit opening balance.

## Example: Updating or Removing a Single Account Opening Balance

If an account already has an opening balance and you enter a new one, the system reverses the old journal and posts the new journal.

Original entry:

```text
DR Main Bank Account           25,000
CR Opening Balance Equity      25,000
```

Updated form entry:

```text
Opening Amount       = 30,000
Opening Balance Side = Debit
```

The system creates a reversal:

```text
DR Opening Balance Equity      25,000
CR Main Bank Account           25,000
```

Then posts the replacement:

```text
DR Main Bank Account           30,000
CR Opening Balance Equity      30,000
```

Net opening balance:

```text
Debit Main Bank Account = 30,000
```

To remove the opening balance:

```text
Opening Amount = 0
```

The system reverses the old opening journal and does not post a replacement.

## Opening Date and Accounting Period

The selected opening date is now used.

The system resolves the period using:

```php
AccountingPeriod::findPeriodForDate($openingDate, $companyId)
```

The transaction is rejected if:

- no period exists
- the period is locked
- the period does not belong to the current company

Default opening date:

- first active accounting period start date for the current company

For the Xero migration, this should normally be:

```text
2026-07-01
```

## Chart of Accounts Import Change

The existing Chart of Accounts import still imports accounts.

But it no longer posts opening balance transactions from imported `initial_balance`, `balance`, `opening_balance` or `ytd` values.

Reason:

The old import used the same unsafe category-based debit/credit guessing.

Use the new conversion module for Xero trial balance balances.

The import/export flow is now company-safe:

- Export only includes Chart of Accounts rows for the current logged-in company.
- Import checks duplicate account codes inside the current company only.
- Import resolves parent accounts inside the current company only.
- Imported accounts are automatically assigned to the current company through `company_id`.
- The spreadsheet template does not require a `company_id` column; company assignment comes from the logged-in user/session.

## Current Validation Rules

### Single account opening balances

Validation rejects:

- missing side when amount is entered
- amount less than or equal to zero
- group accounts
- Revenue, Expenses and COGS categories
- missing accounting period
- locked accounting period
- missing Opening Balance Equity account

### Batch opening balances

Validation rejects:

- empty batch
- line with both debit and credit
- line with neither debit nor credit
- unbalanced batch total
- non-posting account
- missing conversion period
- locked conversion period
- Accounts Receivable control account
- Accounts Payable control account

## Reporting Impact

This stage adds transaction metadata:

```text
source_system
source_reference
is_opening_balance
opening_balance_batch_id
reversal_of_transaction_id
reversal_of_unique_no
```

These fields should be used in later reporting cleanup to prevent opening balances from appearing as:

- July sales
- July expenses
- July cash inflows
- July cash outflows
- new tax activity
- new payroll activity

Report-query cleanup is not fully completed in this stage.

## Known Limitations and Follow-Up Work

The following items are not fully implemented yet:

- Interactive CSV/XLSX preview screen before saving Draft.
- Manual browser-based mapping for unmatched Xero accounts.
- Dedicated outstanding customer invoice conversion flow.
- Dedicated outstanding supplier bill conversion flow.
- Dedicated inventory quantity/value conversion flow.
- Dedicated fixed asset register conversion flow.
- Bank reconciliation opening outstanding deposits/payments flow.
- Tax and payroll operational report exclusions.
- Full automated test matrix from the original request.
- Full cleanup of old unused private opening-balance helper methods in legacy files.

## Important Code Review Notes

### Good changes now in place

- Opening side is explicit.
- Contra accounts can be posted correctly.
- Group accounts are blocked.
- Profit and Loss account opening balances are blocked.
- Opening date is actually used.
- Period resolution is company-aware.
- Locked periods are rejected.
- Opening Balance Equity lookup is company-scoped when `company_id` exists.
- Batch posting is idempotent because already-posted batches cannot post again.
- Posted history is reversed instead of directly deleted.
- Sidebar navigation is available.

### Things to watch

There are still old private helper methods named `postOpeningBalanceEntry` in legacy files. They are no longer used by the new live create/update path, but they should be removed in a cleanup PR once the file encoding/mojibake is cleaned.

Also check this older migration before production deploy:

```text
2026_05_21_213624_create_logistics_trade_documents_table
```

It blocked full migration because the table already existed.

## Manual Testing Checklist

### Setup

1. Run migrations.
2. Run `PermissionSeeder`.
3. Assign opening-balance conversion permissions to your test user.
4. Confirm the sidebar shows `Conversion -> Opening Balances`.

### Chart of Accounts

1. Create Asset account with Debit opening balance.
2. Create Liability account with Credit opening balance.
3. Create contra-asset account with Credit opening balance.
4. Try Group account with opening balance. It should fail.
5. Try Revenue account with opening balance. It should fail.
6. Try Expense account with opening balance. It should fail.
7. Try locked period opening date. It should fail.
8. Edit an existing opening balance and confirm reversal plus replacement rows are created.
9. Remove an opening balance by saving amount `0` and confirm old balance is reversed.

### Conversion batch

1. Create a Draft batch.
2. Add balanced debit/credit lines.
3. Save Draft.
4. Validate.
5. Post.
6. Confirm transactions were created with `opening_balance_batch_id`.
7. Try posting again. It should fail.
8. Reverse the batch.
9. Confirm reversal transactions were created.

### Conversion import

1. Download the template.
2. Fill the example balanced rows from this guide.
3. Import the file as Draft.
4. Confirm the edit screen opens.
5. Confirm total debit and total credit are equal.
6. Validate the batch.
7. Post the batch.
8. Confirm transactions have `source_system = XERO_CONVERSION`.
9. Try a file with duplicate ERP account mapping and confirm it fails.
10. Try a file with both debit and credit on one row and confirm it fails.

### Control accounts

1. Add Accounts Receivable account to a batch. Validation should fail.
2. Add Accounts Payable account to a batch. Validation should fail.

## Quick Commands

Lint key files:

```bash
php -l app/Http/Controllers/Account/OpeningBalanceConversionController.php
php -l app/Services/Account/OpeningBalanceService.php
php -l app/Services/Account/OpeningBalanceConversionService.php
php -l routes/account.php
```

Compile Blade:

```bash
php artisan view:cache
```

Check routes:

```bash
php artisan route:list --name=opening-balances
```

Seed permissions:

```bash
php artisan db:seed --class=PermissionSeeder
```
