# New Company Manual Accounting Entries

Use this file to enter a complete senior-accountant UAT scenario into any new company in the software.

Recommended reference prefix: `UAT-NEWCO-001`

Before entering transactions, create or confirm these master records:

| Master | Required value |
|---|---|
| Customer | ABC Trading Customer |
| Vendor | XYZ Office Supplier |
| Currency | PKR or your company currency |
| Bank account | Main Business Bank |
| Cash account | Cash Account |
| Tax rate for sales | GST 17% |
| Tax rate for purchases | GST 5% |
| Accounting period | Open period covering all dates below |

## Chart Of Accounts Required

Create or map these accounts before entering data:

| Account | Type |
|---|---|
| Main Business Bank | Asset / Bank |
| Cash Account | Asset / Cash |
| Accounts Receivable | Asset |
| Purchase Tax Input | Asset |
| Fixed Assets Cost | Asset |
| Accounts Payable | Liability |
| Wages Payable | Liability |
| Sales Tax | Liability |
| Tax Payable Liability | Liability |
| Share Capital | Equity |
| Opening Balance Equity | Equity |
| Sales | Revenue |
| Interest Income | Revenue |
| Sales Discount | Revenue contra account |
| Purchase Discount | Expense contra account |
| Office Expenses | Expense |
| Rent | Expense |
| Wages and Salaries | Expense |
| Bank Fees | Expense |
| Depreciation Expense | Expense |

## 1. Opening Balance Conversion

Go to `Accounting > Conversion > Opening Balances`.

Create batch:

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-OB` |
| Source system | Manual UAT |
| Conversion date | 2026-07-01 |
| Closing date | 2026-06-30 |
| Fiscal year | 2026-2027 |

Enter lines:

| Account | Debit | Credit |
|---|---:|---:|
| Main Business Bank | 1,200,000 | 0 |
| Accounts Receivable | 300,000 | 0 |
| Fixed Assets Cost | 250,000 | 0 |
| Accounts Payable | 0 | 150,000 |
| Share Capital | 0 | 1,000,000 |
| Opening Balance Equity | 0 | 600,000 |

Total debit and credit must both be `1,750,000`.

## 2. Sales Invoice Without Discount

Go to `Accounting > Sales > Invoices`.

| Field | Value |
|---|---|
| Invoice no | `UAT-NEWCO-001-SALE-001` |
| Date | 2026-09-02 |
| Customer | ABC Trading Customer |
| Item/account | Sales |
| Subtotal | 100,000 |
| Tax | GST 17% = 17,000 |
| Total | 117,000 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Accounts Receivable | 117,000 | 0 |
| Sales | 0 | 100,000 |
| Sales Tax | 0 | 17,000 |

## 3. Sales Invoice With Discount

| Field | Value |
|---|---|
| Invoice no | `UAT-NEWCO-001-SALE-002` |
| Date | 2026-09-03 |
| Customer | ABC Trading Customer |
| Subtotal | 200,000 |
| Discount | 20,000 |
| After discount | 180,000 |
| Tax | GST 17% = 30,600 |
| Total | 210,600 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Accounts Receivable | 210,600 | 0 |
| Sales Discount | 20,000 | 0 |
| Sales | 0 | 200,000 |
| Sales Tax | 0 | 30,600 |

## 4. Customer Receipts

Go to customer payment / receive payment.

### Full Receipt

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-RCPT-FULL` |
| Date | 2026-09-04 |
| Customer | ABC Trading Customer |
| Invoice | `UAT-NEWCO-001-SALE-001` |
| Bank account | Main Business Bank |
| Amount | 117,000 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Main Business Bank | 117,000 | 0 |
| Accounts Receivable | 0 | 117,000 |

### Partial Receipt

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-RCPT-PART` |
| Date | 2026-09-05 |
| Customer | ABC Trading Customer |
| Invoice | `UAT-NEWCO-001-SALE-002` |
| Bank account | Main Business Bank |
| Amount | 100,000 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Main Business Bank | 100,000 | 0 |
| Accounts Receivable | 0 | 100,000 |

## 5. Credit Note

Go to sales credit notes.

| Field | Value |
|---|---|
| Credit note no | `UAT-NEWCO-001-CRN-001` |
| Date | 2026-09-06 |
| Customer | ABC Trading Customer |
| Linked invoice | `UAT-NEWCO-001-SALE-002` |
| Subtotal | 50,000 |
| Tax | GST 17% = 8,500 |
| Total | 58,500 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Sales | 50,000 | 0 |
| Sales Tax | 8,500 | 0 |
| Accounts Receivable | 0 | 58,500 |

## 6. Vendor Bill Without Discount

Go to `Accounting > Purchases/Bills`.

| Field | Value |
|---|---|
| Bill no | `UAT-NEWCO-001-BILL-001` |
| Date | 2026-09-07 |
| Vendor | XYZ Office Supplier |
| Expense account | Office Expenses |
| Subtotal | 30,000 |
| Tax | GST 5% = 1,500 |
| Total | 31,500 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Office Expenses | 30,000 | 0 |
| Purchase Tax Input | 1,500 | 0 |
| Accounts Payable | 0 | 31,500 |

## 7. Vendor Bill With Discount

| Field | Value |
|---|---|
| Bill no | `UAT-NEWCO-001-BILL-002` |
| Date | 2026-09-08 |
| Vendor | XYZ Office Supplier |
| Expense account | Office Expenses |
| Subtotal | 150,000 |
| Discount | 15,000 |
| After discount | 135,000 |
| Tax | GST 5% = 6,750 |
| Total | 141,750 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Office Expenses | 150,000 | 0 |
| Purchase Tax Input | 6,750 | 0 |
| Purchase Discount | 0 | 15,000 |
| Accounts Payable | 0 | 141,750 |

## 8. Vendor Payments

### Full Vendor Payment

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-VPAY-FULL` |
| Date | 2026-09-09 |
| Vendor | XYZ Office Supplier |
| Bill | `UAT-NEWCO-001-BILL-001` |
| Bank account | Main Business Bank |
| Amount | 31,500 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Accounts Payable | 31,500 | 0 |
| Main Business Bank | 0 | 31,500 |

### Partial Vendor Payment

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-VPAY-PART` |
| Date | 2026-09-10 |
| Vendor | XYZ Office Supplier |
| Bill | `UAT-NEWCO-001-BILL-002` |
| Bank account | Main Business Bank |
| Amount | 70,000 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Accounts Payable | 70,000 | 0 |
| Main Business Bank | 0 | 70,000 |

## 9. Debit Note

Go to vendor debit notes.

| Field | Value |
|---|---|
| Debit note no | `UAT-NEWCO-001-DRN-001` |
| Date | 2026-09-11 |
| Vendor | XYZ Office Supplier |
| Linked bill | `UAT-NEWCO-001-BILL-002` |
| Subtotal | 20,000 |
| Tax | GST 5% = 1,000 |
| Total | 21,000 |

Expected accounting:

| Account | Debit | Credit |
|---|---:|---:|
| Accounts Payable | 21,000 | 0 |
| Office Expenses | 0 | 20,000 |
| Purchase Tax Input | 0 | 1,000 |

## 10. Banking Entries

### Spend Money

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-BANK-SPEND` |
| Date | 2026-09-12 |
| Paid from | Main Business Bank |
| Account | Bank Fees |
| Amount | 2,500 |

Expected accounting: debit Bank Fees 2,500, credit Bank 2,500.

### Receive Money

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-BANK-RECEIVE` |
| Date | 2026-09-13 |
| Received in | Main Business Bank |
| Account | Interest Income |
| Amount | 12,000 |

Expected accounting: debit Bank 12,000, credit Interest Income 12,000.

### Bank Transfer

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-BANK-TRANSFER` |
| Date | 2026-09-14 |
| From | Main Business Bank |
| To | Cash Account |
| Amount | 50,000 |

Expected accounting: debit Cash 50,000, credit Bank 50,000.

## 11. Manual Journals

### Salary Accrual

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-JRN-SALARY-ACCRUAL` |
| Date | 2026-09-25 |

| Account | Debit | Credit |
|---|---:|---:|
| Wages and Salaries | 80,000 | 0 |
| Wages Payable | 0 | 80,000 |

### Salary Payment

| Field | Value |
|---|---|
| Reference | `UAT-NEWCO-001-JRN-SALARY-PAY` |
| Date | 2026-09-28 |

| Account | Debit | Credit |
|---|---:|---:|
| Wages Payable | 80,000 | 0 |
| Main Business Bank | 0 | 80,000 |

## 12. Tax Period Settlement

Create tax period:

| Field | Value |
|---|---|
| Name | `UAT-NEWCO-001 GST September 2026` |
| Type | Monthly |
| Start date | 2026-09-01 |
| End date | 2026-09-30 |

Expected tax result:

| Tax movement | Amount |
|---|---:|
| Output tax from sales | 47,600 |
| Less credit note output tax reversal | 8,500 |
| Net output tax payable | 39,100 |
| Input tax from bills | 8,250 |
| Less debit note input tax reversal | 1,000 |
| Net input tax recoverable | 7,250 |
| Net tax payable | 31,850 |

Expected settlement:

| Account | Debit | Credit |
|---|---:|---:|
| Sales Tax | 39,100 | 0 |
| Purchase Tax Input | 0 | 7,250 |
| Tax Payable Liability | 0 | 31,850 |

Then record tax payment:

| Field | Value |
|---|---|
| Payment date | 2026-09-30 |
| Bank account | Main Business Bank |
| Amount | 31,850 |

Expected payment:

| Account | Debit | Credit |
|---|---:|---:|
| Tax Payable Liability | 31,850 | 0 |
| Main Business Bank | 0 | 31,850 |

## 13. Recurring Invoice Template

Go to recurring invoices.

| Field | Value |
|---|---|
| Title | `UAT-NEWCO-001 Monthly retainer invoice` |
| Customer | ABC Trading Customer |
| Frequency | Monthly |
| Start date | 2026-10-01 |
| Next run date | 2026-10-01 |
| Payment terms | 30 days |

Line:

| Account | Qty | Price | Discount | Tax | Total |
|---|---:|---:|---:|---:|---:|
| Sales | 1 | 90,000 | 10% / 9,000 | 17% / 13,770 | 94,770 |

Expected generated invoice accounting: debit AR 94,770, debit Sales Discount 9,000, credit Sales 90,000, credit Sales Tax 13,770.

## 14. Recurring Bill Template

Go to recurring bills.

| Field | Value |
|---|---|
| Title | `UAT-NEWCO-001 Monthly office rent bill` |
| Vendor | XYZ Office Supplier |
| Frequency | Monthly |
| Start date | 2026-10-01 |
| Next run date | 2026-10-01 |
| Payment terms | 5 days |

Line:

| Account | Qty | Price | Discount | Tax | Total |
|---|---:|---:|---:|---:|---:|
| Rent | 1 | 120,000 | 5% / 6,000 | 5% / 5,700 | 119,700 |

Expected generated bill accounting: debit Rent 120,000, debit Purchase Tax Input 5,700, credit Purchase Discount 6,000, credit AP 119,700.

## 15. Budget Entries

Go to budgets and enter:

| Account | Budget amount |
|---|---:|
| Sales | 1,000,000 |
| Office Expenses | 250,000 |
| Wages and Salaries | 500,000 |

Use these to test budget vs actual reports.

## 16. Fixed Asset

Go to fixed assets.

| Field | Value |
|---|---|
| Asset number | `UAT-NEWCO-001-FA-001` |
| Name | UAT Laptop Fleet |
| Category | Equipment |
| Purchase date | 2026-09-15 |
| In service date | 2026-09-16 |
| Asset account | Fixed Assets Cost |
| Depreciation expense account | Depreciation Expense |
| Purchase cost | 450,000 |
| Residual value | 50,000 |
| Useful life | 36 months |
| Depreciation method | Straight line |

Expected monthly depreciation: `(450,000 - 50,000) / 36 = 11,111.11`.

## 17. Bank Reconciliation Lines

Import or create bank statement lines:

| Date | Reference | Amount | Status to test |
|---|---|---:|---|
| 2026-09-04 | `UAT-NEWCO-001-RCPT-FULL` | 117,000 | Match to customer receipt |
| 2026-09-09 | `UAT-NEWCO-001-VPAY-FULL` | -31,500 | Match to vendor payment |
| 2026-09-12 | `UAT-NEWCO-001-BANK-SPEND` | -2,500 | Leave unreconciled first, then reconcile |

## Final Checks

After entering all data, verify:

| Report/check | Expected result |
|---|---|
| Trial balance | Total debit equals total credit |
| Customer ageing | Sale 001 paid, Sale 002 partly outstanding after receipt and credit note |
| Vendor ageing | Bill 001 paid, Bill 002 partly outstanding after payment and debit note |
| Tax report | Net payable 31,850 before payment |
| Bank reconciliation | Statement lines can match receipt/payment/spend money |
| Profit and loss | Sales, discounts, expenses, bank fees, interest income, and wages appear |
| Balance sheet | Bank, AR, AP, tax payable, equity, and fixed assets appear correctly |

