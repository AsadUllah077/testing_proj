# Complete Documentation: Accounting Module

## R&D Overview & User Guide

---

# PART 1: R&D DOCUMENTATION

## 1. Module Overview

| Item                   | Detail                                                                                                                                  |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| **Module Name**  | Accounting & Finance                                                                                                                    |
| **Purpose**      | Full double-entry accounting system with sales, purchases, banking, financial reporting, tax management, budgeting, and asset tracking. |
| **Target Users** | Accountants, Finance Managers, CFOs, Billing Teams, AP/AR Teams                                                                         |
| **Tech Stack**   | Laravel (PHP), Blade, jQuery, Select2, Chart.js, DomPDF, Maatwebsite Excel                                                              |
| **Multi-Tenant** | Yes - all data isolated per company via `BelongsToCompany` trait                                                                      |

---

## 2. Module Architecture Map

### 2.1 Sub-Modules (25+ Feature Areas)

```
ACCOUNTING MODULE
├── CORE
│   ├── Dashboard & KPI
│   ├── Chart of Accounts
│   ├── Accounting Periods
│   ├── Journal Entries
│   └── Currency Management
│
├── SALES (Revenue Cycle)
│   ├── Sales Invoices
│   ├── Credit Notes
│   ├── Recurring Invoices
│   ├── Customer Management
│   ├── Customer Statements
│   ├── Payment Reminders
│   ├── Customer Receipts (Partial Payments)
│   └── Invoice Allocation (Bulk)
│
├── PURCHASES (Expense Cycle)
│   ├── Bills (Purchase Invoices)
│   ├── Debit Notes
│   ├── Recurring Bills
│   ├── Purchase Orders
│   ├── Goods Receiving Notes (GRN)
│   ├── Vendor Management
│   ├── Vendor Statements
│   └── Vendor Payment Runs
│
├── BANKING
│   ├── Bank Reconciliation
│   ├── Statement Import
│   ├── Spend Money / Receive Money / Transfer
│   └── Transaction Matching
│
├── FINANCIAL REPORTS
│   ├── Journal Report
│   ├── General Ledger
│   ├── Trial Balance
│   ├── Balance Sheet
│   ├── Profit & Loss (Income Statement)
│   ├── Cash Flow (Indirect Method)
│   ├── Cash Flow (Direct Method)
│   ├── Aged Receivable
│   ├── Aged Payable
│   └── Audit Trail
│
├── TAX MANAGEMENT
│   ├── Tax Rates Configuration
│   ├── Tax Periods
│   ├── Tax Settlement
│   ├── Tax Payments
│   └── Tax Authority Ledger
│
├── FINANCIAL PLANNING
│   ├── Budget Management
│   ├── Budget vs Actual Report
│   ├── Cash Flow Forecast
│   ├── Fixed Assets Register
│   └── Depreciation (Automated)
│
├── APPROVAL WORKFLOWS
│   ├── Sales Invoice Approvals
│   └── Bill Approvals
│
├── LIABILITY MANAGEMENT
│   └── Liability Payments
│
└── CONFIGURATION
    ├── Invoice Templates
    ├── Organization Settings
    └── User/Role Management
```

### 2.2 Controller Inventory (41 Controllers)

| Category                | Controller                       | Purpose                          |
| ----------------------- | -------------------------------- | -------------------------------- |
| **Dashboard**     | DashboardController              | Main accounting dashboard        |
| **Sales**         | SaleController                   | Sales invoice CRUD + payments    |
|                         | CreditNoteController             | Credit notes (sales returns)     |
|                         | RecurringInvoiceController       | Automated recurring invoices     |
|                         | SaleTransactionController        | Sales transaction views          |
| **Purchases**     | BillController                   | Purchase bill CRUD + payments    |
|                         | DebitNoteController              | Debit notes (purchase returns)   |
|                         | RecurringBillController          | Automated recurring bills        |
|                         | BillTransactionController        | Bill transaction views           |
|                         | PurchaseOrderController          | Purchase order management        |
|                         | PurchaseOrderQuotationController | PO quotations + goods receiving  |
| **Banking**       | BankReconciliationController     | Bank statement reconciliation    |
| **Reports**       | JournalController                | Journal report                   |
|                         | LedgerController                 | General ledger report            |
|                         | TrialBalanceController           | Trial balance report             |
|                         | BalanceSheetController           | Balance sheet report             |
|                         | ProfitLossController             | P&L / income statement           |
|                         | CashFlowController               | Cash flow (indirect method)      |
|                         | CashFlowDirectController         | Cash flow (direct method)        |
|                         | ReportController                 | Aged payable/receivable reports  |
| **Payments**      | PartialPaymentController         | Customer receipt allocation      |
|                         | InvoiceAllocationController      | Bulk invoice allocation          |
|                         | VendorPaymentRunController       | Vendor bulk payment runs         |
|                         | PaymentReminderController        | Customer payment reminders       |
|                         | LiabilityPaymentController       | Liability payment tracking       |
| **Statements**    | CustomerStatementController      | Customer account statements      |
|                         | VendorStatementController        | Vendor account statements        |
| **Approvals**     | InvoiceApprovalController        | Invoice approval workflows       |
| **Tax**           | TaxPeriodController              | Tax period management            |
|                         | TaxPaymentController             | Tax payment recording            |
| **Finance Tools** | BudgetController                 | Budget management + reports      |
|                         | FixedAssetController             | Fixed asset register             |
|                         | CashFlowForecastController       | Cash flow forecasting            |
| **Setup**         | ChartOfAccountController         | Chart of accounts CRUD           |
|                         | AccountingPeriodController       | Period management                |
|                         | TaxRateController                | Tax rate configuration           |
|                         | CurrencyController               | Currency management              |
|                         | InvoiceTemplateController        | Invoice template designer        |
|                         | OrganizationSettingController    | Organization settings            |
| **Parties**       | CustomerController               | Customer master data             |
|                         | VendorController                 | Vendor master data               |
| **Utility**       | TransactionToInvoice             | Convert transactions to invoices |

### 2.3 Model Inventory (60+ Models)

**Core:** ChartOfAccount, Transaction, JournalEntry, AccountingPeriod, Currency, InvoiceTemplate, Company

**Sales:** Sale, SaleItem, SaleAttachments, SaleAuditLog, CreditNote, CreditNoteItem, CreditNoteAuditLog, RecurringInvoice, RecurringInvoiceItem

**Purchases:** Bill, BillItem, BillAttachment, BillAuditLog, DebitNote, DebitNoteItem, RecurringBill, RecurringBillItem, PurchaseOrder, PurchaseOrderItem, PurchaseOrderPayment, PurchaseOrderAuditLog, PurchaseOrderQuotation, PurchaseOrderQuotationItem

**Tax:** TaxRate, TaxRateLog, TaxPeriod, TaxBracket

**Payments:** CustomerPayment, CustomerPaymentItem, VendorPaymentRun, VendorPaymentRunItem

**Banking:** BankStatementLine

**Planning:** Budget, FixedAsset

**Parties:** Customer, CustomerLog, Vendor, VendorLog

**Audit:** ChartOfAccountLog, JournalEntryLog, AccountingPeriodLogs, AccountingPeriodLockUser

---

## 3. Database Design

### 3.1 Core Tables

| Table                  | Purpose                                    | Key Columns                                           |
| ---------------------- | ------------------------------------------ | ----------------------------------------------------- |
| `chart_of_accounts`  | Account master (Assets, Liabilities, etc.) | account_number, name, category, type, initial_balance |
| `transactions`       | General ledger (all debits/credits)        | account_id, debit, credit, date, type, unique_no      |
| `journal_entries`    | Manual journal headers                     | unique_no, date, description, status                  |
| `accounting_periods` | Fiscal period definitions                  | start_date, end_date, is_active, is_locked            |
| `currencies`         | Currency definitions                       | code, name, symbol, exchange_rate                     |

### 3.2 Sales Tables

| Table                       | Purpose                      |
| --------------------------- | ---------------------------- |
| `sales`                   | Sales invoice headers        |
| `sale_items`              | Sales invoice line items     |
| `sale_attachments`        | Invoice file attachments     |
| `sale_audit_logs`         | Sales change tracking        |
| `credit_notes`            | Credit note headers          |
| `credit_note_items`       | Credit note line items       |
| `credit_note_audit_logs`  | Credit note change tracking  |
| `recurring_invoices`      | Recurring invoice schedules  |
| `recurring_invoice_items` | Recurring invoice line items |

### 3.3 Purchase Tables

| Table                       | Purpose                   |
| --------------------------- | ------------------------- |
| `bills`                   | Purchase bill headers     |
| `bill_items`              | Purchase bill line items  |
| `bill_attachments`        | Bill file attachments     |
| `bill_audit_logs`         | Bill change tracking      |
| `debit_notes`             | Debit note headers        |
| `debit_note_items`        | Debit note line items     |
| `recurring_bills`         | Recurring bill schedules  |
| `recurring_bill_items`    | Recurring bill line items |
| `purchase_orders`         | Purchase order headers    |
| `purchase_order_items`    | PO line items             |
| `purchase_order_payments` | PO payment tracking       |

### 3.4 Payment & Banking Tables

| Table                        | Purpose                        |
| ---------------------------- | ------------------------------ |
| `customer_payments`        | Customer payment headers       |
| `customer_payment_items`   | Payment allocation to invoices |
| `vendor_payment_runs`      | Vendor bulk payment headers    |
| `vendor_payment_run_items` | Payment run line items         |
| `bank_statement_lines`     | Imported bank statement lines  |

### 3.5 Tax & Finance Tables

| Table            | Purpose                           |
| ---------------- | --------------------------------- |
| `tax_rates`    | Tax rate definitions              |
| `tax_periods`  | Tax settlement periods            |
| `tax_brackets` | Progressive tax slabs (FBR)       |
| `budgets`      | Budget targets per account/period |
| `fixed_assets` | Fixed asset register              |

---

## 4. Double-Entry Accounting Engine

### 4.1 Transaction Types

Every financial event creates balanced transactions in the `transactions` table:

| Event                        | Debit Account        | Credit Account           |
| ---------------------------- | -------------------- | ------------------------ |
| **Sales Invoice**      | Accounts Receivable  | Revenue + Sales Tax      |
| **Customer Payment**   | Bank/Cash            | Accounts Receivable      |
| **Credit Note**        | Revenue + Sales Tax  | Accounts Receivable      |
| **Purchase Bill**      | Expense + Input Tax  | Accounts Payable         |
| **Vendor Payment**     | Accounts Payable     | Bank/Cash                |
| **Debit Note**         | Accounts Payable     | Expense + Input Tax      |
| **Journal Entry**      | User-defined         | User-defined             |
| **Asset Depreciation** | Depreciation Expense | Accumulated Depreciation |
| **Bank Transfer**      | Destination Bank     | Source Bank              |

### 4.2 Account Categories

| Category              | Normal Balance | Examples                                     |
| --------------------- | -------------- | -------------------------------------------- |
| **Assets**      | Debit          | Bank, Cash, Receivable, Inventory, Equipment |
| **Liabilities** | Credit         | Payable, Loans, Tax Payable                  |
| **Equity**      | Credit         | Share Capital, Retained Earnings             |
| **Revenue**     | Credit         | Sales Income, Service Revenue                |
| **COGS**        | Debit          | Cost of Goods Sold, Direct Materials         |
| **Expenses**    | Debit          | Rent, Utilities, Salaries, Depreciation      |

---

## 5. Automated Processes

### 5.1 Scheduled Commands

| Command                         | Schedule    | Purpose                                    |
| ------------------------------- | ----------- | ------------------------------------------ |
| `invoices:generate-recurring` | Daily 07:00 | Generate invoices from recurring schedules |
| `bills:generate-recurring`    | Daily 07:00 | Generate bills from recurring schedules    |
| `assets:post-depreciation`    | Monthly     | Post fixed asset depreciation entries      |

### 5.2 Queued Jobs

| Job                     | Purpose                           |
| ----------------------- | --------------------------------- |
| `ExportCreditNotePdf` | Background batch PDF/Excel export |

---

## 6. Export Capabilities

| Report / Document        | PDF | CSV | Excel | Email |
| ------------------------ | --- | --- | ----- | ----- |
| Sales Invoice            | Yes | -   | Yes   | -     |
| Credit Note              | Yes | -   | Yes   | -     |
| Journal Report           | Yes | Yes | -     | -     |
| General Ledger           | Yes | Yes | -     | -     |
| Trial Balance            | Yes | Yes | -     | -     |
| Balance Sheet            | Yes | Yes | -     | -     |
| Profit & Loss            | Yes | Yes | -     | -     |
| Cash Flow (Both)         | Yes | Yes | -     | -     |
| Aged Payable/Receivable  | Yes | Yes | -     | -     |
| Customer Statement       | Yes | -   | -     | Yes   |
| Vendor Statement         | Yes | -   | -     | Yes   |
| Vendor Remittance Advice | Yes | -   | -     | -     |
| Customer Receipt         | Yes | -   | -     | -     |
| Purchase Order           | Yes | -   | -     | -     |

---

## 7. Permission Structure

| Permission Group                  | Permissions                                                                                                             |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `account.dashboard`             | view                                                                                                                    |
| `account.sale`                  | view, create, edit, delete, export, export-pdf, record-payment, transaction.update                                      |
| `account.bill-invoice`          | view, create, edit, delete, export, export-pdf, record-payment, transaction.update                                      |
| `account.purchase-order`        | view, create, edit, delete, export, export-pdf                                                                          |
| `account.journal-entries`       | view, create, edit, delete                                                                                              |
| `account.credit-note`           | view, create, record-payment, transaction.update                                                                        |
| `account.reports`               | view, journal, ledger, trial-balance, balance-sheet, profit-loss, cash-flow, aged-payable, aged-receivable, audit-trail |
| `account.bank-reconciliation`   | view, upload, report, match, create-transaction, transfer                                                               |
| `account.organization-settings` | view                                                                                                                    |
| `account.accounting-period`     | view, create, edit, delete, update-status                                                                               |
| `account.chart-of-account`      | view, create, edit, delete                                                                                              |
| `account.tax-rate`              | view, create, edit, delete                                                                                              |
| `account.invoice-setting`       | view, create, edit, delete                                                                                              |

---

## 8. File Manifest (Key Files)

### Controllers (41 files in `app/Http/Controllers/Account/`)

### Models (60+ files in `app/Models/`)

### Views (150+ files in `resources/views/pages/account/`)

### Routes: `routes/account.php`

### Services: `app/Services/Account/BankReconciliationService.php`, `app/Services/CreditNoteService.php`, `app/Services/CreditNoteAuditService.php`

### Console Commands: `app/Console/Commands/GenerateRecurringInvoices.php`, `app/Console/Commands/GenerateRecurringBills.php`, `app/Console/Commands/PostAssetDepreciation.php`

### Config: `config/modules.php`

---

---

# PART 2: USER GUIDE

---

## 1. Getting Started with Accounting

### 1.1 Initial Setup Checklist

Before you start using the accounting module, complete these setup steps in order:

| Step | What to Do                  | Where to Go                               |
| ---- | --------------------------- | ----------------------------------------- |
| 1    | Set up your company details | Organization Settings > Company           |
| 2    | Create accounting periods   | Organization Settings > Accounting Period |
| 3    | Configure Chart of Accounts | Organization Settings > Chart of Accounts |
| 4    | Set up tax rates            | Organization Settings > Tax Rates         |
| 5    | Create invoice templates    | Organization Settings > Invoice Templates |
| 6    | Set up currencies           | Organization Settings > Currency          |
| 7    | Add customers               | Sales > Customers                         |
| 8    | Add vendors                 | Bills > Vendors                           |

---

## 2. Organization Settings

### 2.1 Accounting Periods

**Navigation:** Organization Settings > Accounting Period

Accounting periods define your fiscal calendar. All transactions must fall within an open period.

**How to create a period:**

1. Click **"Create New Period"**
2. Enter period name (e.g., "January 2026", "Q1 2026")
3. Set **Start Date** and **End Date**
4. Set status to **Active**
5. Click **Save**

**Managing periods:**

- **Open/Active** - Transactions can be posted
- **Locked** - No new transactions allowed (period is closed)
- Use the status toggle to lock/unlock periods
- View audit logs to see who changed period status

**Important:** Always close old periods after month-end reconciliation to prevent accidental backdated entries.

### 2.2 Chart of Accounts

**Navigation:** Organization Settings > Chart of Accounts

The Chart of Accounts (CoA) is your complete list of financial accounts organized by category.

**Account Categories:**

| Category              | Purpose                    | Examples                             |
| --------------------- | -------------------------- | ------------------------------------ |
| **Assets**      | What you own               | Bank, Cash, Receivables, Equipment   |
| **Liabilities** | What you owe               | Payables, Loans, Tax Liabilities     |
| **Equity**      | Owner's investment         | Share Capital, Retained Earnings     |
| **Revenue**     | Income earned              | Sales, Service Income                |
| **COGS**        | Direct costs of goods sold | Materials, Direct Labor              |
| **Expenses**    | Operating costs            | Rent, Utilities, Salaries, Marketing |

**How to create an account:**

1. Click **"Create New Account"**
2. Enter **Account Number** (e.g., 1000 for Bank)
3. Enter **Account Name** (e.g., "Main Business Bank")
4. Select **Category** (Assets, Liabilities, etc.)
5. Set **Initial Balance** if needed (for opening balances)
6. Click **Save**

**Bulk import:** Use **Import** to upload accounts from a spreadsheet. Download the template first for correct format.

**Tree view:** Switch to tree view to see the account hierarchy.

### 2.3 Tax Rates

**Navigation:** Organization Settings > Tax Rates

Set up all tax rates your business uses.

**How to create a tax rate:**

1. Click **"Create"**
2. Enter **Name** (e.g., "GST 17%", "VAT 20%")
3. Enter **Rate** (percentage)
4. Click **Save**

Tax rates are applied per line item on invoices and bills.

### 2.4 Invoice Templates

**Navigation:** Organization Settings > Invoice Templates

Customize the appearance of your printed/PDF invoices.

**Features:**

- Set company logo, fonts, colors
- Configure margins and layout
- Customize which fields appear
- Create multiple templates for different purposes
- **Duplicate** an existing template to quickly create variations

### 2.5 Currency Management

**Navigation:** Organization Settings > Currency

Set up currencies for multi-currency transactions.

---

## 3. Sales Invoices

### 3.1 Creating a Sales Invoice

**Navigation:** Sidebar > **Sale Invoice** > Click **"Create New"**

| Field                  | Description                   | Required |
| ---------------------- | ----------------------------- | -------- |
| **Customer**     | Select customer (AJAX search) | Yes      |
| **Invoice No**   | Auto-generated or custom      | Yes      |
| **Date**         | Invoice date                  | Yes      |
| **Currency**     | Transaction currency          | Yes      |
| **Reference No** | External reference            | No       |

**Adding Line Items:**

1. Click **"Add Item"**
2. Enter: Item name, Description, Quantity, Unit Price, Tax Rate, Account
3. Totals calculate in real-time
4. Add as many items as needed

**After saving:** The system automatically creates journal entries:

- **DR** Accounts Receivable
- **CR** Revenue Account(s) + Sales Tax Payable

### 3.2 Recording Payments on Invoices

1. Open the invoice or click the remaining amount in the list
2. Enter: Payment Date, Amount, Bank/Cash Account
3. Click **"Record Payment"**
4. Journal entry created: **DR** Bank / **CR** Accounts Receivable

Partial payments are supported - the invoice tracks paid vs remaining amounts.

### 3.3 Invoice Import

You can bulk import invoices from spreadsheets:

1. Click **"Import"** on the Sales page
2. Download the **template** first
3. Fill in your data
4. Upload and process

### 3.4 Invoice Approval Workflow

**Navigation:** Finance > Invoice Approvals > Sales

If your organization uses approval workflows:

1. Create invoice (status: Draft)
2. **Submit** for approval
3. Approver reviews and **Approves** or **Rejects**
4. Approved invoices can be posted
5. Bulk approve/reject is available for processing multiple invoices

### 3.5 Sales Transactions View

**Navigation:** Sales > Sale Transactions

View all accounting transactions generated from sales invoices in a detailed ledger format.

---

## 4. Credit Notes (Sales Returns)

**Navigation:** Sidebar > **Finance** > **Credit Notes**

Issue credit to customers for returns, errors, or agreed discounts.

**How to create:**

1. Click **"New Credit Note"**
2. Select **Customer** then optionally select the **Linked Sale** (items auto-populate)
3. Adjust quantities/prices as needed
4. Save - journal entries posted automatically
5. Optionally record a refund payment

**Journal entries:**

- **DR** Revenue + Sales Tax / **CR** Accounts Receivable

See [RD-Credit-Notes.md](RD-Credit-Notes.md) for detailed documentation.

---

## 5. Bills (Purchase Invoices)

### 5.1 Creating a Bill

**Navigation:** Sidebar > **Bill** > Click **"Create New"**

| Field                | Description                 | Required |
| -------------------- | --------------------------- | -------- |
| **Vendor**     | Select vendor (AJAX search) | Yes      |
| **Invoice No** | Vendor's invoice number     | Yes      |
| **Date**       | Bill date                   | Yes      |
| **Currency**   | Transaction currency        | Yes      |

**After saving:** Journal entries:

- **DR** Expense Account(s) + Input Tax
- **CR** Accounts Payable

### 5.2 Recording Payments on Bills

Same flow as sales - record payments against bills to reduce AP.

### 5.3 Bill Approval Workflow

**Navigation:** Finance > Invoice Approvals > Bills

Same approval workflow as sales: Submit > Approve/Reject > Post.

---

## 6. Debit Notes (Purchase Returns)

**Navigation:** Sidebar > **Finance** > **Debit Notes**

Issue debit adjustments to vendors for returns or errors.

**How to create:**

1. Click **"New Debit Note"**
2. Select **Vendor** then optionally select **Linked Bill** (items auto-populate)
3. Adjust items as needed
4. Save - journal entries: **DR** Accounts Payable / **CR** Expense + Input Tax

See [RD-Debit-Notes.md](RD-Debit-Notes.md) for detailed documentation.

---

## 7. Purchase Orders

### 7.1 Creating a Purchase Order

**Navigation:** Sidebar > **Purchase Order** > Click **"Create"**

1. Select **Vendor**
2. Add line items with quantities, prices, accounts
3. Save the PO
4. **Export to PDF** to send to the vendor

### 7.2 Goods Receiving Notes (GRN)

When goods arrive from a vendor:

1. Open the Purchase Order
2. Click **"Create GRN"** (Goods Receiving Note)
3. Enter quantities received (may be partial)
4. Save - the system tracks received vs ordered quantities
5. Convert to a **Bill** when ready to pay

### 7.3 PO Payment Tracking

Track payments made against purchase orders with dates and references.

---

## 8. Recurring Documents

### 8.1 Recurring Invoices

**Navigation:** Finance > Recurring Invoices

Automate regular customer billing. See [RD-Recurring-Invoices.md](RD-Recurring-Invoices.md) for full details.

### 8.2 Recurring Bills

**Navigation:** Finance > Recurring Bills

Automate regular vendor bills. See [RD-Recurring-Bills.md](RD-Recurring-Bills.md) for full details.

---

## 9. Journal Entries

### 9.1 Creating a Manual Journal Entry

**Navigation:** Sidebar > **Journal Entries** > Click **"Create"**

Use journal entries for adjustments, corrections, or transactions not covered by invoices/bills.

1. Enter **Date** and **Description**
2. Add line items - each must have an **Account**, **Debit** or **Credit** amount
3. The total debits **must equal** total credits (the system enforces this)
4. Click **Save**

**Common uses:**

- Opening balances
- Period-end adjustments
- Depreciation entries (if not automated)
- Loan transactions
- Correcting errors

### 9.2 Viewing Journal Logs

Every journal entry maintains an audit log showing creation, edits, and who made changes.

---

## 10. Bank Reconciliation

### 10.1 Overview

**Navigation:** Sidebar > Select a bank account > **Bank Reconciliation**

Bank reconciliation matches your accounting records against your actual bank statements to ensure accuracy.

### 10.2 Importing Bank Statements

1. Open Bank Reconciliation for your bank account
2. Click **"Upload Statement"**
3. Upload your bank statement file (download template for format)
4. Imported lines appear in the **Statement Lines** panel

### 10.3 Matching Transactions

The reconciliation interface has two panels:

- **Left:** Bank statement lines (from your bank)
- **Right:** System transactions (from your books)

**To match:**

1. Select a statement line on the left
2. Find the matching transaction on the right
3. Click **"Match"**
4. The items are reconciled

**If no matching transaction exists:**

- Click **"Create Transaction"** to create a new accounting entry from the statement line
- Or use **"Spend Money"** / **"Receive Money"** / **"Transfer"** to record new transactions

### 10.4 Additional Banking Functions

| Function                | Purpose                                        |
| ----------------------- | ---------------------------------------------- |
| **Spend Money**   | Record a cash disbursement (check, wire, etc.) |
| **Receive Money** | Record cash received (deposit, wire in, etc.)  |
| **Transfer**      | Move money between bank/cash accounts          |
| **Create Line**   | Manually add a bank statement line             |

### 10.5 Reconciliation Report

Click **"Report"** to see a reconciliation summary showing matched, unmatched, and outstanding items.

---

## 11. Payment Processing

### 11.1 Customer Receipts (Partial Payments)

**Navigation:** Finance > Customer Receipts

Record payments from customers and allocate them to specific invoices:

1. Click **"Create Receipt"**
2. Select **Customer** and **Bank Account**
3. Enter total payment amount
4. Allocate amounts to outstanding invoices
5. Click **"Confirm"**
6. Download a receipt PDF

### 11.2 Invoice Allocation (Bulk)

**Navigation:** Finance > Invoice Processing

Bulk allocate payments across multiple invoices:

- **Sales tab:** Allocate customer payments to multiple sales invoices at once
- **Bills tab:** Allocate payments to multiple vendor bills at once

### 11.3 Payment Reminders

**Navigation:** Finance > Payment Reminders

Send automated reminders to customers with overdue invoices:

1. View overdue invoices grouped by aging buckets (30, 60, 90+ days)
2. **Send One** - Send reminder to a single customer
3. **Send Bucket** - Send reminders to all customers in an aging bucket

### 11.4 Vendor Payment Runs

**Navigation:** Finance > Vendor Payment Runs

Process bulk payments to multiple vendors:

1. Click **"Create Payment Run"**
2. Select outstanding bills to include
3. Review total payment amount
4. **Confirm** the payment run
5. Generate **Remittance Advice PDFs** for each vendor
6. Delete if needed before confirmation

---

## 12. Customer & Vendor Statements

### 12.1 Customer Statements

**Navigation:** Finance > Customer Statements

Generate account statements showing all transactions with a customer:

1. Select a customer to view their statement
2. Shows: invoices, payments, credit notes, running balance
3. **Export PDF** for records
4. **Email** statement directly to customer
5. **Bulk Email** statements to all customers at once

### 12.2 Vendor Statements

**Navigation:** Finance > Vendor Statements

Same functionality as customer statements but for vendors:

- Bills, payments, debit notes, running balance
- PDF export and email capabilities

---

## 13. Financial Reports

### 13.1 Journal Report

**Navigation:** Reports > Journal

View all journal entries for a date range. Shows every debit and credit posting with:

- Date, unique number, description
- Account, debit amount, credit amount
- Export to **PDF** or **CSV**

### 13.2 General Ledger

**Navigation:** Reports > Ledger

View transactions for specific accounts with running balances:

- Filter by account, date range
- Shows opening balance, transactions, closing balance
- Export to **PDF** or **CSV**

### 13.3 Trial Balance

**Navigation:** Reports > Trial Balance

Verify that total debits equal total credits across all accounts:

- Shows each account's debit and credit balance
- Must balance (total debits = total credits)
- Compare across multiple periods
- Export to **PDF** or **CSV**

### 13.4 Balance Sheet

**Navigation:** Reports > Balance Sheet

Your financial position at a point in time:

```
ASSETS (what you own)
  Current Assets (bank, receivables, inventory)
  Non-Current Assets (equipment, buildings)

LIABILITIES (what you owe)
  Current Liabilities (payables, short-term loans)
  Non-Current Liabilities (long-term loans)

EQUITY (owner's investment)
  Share Capital, Retained Earnings

Assets = Liabilities + Equity (must balance!)
```

- Multi-period comparison with variance columns
- Export to **PDF** or **CSV**

### 13.5 Profit & Loss (Income Statement)

**Navigation:** Reports > Profit & Loss

Your financial performance over a period:

```
REVENUE (income earned)
- COST OF GOODS SOLD (direct costs)
= GROSS PROFIT

- OPERATING EXPENSES (overhead costs)
= NET PROFIT (or LOSS)
```

- Multi-period comparison
- Export to **PDF** or **CSV**

### 13.6 Cash Flow Statement

**Navigation:** Reports > Cash Flow

Two methods available:

**Indirect Method** (standard):

- Starts with net profit, adjusts for non-cash items
- Three sections: Operating, Investing, Financing
- Shows opening → net movement → closing balance

**Direct Method**:

- Shows actual cash receipts and payments
- Same three sections, different detail level

Both support multi-period comparison and PDF/CSV export.

### 13.7 Aged Receivable

**Navigation:** Reports > Aged Receivable

Shows what customers owe, grouped by age:

- Current (not yet due)
- 1-30 days overdue
- 31-60 days overdue
- 61-90 days overdue
- 90+ days overdue

Export to PDF or CSV.

### 13.8 Aged Payable

**Navigation:** Reports > Aged Payable

Same as Aged Receivable but for what you owe vendors.

### 13.9 Audit Trail

**Navigation:** Reports > Audit Trail

Complete history of all accounting transactions, showing who posted what and when. Essential for compliance and auditing.

---

## 14. Tax Management

### 14.1 Tax Periods

**Navigation:** Finance > Tax Settlement

Manage your tax filing periods:

1. **Create** a tax period (e.g., "Q1 2026 GST")
2. View **tax collected** (from sales) vs **tax paid** (from purchases)
3. See the **net tax payable** (or refundable)
4. **Settle** the period when you file and pay

### 14.2 Tax Authority Ledger

View all tax transactions in a detailed ledger format showing every tax debit and credit.

### 14.3 Recording Tax Payments

When you pay your tax authority:

1. Open the tax period
2. Click **"Record Payment"**
3. Enter amount, date, bank account
4. Payment is recorded and reduces your tax liability

---

## 15. Financial Planning Tools

### 15.1 Budget Management

**Navigation:** Finance > Budget

Set financial targets and compare against actuals. See [RD-Budget.md](RD-Budget.md) for full details.

### 15.2 Cash Flow Forecast

**Navigation:** Finance > Cash Flow Forecast

Predict future cash position based on outstanding invoices and bills. See [RD-Cash-Flow.md](RD-Cash-Flow.md) for full details.

### 15.3 Fixed Assets

**Navigation:** Finance > Fixed Assets

Track long-term assets and automate depreciation. See [RD-Fixed-Assets.md](RD-Fixed-Assets.md) for full details.

---

## 16. Liability Management

**Navigation:** Finance > Liabilities

Track and pay non-invoice liabilities:

1. View outstanding liabilities by account
2. Record payments against liabilities
3. Track payment history

---

## 17. Tips for Effective Use

### Daily Tasks

- Create invoices for sales made
- Enter bills received from vendors
- Record customer payments received
- Record vendor payments made

### Weekly Tasks

- Review aged receivables - follow up on overdue invoices
- Send payment reminders to overdue customers
- Reconcile bank statements
- Review cash flow forecast

### Monthly Tasks

- Run trial balance to verify books are balanced
- Generate Profit & Loss statement
- Generate Balance Sheet
- Post fixed asset depreciation
- Review budget vs actual
- Close/lock the completed accounting period

### Year-End Tasks

- Generate full year financial statements
- Review all open items
- Post year-end adjustments
- Close all monthly periods
- Set up periods for the new year

---

## 18. Frequently Asked Questions

**Q: My trial balance doesn't balance - what do I do?**
A: Check for recent journal entries that may have errors. Look at the audit trail for unexpected transactions. Ensure all auto-generated entries (from invoices/bills) posted correctly.

**Q: How do I correct a posted invoice?**
A: Create a **Credit Note** to reverse the incorrect invoice, then create a new correct invoice. Never delete posted invoices.

**Q: Can I backdate a transaction?**
A: Only if the accounting period for that date is still open (unlocked). If it's locked, you need to unlock it first or post the adjustment in the current period.

**Q: How do I handle foreign currency transactions?**
A: Select the appropriate currency when creating invoices or bills. The system stores the currency with each transaction.

**Q: What's the difference between a Journal Entry and an Invoice?**
A: Invoices are for customer/vendor transactions and automatically generate journal entries. Manual journal entries are for everything else (adjustments, transfers, opening balances).

**Q: How do I set up opening balances?**
A: Create a manual journal entry dated at the start of your first accounting period. Debit asset accounts, credit liability/equity accounts with your opening balances.

**Q: Can multiple users work in the accounting module simultaneously?**
A: Yes. All data is isolated per company, and the audit trail tracks which user made each change.
