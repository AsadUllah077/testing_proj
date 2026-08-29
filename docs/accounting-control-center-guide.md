# Accounting Control Center Guide

## Access

Open **Accounting > Control Center**. The workspace contains:

1. Command Center
2. Health Center
3. Period Close
4. Document Trace
5. Adjustments
6. Bank Rules

Access uses the existing accounting permissions. Reports permission opens the control and audit screens, journal-entry permission controls adjustments, bank-reconciliation permission controls bank rules, and accounting-period status permission controls final period locking.

## 1. Command Center

Choose a start and end date and click **Apply**. Review:

- Accounting health score and exception queue
- Approved sales and purchases for the selected range
- Outstanding receivables and payables
- Unreconciled bank lines
- Open payroll runs
- Recent general-ledger postings

Open an exception before starting period close. The dashboard does not change accounting data.

## 2. Health Center

The Health Center checks the selected company only. It currently tests:

- General ledger debit equals credit
- Every posting reference balances
- Ledger accounts belong to the same company
- Sale and bill remaining balances agree with total less payments
- Approved sales and bills have ledger postings
- Draft sales and bills awaiting approval
- Unreconciled bank statement lines
- Unpaid tax periods and posted payroll runs
- Missing payroll account mappings
- Suspense-account balances
- Fixed assets awaiting depreciation
- Accounting-period coverage

Critical findings must be corrected before period locking. Warnings require accountant review but do not automatically block closing.

## 3. Document To Ledger Trace

Select the document type and enter its invoice number or unique number.

Examples:

| Document         | Type          | Reference example                           |
| ---------------- | ------------- | ------------------------------------------- |
| Customer invoice | Sale invoice  | `INV-2026-0042` or the sale unique number |
| Vendor bill      | Bill          | Vendor invoice number or bill unique number |
| Payroll          | Payroll       | `PR-000057`                               |
| Manual journal   | Journal       | `JV-00042`                                |
| Any posting      | Any reference | Exact transaction`unique_no`              |

For sales, bills, and payroll, the trace includes the original posting and related payment or liability-clearing postings when they reference the same document. Confirm that total debit equals total credit and inspect each account before correcting a source document.

## 4. Smart Bank Rules

Open **Control Center > Bank Rules** and enter:

- Rule name
- Bank account
- Payee pattern
- Match method: exact, contains, or starts with
- Direction: money in, money out, or both
- Optional minimum and maximum amount
- Suggested contra account

Lower priority numbers run first. A rule only suggests the account on an unreconciled bank line; it does not create or post a transaction.

Example rule:

| Field             | Entry                              |
| ----------------- | ---------------------------------- |
| Rule name         | Monthly office internet            |
| Bank account      | Main Current Account               |
| Payee pattern     | stormfiber                         |
| Match             | Contains                           |
| Direction         | Money out                          |
| Minimum / maximum | 5,000 / 30,000                     |
| Suggested account | Internet and Communication Expense |

Review the invoice, tax treatment, date, and amount before accepting the suggestion.

## 5. Month-End Adjustments

Open **Control Center > Adjustments**, select an open accounting period, and click **New**.

An adjustment has two states:

- **Draft:** saved for review; no ledger impact.
- **Posted:** creates matching journal and transaction rows; it cannot be deleted.

The system requires at least two lines, a debit or credit on each line (not both), equal debit and credit totals, company-owned accounts, and a date inside an open period.

### Accrued Expense Example

At 30 June, electricity of 125,000 has been used but the bill is not received:

| Account                  |   Debit |  Credit |
| ------------------------ | ------: | ------: |
| Electricity Expense      | 125,000 |       0 |
| Accrued Expenses Payable |       0 | 125,000 |

Narration: `June electricity expense accrued from meter estimate`.

When the actual vendor bill arrives, reverse or clear the accrual through a reviewed journal so expense is not recorded twice.

### Prepayment Release Example

Monthly insurance expense from an annual prepayment is 50,000:

| Account           |  Debit | Credit |
| ----------------- | -----: | -----: |
| Insurance Expense | 50,000 |      0 |
| Prepaid Insurance |      0 | 50,000 |

### Depreciation

The adjustment list displays depreciation due from the fixed-asset register. Click **Prepare** to load the asset's configured depreciation-expense and accumulated-depreciation accounts.

The system prevents:

- Posting depreciation to accounts other than the asset's configured accounts
- Reducing book value below residual value
- More than one adjustment for the same asset and accounting period
- Updating the asset register before the journal posts successfully

## 6. Period Close

Open **Control Center > Period Close** and select the period. Complete each item with a review note:

1. Bank reconciliation
2. Accounts receivable review
3. Accounts payable review
4. Payroll liabilities
5. Tax review
6. Month-end adjustments
7. Management review

The **Lock Period** button becomes available only when every checklist item is complete and the Health Center has no critical finding. Locking uses the existing accounting-period control and writes an accounting-period audit log.

After locking, ordinary posting into that period is blocked. Unlock only through the existing authorized accounting-period process.

## Production Deployment

Run migrations during the normal deployment window:

```bash
php artisan migrate --force
php artisan view:cache
```

In the current database, an older migration for `logistics_trade_documents` is marked pending even though its table already exists. The new accounting migration was therefore applied directly with:

```bash
php artisan migrate --path=database/migrations/2026_08_11_000001_create_accounting_control_tables.php --force
```

Resolve the unrelated migration-history mismatch before relying on the full migration command in a future deployment.
