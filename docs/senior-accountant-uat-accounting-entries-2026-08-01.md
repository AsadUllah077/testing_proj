# Senior Accountant UAT Accounting Entries

Date prepared: 2026-08-01
Company used: Safe Solution Consultants
UAT reference prefix: `CODX-UAT-20260801`

This file records the accounting test entries I entered/seeded into the software as a senior accountant. Use the prefix `CODX-UAT-20260801` in list/search screens to find the documents, vouchers, opening balance batch, recurring templates, budget notes, asset number, and bank statement lines.

## How To Use These Entries

Run the seeder if you need to recreate the same UAT entries:

```bash
php artisan db:seed --class=SeniorAccountantAccountingUatSeeder
```

The seeder is idempotent for the UAT references, so running it again skips existing UAT vouchers instead of duplicating them.

Use these entries to test:

- Accounting dashboard, trial balance, general ledger, profit and loss, balance sheet, tax reports.
- Sales invoice, customer receipt, credit note, and receivable ageing.
- Bill, vendor payment run, debit note, and payable ageing.
- Conversion/opening balances.
- Recurring invoice and recurring bill templates with discount and tax.
- Budget module.
- Fixed asset register.
- Bank reconciliation statement lines.
- Manual journal entry posting.
- Tax period settlement and tax payment.

## Master Data Used

| Area              | Value                                |
| ----------------- | ------------------------------------ |
| Customer          | First company customer in database   |
| Vendor            | First company vendor in database     |
| Currency          | PKR                                  |
| Accounting period | FY 2026-2027 covering September 2026 |
| Bank              | Habib metropolitan Business account  |
| Cash              | Cash Account                         |
| AR                | Accounts Receivable                  |
| AP                | Accounts Payable                     |
| Sales             | Sales                                |
| Output tax        | Sales Tax                            |
| Input tax         | Purchase Tax Input                   |
| Expense           | Office Expenses                      |

## Entries Created By Module

| Module                      |                                               Reference |              Date |    Amount |
| --------------------------- | ------------------------------------------------------: | ----------------: | --------: |
| Conversion/opening balances | `CODX-UAT-20260801-OB` / `CODX-UAT-20260801-OB-JRN` |        2026-07-01 | 1,750,000 |
| Sales invoice               |                          `CODX-UAT-20260801-SALE-001` |        2026-09-02 |   117,000 |
| Sales invoice with discount |                          `CODX-UAT-20260801-SALE-002` |        2026-09-03 |   210,600 |
| Customer receipt full       |                         `CODX-UAT-20260801-RCPT-FULL` |        2026-09-04 |   117,000 |
| Customer receipt partial    |                         `CODX-UAT-20260801-RCPT-PART` |        2026-09-05 |   100,000 |
| Credit note                 |                           `CODX-UAT-20260801-CRN-001` |        2026-09-06 |    58,500 |
| Vendor bill                 |                          `CODX-UAT-20260801-BILL-001` |        2026-09-07 |    31,500 |
| Vendor bill with discount   |                          `CODX-UAT-20260801-BILL-002` |        2026-09-08 |   141,750 |
| Vendor payment full         |                         `CODX-UAT-20260801-VPAY-FULL` |        2026-09-09 |    31,500 |
| Vendor payment partial      |                         `CODX-UAT-20260801-VPAY-PART` |        2026-09-10 |    70,000 |
| Debit note                  |                           `CODX-UAT-20260801-DRN-001` |        2026-09-11 |    21,000 |
| Spend money                 |                        `CODX-UAT-20260801-BANK-SPEND` |        2026-09-12 |     2,500 |
| Receive money               |                      `CODX-UAT-20260801-BANK-RECEIVE` |        2026-09-13 |    12,000 |
| Bank transfer               |                     `CODX-UAT-20260801-BANK-TRANSFER` |        2026-09-14 |    50,000 |
| Salary accrual journal      |                `CODX-UAT-20260801-JRN-SALARY-ACCRUAL` |        2026-09-25 |    80,000 |
| Salary payment journal      |                    `CODX-UAT-20260801-JRN-SALARY-PAY` |        2026-09-28 |    80,000 |
| Tax settlement              |                           `TAX-SET-20260801152121-19` |        2026-09-30 |    39,100 |
| Tax payment                 |                           `TAX-PAY-20260801152121-19` |        2026-09-30 |    31,850 |
| Recurring invoice template  |          `CODX-UAT-20260801 Monthly retainer invoice` | Starts 2026-10-01 |    94,770 |
| Recurring bill template     |          `CODX-UAT-20260801 Monthly office rent bill` | Starts 2026-10-01 |   119,700 |
| Fixed asset                 |                            `CODX-UAT-20260801-FA-001` |        2026-09-15 |   450,000 |

## Accounting Posting Details

### Opening Balance Conversion

| Account                |     Debit |    Credit |
| ---------------------- | --------: | --------: |
| Bank                   | 1,200,000 |         0 |
| Accounts Receivable    |   300,000 |         0 |
| Fixed Assets Cost      |   250,000 |         0 |
| Accounts Payable       |         0 |   150,000 |
| Share Capital          |         0 | 1,000,000 |
| Opening Balance Equity |         0 |   600,000 |

Expected result: opening debit and credit both equal 1,750,000.

### Sales Invoice Normal

Reference: `CODX-UAT-20260801-SALE-001`

| Account             |   Debit |  Credit |
| ------------------- | ------: | ------: |
| Accounts Receivable | 117,000 |       0 |
| Sales               |       0 | 100,000 |
| Sales Tax           |       0 |  17,000 |

Expected result: customer owes 117,000, sales increases by 100,000, output tax liability increases by 17,000.

### Sales Invoice With Discount

Reference: `CODX-UAT-20260801-SALE-002`

| Account             |   Debit |  Credit |
| ------------------- | ------: | ------: |
| Accounts Receivable | 210,600 |       0 |
| Sales Discount      |  20,000 |       0 |
| Sales               |       0 | 200,000 |
| Sales Tax           |       0 |  30,600 |

Expected result: tax is calculated after discount. Net taxable sales are 180,000 and 17% tax is 30,600.

### Customer Receipts

References: `CODX-UAT-20260801-RCPT-FULL`, `CODX-UAT-20260801-RCPT-PART`

| Account             |   Debit |  Credit |
| ------------------- | ------: | ------: |
| Bank                | 217,000 |       0 |
| Accounts Receivable |       0 | 217,000 |

Expected result: first invoice is fully paid, second invoice remains partially outstanding after 100,000 receipt and credit note allocation.

### Credit Note

Reference: `CODX-UAT-20260801-CRN-001`

| Account             |  Debit | Credit |
| ------------------- | -----: | -----: |
| Sales               | 50,000 |      0 |
| Sales Tax           |  8,500 |      0 |
| Accounts Receivable |      0 | 58,500 |

Expected result: sales revenue and output tax are reversed, and customer receivable reduces.

### Vendor Bill Normal

Reference: `CODX-UAT-20260801-BILL-001`

| Account            |  Debit | Credit |
| ------------------ | -----: | -----: |
| Office Expenses    | 30,000 |      0 |
| Purchase Tax Input |  1,500 |      0 |
| Accounts Payable   |      0 | 31,500 |

Expected result: AP increases by supplier total, input tax recoverable increases by 1,500.

### Vendor Bill With Discount

Reference: `CODX-UAT-20260801-BILL-002`

| Account            |   Debit |  Credit |
| ------------------ | ------: | ------: |
| Office Expenses    | 150,000 |       0 |
| Purchase Tax Input |   6,750 |       0 |
| Purchase Discount  |       0 |  15,000 |
| Accounts Payable   |       0 | 141,750 |

Expected result: purchase tax is calculated after discount. Net taxable purchase is 135,000 and 5% tax is 6,750.

### Vendor Payments

References: `CODX-UAT-20260801-VPAY-FULL`, `CODX-UAT-20260801-VPAY-PART`

| Account          |   Debit |  Credit |
| ---------------- | ------: | ------: |
| Accounts Payable | 101,500 |       0 |
| Bank             |       0 | 101,500 |

Expected result: first bill is fully paid; second bill is partially paid.

### Debit Note

Reference: `CODX-UAT-20260801-DRN-001`

| Account            |  Debit | Credit |
| ------------------ | -----: | -----: |
| Accounts Payable   | 21,000 |      0 |
| Office Expenses    |      0 | 20,000 |
| Purchase Tax Input |      0 |  1,000 |

Expected result: vendor payable, expense, and input tax are reduced.

### Banking

| Reference                           |           Debit |                 Credit | Expected result                  |
| ----------------------------------- | --------------: | ---------------------: | -------------------------------- |
| `CODX-UAT-20260801-BANK-SPEND`    | Bank Fees 2,500 |             Bank 2,500 | Bank charges recorded            |
| `CODX-UAT-20260801-BANK-RECEIVE`  |     Bank 12,000 | Interest Income 12,000 | Other income received            |
| `CODX-UAT-20260801-BANK-TRANSFER` |     Cash 50,000 |            Bank 50,000 | Internal transfer, no P&L impact |

### Manual Payroll-Style Journals In Accounting

| Reference                                |                     Debit |               Credit |
| ---------------------------------------- | ------------------------: | -------------------: |
| `CODX-UAT-20260801-JRN-SALARY-ACCRUAL` | Wages and Salaries 80,000 | Wages Payable 80,000 |
| `CODX-UAT-20260801-JRN-SALARY-PAY`     |      Wages Payable 80,000 |          Bank 80,000 |

Expected result: salary expense is recognized on accrual; later payment clears liability.

### Tax Period

Tax period: `CODX-UAT-20260801 GST September 2026`
Settlement reference: `TAX-SET-20260801152121-19`
Payment reference: `TAX-PAY-20260801152121-19`

| Tax movement                            | Amount |
| --------------------------------------- | -----: |
| Output tax from sales                   | 47,600 |
| Less output tax reversed by credit note |  8,500 |
| Net output tax payable                  | 39,100 |
| Input tax from bills                    |  8,250 |
| Less input tax reversed by debit note   |  1,000 |
| Net input tax recoverable               |  7,250 |
| Net tax payable                         | 31,850 |
| Tax paid from bank                      | 31,850 |

Expected settlement entry: debit Sales Tax 39,100, credit Purchase Tax Input 7,250, credit Tax Payable Liability 31,850.
Expected payment entry: debit Tax Payable Liability 31,850, credit Bank 31,850.

## Recurring Templates

### Recurring Invoice

Title: `CODX-UAT-20260801 Monthly retainer invoice`

| Field          | Amount |
| -------------- | -----: |
| Subtotal       | 90,000 |
| Discount 10%   |  9,000 |
| After discount | 81,000 |
| GST 17%        | 13,770 |
| Total          | 94,770 |

Expected result when generated: debit AR 94,770, debit Sales Discount 9,000, credit Sales 90,000, credit Sales Tax 13,770.

### Recurring Bill

Title: `CODX-UAT-20260801 Monthly office rent bill`

| Field          |  Amount |
| -------------- | ------: |
| Subtotal       | 120,000 |
| Discount 5%    |   6,000 |
| After discount | 114,000 |
| GST 5%         |   5,700 |
| Total          | 119,700 |

Expected result when generated: debit Rent 120,000, debit Purchase Tax Input 5,700, credit Purchase Discount 6,000, credit AP 119,700.

## Budget Entries

| Account            |    Budget |
| ------------------ | --------: |
| Sales              | 1,000,000 |
| Office Expenses    |   250,000 |
| Wages and Salaries |   500,000 |

Use these to compare actual September UAT activity against budget reports.

## Fixed Asset Entry

Asset number: `CODX-UAT-20260801-FA-001`
Name: UAT Laptop Fleet
Cost: 450,000
Residual value: 50,000
Useful life: 36 months
Method: straight line
Expected monthly depreciation: 11,111.11

Expected acquisition accounting, if posted by purchase/payment flow: debit Fixed Assets Cost and credit Bank/AP. The fixed asset register entry itself stores the asset and depreciation setup.

## Bank Reconciliation Lines

| Reference                        |  Amount | Status       |
| -------------------------------- | ------: | ------------ |
| `CODX-UAT-20260801-RCPT-FULL`  | 117,000 | Reconciled   |
| `CODX-UAT-20260801-VPAY-FULL`  | -31,500 | Reconciled   |
| `CODX-UAT-20260801-BANK-SPEND` |  -2,500 | Unreconciled |

Use these to test matched receipt/payment lines and an unreconciled bank charge.

## Verification Result

I checked the UAT ledger groups after seeding:

- UAT transactions: 44 rows.
- UAT journal entries: 44 rows.
- Every UAT transaction group has debit equal to credit.
- Every UAT journal group has debit equal to credit.
- Tax period status is `paid`.
- Tax payable was 39,100.
- Tax receivable was 7,250.
- Net tax paid was 31,850.

Important existing data note: the previous non-UAT tax journal `TAX-SET-20260801144924-16` was already unbalanced before this UAT seed. The UAT entries created here are balanced.
