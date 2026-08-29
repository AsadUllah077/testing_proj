# R&D Document: Credit Notes Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Credit Notes                                              |
| **Parent Module**  | Accounting > Sales                                        |
| **Purpose**        | Issue credit adjustments to customers for returned goods, billing errors, or agreed discounts. Reduces Accounts Receivable and reverses revenue with proper double-entry journal entries. |
| **Target Users**   | Accountants, Finance Managers, Sales/Billing Teams         |
| **Permissions**    | `account.credit-note.view`, `account.credit-note.create`, `account.credit-note.record-payment`, `account.credit-note.transaction.update` |

---

## 2. Problem Statement

When goods are returned, invoices are overcharged, or post-sale discounts are granted, the original sale invoice cannot simply be edited (for audit reasons). Organizations need a formal document that:
- Reverses part or all of a sale's revenue and tax
- Reduces what the customer owes (Accounts Receivable)
- Optionally processes a cash/bank refund
- Maintains a complete audit trail of all changes
- Generates proper accounting journal entries automatically

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer          | Component                                                  |
|----------------|-------------------------------------------------------------|
| **Model**      | `CreditNote`, `CreditNoteItem`, `CreditNoteAuditLog`       |
| **Controller** | `CreditNoteController` (CRUD + payments + exports + AJAX)   |
| **Service**    | `CreditNoteService` (business logic + journal entries)      |
| **Audit**      | `CreditNoteAuditService` (change tracking)                  |
| **Request**    | `CreditNoteRequest` (payment validation)                    |
| **Export**     | `CreditNoteExport`, `CreditNoteSingleExport`, `ExportCreditNotePdf` |
| **Views**      | `index`, `create`, `edit`, `show`, `audit-logs`, `table` (Blade) |
| **PDF**        | `credit-note-pdf`, `credit-note-single-pdf`, `export-columns-credit-note` |
| **Routes**     | 17+ routes under `account.sale.credit-notes.*`              |

### 3.2 Database Schema

**Table: `credit_notes`**

| Column               | Type              | Description                                   |
|----------------------|-------------------|-----------------------------------------------|
| id                   | bigint (PK)       | Primary key                                   |
| company_id           | FK -> companies   | Multi-tenant isolation                        |
| accounting_period_id | FK -> accounting_periods | Linked accounting period               |
| customer_id          | FK -> customers   | Customer receiving the credit                 |
| account_id           | FK -> chart_of_accounts | Payment/bank account (for refunds)      |
| currency_id          | FK -> currencies  | Transaction currency                          |
| sale_id              | FK -> sales (nullable) | Original sale invoice being credited     |
| created_by           | FK -> users       | Audit: creator                                |
| updated_by           | FK -> users       | Audit: last modifier                          |
| unique_no            | string            | Auto-generated: `CRN` + YYYYMM + 4-digit seq |
| invoice_no           | string (nullable) | Custom invoice number                         |
| ref_no               | string (nullable) | External reference number                     |
| sale_unique_no       | string (nullable) | Linked sale's unique number (denormalized)    |
| date                 | date              | Credit note date                              |
| sub_total            | decimal(15,2)     | Sum before tax & discount                     |
| tax                  | decimal(15,2)     | Total tax amount                              |
| discount             | decimal(15,2)     | Total discount amount                         |
| total                | decimal(15,2)     | Net after discount, before tax                |
| total_after_discount | decimal(15,2)     | Final total (includes tax, after discount)    |
| paid_amount          | decimal(15,2)     | Amount already refunded                       |
| remaining_amount     | decimal(15,2)     | Amount still to be refunded                   |
| status               | tinyint           | 0=Draft, 1=Approved, 2=Cancelled, 3=Refunded |
| timestamps           |                   | created_at, updated_at                        |
| softDeletes          |                   | deleted_at                                    |

**Table: `credit_note_items`**

| Column              | Type              | Description                    |
|---------------------|-------------------|--------------------------------|
| id                  | bigint (PK)       | Primary key                    |
| company_id          | FK -> companies   | Multi-tenant isolation         |
| accounting_period_id| FK                | Linked period                  |
| credit_note_id      | FK                | Parent credit note             |
| account_id          | FK                | Revenue account for this line  |
| tax_id              | FK (nullable)     | Applied tax rate               |
| created_by          | FK -> users       | Audit: creator                 |
| updated_by          | FK -> users       | Audit: modifier                |
| unique_no           | string            | Credit note's unique_no        |
| sale_unique_no      | string (nullable) | Related sale's unique_no       |
| item                | string            | Item name                      |
| manual_item         | string (nullable) | Manual item entry              |
| hs_code             | string (nullable) | Harmonized System code         |
| description         | text (nullable)   | Item description               |
| quantity            | int               | Quantity                       |
| kg                  | int (nullable)    | Weight in kilograms (alt unit) |
| price               | decimal(15,2)     | Unit price                     |
| discount            | decimal(5,2)      | Discount percentage            |
| total               | decimal(15,2)     | Line total                     |
| timestamps          |                   | created_at, updated_at         |
| softDeletes         |                   | deleted_at                     |

**Table: `credit_note_audit_logs`**

| Column          | Type          | Description                                 |
|-----------------|---------------|---------------------------------------------|
| id              | bigint (PK)   | Primary key                                 |
| credit_note_id  | FK            | Parent credit note                          |
| user_id         | FK -> users   | Who made the change                         |
| action          | string        | created, updated, deleted, payment_recorded, transaction_updated |
| old_data        | JSON          | Previous state                              |
| new_data        | JSON          | New state                                   |
| changes         | JSON          | Specific field-level changes                |
| ip_address      | string        | User's IP address                           |
| user_agent      | string        | User's browser info                         |
| timestamps      |               | created_at, updated_at                      |

### 3.3 Unique Number Generation

Format: `CRN` + `YYYYMM` + 3-digit sequence

Example: `CRN202604001`, `CRN202604002`

Generated by `CreditNote::generateUniqueNo()` which finds the max existing number for the current month and increments.

### 3.4 Calculation Logic (Cent-Based Precision)

The `CreditNoteService::calculateTotals()` method uses **cent-based arithmetic** to avoid floating-point errors:

```
For each item:
  units = kg > 0 ? kg : quantity
  row_subtotal = units x price                    (in cents)
  discount_amount = row_subtotal x (discount% / 100)
  amount_after_discount = row_subtotal - discount_amount
  tax = amount_after_discount x (tax_rate / 100)
  row_total = amount_after_discount + tax

Totals:
  sub_total = sum of all row_subtotals            (converted back to dollars)
  discount  = sum of all discount_amounts
  tax_total = sum of all tax amounts
  total     = sub_total - discount                (net before tax)
  total_after_discount = total + tax_total        (final amount)
```

### 3.5 Journal Entry Logic

**Main Credit Note Journal (unique_no: CRN-YYYYMM-XXX):**

```
DR  Revenue Account(s)          [Gross amount per item, grouped by account]
DR  Sales Tax Payable           [Total tax reversed]
CR  Sales Discounts             [Total discount reversed]
CR  Accounts Receivable         [Final total_after_discount - customer credit]
```

**Explanation:**
- **DR Revenue** - Reverses the income that was originally recognized
- **DR Sales Tax Payable** - Reverses the tax liability (you no longer owe this tax)
- **CR Sales Discounts** - Reverses discount (contra-revenue adjustment)
- **CR Accounts Receivable** - Reduces what the customer owes you

**Refund Journal (unique_no: REF-XXXXXX) - Only when `paid_amount > 0`:**

```
DR  Accounts Receivable         [Refund amount]
CR  Bank/Cash Account           [Refund amount - money going out]
```

**Explanation:**
- The refund journal settles part of the AR credit created by the main journal
- Net effect: money leaves your bank, customer's credit is consumed

**Journal Balance Validation:**
The service validates that `SUM(debits) = SUM(credits)` per unique_no before saving. If unbalanced, an exception is thrown.

### 3.6 Sale Integration

When a credit note is linked to a sale:

**On Creation:**
- Sale's `remaining_amount` is **reduced** by the credit note total
- Sale's `credit_note_status` is set to `1` (has credit note)

**On Update:**
- Old sale impact is **reverted** first (remaining_amount restored)
- New sale impact is applied

**On Deletion:**
- Sale's `remaining_amount` is **restored**
- `credit_note_status` is reset to `0` if no other credit notes exist

### 3.7 Payment Recording Flow

1. User clicks remaining amount on a credit note in the list
2. Modal opens with payment form (amount, date, bank account, description)
3. Validates: `payment amount <= remaining_amount`
4. Validates accounting period is open for the payment date
5. Updates credit note: `paid_amount += amount`, `remaining_amount -= amount`
6. Creates refund journal entry (DR AR / CR Bank)
7. Creates audit log entry with payment details
8. Returns updated remaining balance

### 3.8 Status Lifecycle

```
0 (Draft) -----> 1 (Approved)     (on creation/confirmation)
1 (Approved) --> 2 (Cancelled)    (manual cancellation)
1 (Approved) --> 3 (Refunded)     (when fully paid/refunded)
```

---

## 4. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Sales Module          | Links to original sale; reduces sale remaining_amount; sets credit_note_status |
| Journal Entries       | Auto-creates balanced DR/CR transactions             |
| Accounting Periods    | Validates period is open before any transaction       |
| Chart of Accounts     | Revenue accounts (items), AR, Tax Payable, Sales Discounts, Bank/Cash |
| Tax Rates             | Per-line tax calculation                             |
| Customer Module       | Links to customer; AJAX Select2 search               |
| Currency Module       | Multi-currency support                               |
| Audit Log System      | Complete change history with IP/user-agent tracking   |
| PDF/Excel Export      | Individual and batch export with customizable columns |

---

## 5. Technical Decisions & Trade-offs

| Decision                              | Rationale                                                   |
|---------------------------------------|-------------------------------------------------------------|
| Dedicated Service layer               | Separates complex business logic from controller            |
| Cent-based arithmetic                 | Eliminates floating-point rounding errors in financial calcs |
| Separate audit log table              | Full change tracking without polluting main table           |
| Denormalized `sale_unique_no`         | Faster lookups without joins for common queries             |
| Dual quantity system (qty + kg)       | Supports both unit-based and weight-based billing           |
| Revenue grouped by account in journal | Produces cleaner, more readable journal entries             |
| Journal balance validation            | Prevents posting unbalanced entries (accounting integrity)  |
| Soft deletes                          | Preserves audit trail; meets regulatory requirements        |
| Column visibility (localStorage)      | User preference persists across sessions without DB storage |
| Queued PDF export job                 | Large batch exports don't block the request                 |

---

## 6. File Manifest

| File                                                                  | Purpose                        |
|-----------------------------------------------------------------------|--------------------------------|
| `app/Models/CreditNote.php`                                          | Main model                     |
| `app/Models/CreditNoteItem.php`                                      | Line item model                |
| `app/Models/CreditNoteAuditLog.php`                                  | Audit log model                |
| `app/Http/Controllers/Account/CreditNoteController.php`              | Controller (17+ methods)       |
| `app/Services/CreditNoteService.php`                                 | Business logic + journals      |
| `app/Services/CreditNoteAuditService.php`                            | Audit logging                  |
| `app/Http/Requests/CreditNoteRequest.php`                            | Payment validation             |
| `app/Exports/CreditNoteExport.php`                                   | Batch Excel export             |
| `app/Exports/CreditNoteSingleExport.php`                             | Single Excel export            |
| `app/Jobs/ExportCreditNotePdf.php`                                   | Queued PDF export job          |
| `database/migrations/2025_11_27_061842_create_credit_notes_table.php`| Main migration                 |
| `database/migrations/2025_11_27_063048_create_credit_note_items_table.php` | Items migration          |
| `database/migrations/2025_11_27_112131_add_credit_note_status_to_sales_table.php` | Sales integration  |
| `database/migrations/2025_11_27_115054_add_credit_note_status_to_transactions_table.php` | Txn integration |
| `database/migrations/2025_12_02_073412_add_discount_to_credit_note_items_table.php` | Discount column   |
| `database/migrations/2026_01_05_081132_add_tax_and_discount_to_credit_notes_tables.php` | Tax/discount   |
| `resources/views/pages/account/sale/credit-note/index.blade.php`     | List view                      |
| `resources/views/pages/account/sale/credit-note/create.blade.php`    | Create form                    |
| `resources/views/pages/account/sale/credit-note/edit.blade.php`      | Edit form                      |
| `resources/views/pages/account/sale/credit-note/show.blade.php`      | Detail view                    |
| `resources/views/pages/account/sale/credit-note/audit-logs.blade.php`| Audit history                  |
| `resources/views/pages/account/sale/credit-note/partials/table.blade.php` | Table partial             |
| `resources/views/pdf/credit-note-pdf.blade.php`                      | Batch PDF template             |
| `resources/views/pdf/credit-note-single-pdf.blade.php`               | Single PDF template            |
| `resources/views/pdf/export-columns-credit-note.blade.php`           | Column-based PDF               |
| `routes/account.php`                                                  | Route definitions              |

---

## 7. Testing Considerations

| Scenario                                   | Expected Behavior                                    |
|--------------------------------------------|------------------------------------------------------|
| Create credit note with all fields         | Record saved, unique_no generated, journals posted   |
| Create credit note linked to a sale        | Sale's remaining_amount reduced, credit_note_status=1|
| Create with paid_amount > 0               | Two journals: CRN (reversal) + REF (refund)         |
| Create with paid_amount = 0               | Only CRN journal posted                             |
| Edit credit note                           | Old journals deleted, new journals created           |
| Edit and change linked sale                | Old sale reverted, new sale impacted                 |
| Delete credit note                         | Soft delete; sale impact reverted; journals deleted   |
| Record payment against credit note         | paid_amount increases, remaining decreases, REF journal posted |
| Payment exceeding remaining amount         | Validation error                                     |
| Locked accounting period                   | Error thrown, no records created                     |
| Cent-based calculation accuracy            | No floating-point rounding errors                    |
| Journal balance validation                 | Debits = Credits enforced                            |
| Audit log on every action                  | action, old_data, new_data, IP tracked               |
| Export single PDF                          | Formatted PDF with company info and line items       |
| Export batch PDF/Excel                     | Queued job processes all records                      |
| Multi-currency credit note                 | Currency stored and displayed correctly              |
| Multi-tenant isolation                     | Company A cannot see Company B's credit notes        |

---

# User Guide: Credit Notes

## What Are Credit Notes?

A Credit Note (also called a "Credit Memo") is a document you issue to a customer to reduce the amount they owe you. Common reasons include:
- **Returned goods** - Customer sent items back
- **Billing error** - You overcharged on the original invoice
- **Post-sale discount** - Agreed discount after the invoice was issued
- **Damaged goods** - Items arrived damaged and you're crediting the customer

A credit note is the **opposite** of an invoice - instead of the customer owing you money, you're giving them credit.

---

## How to Access

**Navigation:** Sidebar > **Sales** > **Credit Notes**

---

## Creating a Credit Note

### Step 1: Click "New Credit Note"
Click the **"New Credit Note"** button on the Credit Notes page.

### Step 2: Fill in Header Information

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Customer**       | Search and select the customer receiving the credit   | Yes      |
| **Linked Sale**    | Original invoice being credited (loads after customer selected) | No |
| **Date**           | Credit note date                                      | Yes      |
| **Currency**       | Transaction currency                                  | Yes      |
| **Invoice No**     | Custom invoice number (auto-generated if blank)       | No       |
| **Reference No**   | External reference (e.g., customer's return request #)| No       |

### Step 3: Select a Sale (Optional but Recommended)

After selecting a customer, a **Linked Sale** dropdown appears showing that customer's invoices:
1. Select the original invoice you're crediting
2. The line items from that invoice **automatically populate** the credit note
3. You can then adjust quantities, prices, or remove items as needed

If you don't link to a sale, you can add items manually.

### Step 4: Add/Edit Line Items

Each line item has:

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Item**           | Item name                                             | Yes      |
| **Description**    | Detailed description                                  | No       |
| **HS Code**        | Harmonized System code (for international trade)      | No       |
| **Quantity**       | Number of units being credited                        | Yes      |
| **Kg**             | Weight (alternative to quantity for weight-based items)| No      |
| **Price**          | Unit price                                            | Yes      |
| **Discount %**     | Per-line discount percentage                          | No       |
| **Account**        | Revenue account to reverse                            | Yes      |
| **Tax**            | Tax rate to reverse                                   | No       |

- Click **"Add Item"** to add more lines
- Click the delete icon to remove a line (minimum 1 required)
- Totals calculate **in real-time** as you type

### Step 5: Payment Information (Optional)

| Field              | Description                                           |
|--------------------|-------------------------------------------------------|
| **Paid Amount**    | If issuing an immediate refund, enter the amount      |
| **Payment Type**   | Required if paid amount > 0                           |

- If `Paid Amount = 0`: Only a credit is created (customer can apply it to future invoices)
- If `Paid Amount > 0`: A refund journal entry is also created (money leaves your bank)

### Step 6: Save
Click **"Save"**. The system will:
- Generate a unique number (e.g., `CRN202604001`)
- Calculate all totals using cent-based precision
- Create journal entries automatically
- Update the linked sale's remaining amount (if applicable)
- Log the creation in the audit trail

---

## Viewing a Credit Note

Click on any credit note to see its **detail page**:

### Information Displayed
- **Header**: Unique number, reference, date, customer, linked sale
- **Line Items**: Full table with item, quantity, price, discount, tax, total
- **Totals**: Subtotal, discount, tax, total, paid amount, remaining amount
- **Status Badge**: Color-coded (Draft, Approved, Cancelled, Refunded)
- **Journal Entries**: The actual accounting entries that were posted

---

## Recording a Refund Payment

When you've issued a credit note but haven't refunded the customer yet:

1. Go to the Credit Notes list
2. Find the credit note with a remaining amount
3. Click the **remaining amount** value (it's a clickable link)
4. A **payment modal** opens:

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Payment Date**   | Date of the refund                                    | Yes      |
| **Amount**         | How much to refund (cannot exceed remaining)          | Yes      |
| **Account**        | Bank/Cash account the refund comes from               | Yes      |
| **Description**    | Payment description                                   | No       |

5. Click **"Record Payment"**
6. The system creates a refund journal entry and updates the credit note's paid/remaining amounts

You can record **partial refunds** - just enter less than the remaining amount. The credit note tracks the running balance.

---

## Managing Credit Notes

### Editing a Credit Note
1. Click the **Edit** button on the credit note
2. Modify any fields (customer, items, amounts, etc.)
3. Click **Save**
4. The system:
   - Reverts the old sale impact
   - Deletes old journal entries
   - Creates new journal entries
   - Applies new sale impact
   - Logs all changes in the audit trail

### Deleting a Credit Note
1. Click the **Delete** button
2. Confirm the deletion
3. The system:
   - Reverts the linked sale's remaining amount
   - Deletes all journal entries
   - Soft-deletes the credit note (preserved for audit)

### Viewing Audit Logs
1. Open the credit note detail page
2. Click **"Audit Logs"**
3. View the complete history of all changes:
   - Who made each change
   - What was changed (old vs new values)
   - When it was changed
   - From what IP address

---

## Understanding the Table Columns

| Column              | Description                                              |
|---------------------|----------------------------------------------------------|
| **Unique No**       | Auto-generated credit note number (CRN...)               |
| **Date**            | Credit note date                                         |
| **Customer**        | Customer receiving the credit                            |
| **Invoice No**      | Custom invoice number (if provided)                      |
| **Total**           | Credit note total amount                                 |
| **Paid**            | Amount already refunded                                  |
| **Remaining**       | Amount still to be refunded (clickable to record payment)|
| **Status**          | Draft, Approved, Cancelled, or Refunded                  |
| **Actions**         | View, Edit, Delete, Export PDF, Audit Logs               |

### Filtering & Search
- **Search** - Find by unique number, invoice number, or reference
- **Customer filter** - Filter by specific customer
- **Date range** - Filter by date period

---

## Exporting Credit Notes

### Single Credit Note PDF
1. Open the credit note detail page
2. Click **"Export PDF"**
3. A professionally formatted PDF downloads with company logo, customer details, line items, and totals

### Batch Export
1. On the Credit Notes list page, click **"Export"**
2. Choose **PDF** or **Excel** format
3. Optionally select which columns to include
4. The export processes in the background (for large datasets)
5. Download link appears when ready

---

## How the Accounting Works

### Example: Credit Note for $1,000 Sale

**Original Sale:** Customer bought $1,000 of services + 15% tax = $1,150 total

**Credit Note for full amount:**

| Account              | Debit    | Credit   | Explanation                    |
|----------------------|----------|----------|--------------------------------|
| Revenue              | $1,000   |          | Reverses the income            |
| Sales Tax Payable    | $150     |          | Reverses the tax liability     |
| Accounts Receivable  |          | $1,150   | Reduces what customer owes     |

**If you also issue a $1,150 refund:**

| Account              | Debit    | Credit   | Explanation                    |
|----------------------|----------|----------|--------------------------------|
| Accounts Receivable  | $1,150   |          | Clears the credit balance      |
| Bank Account         |          | $1,150   | Money leaves your bank         |

---

## Frequently Asked Questions

**Q: What's the difference between a Credit Note and deleting an invoice?**
A: You should **never delete** an invoice for audit reasons. A Credit Note is the proper accounting way to reverse a sale - it creates a formal document and audit trail.

**Q: Do I have to link a credit note to a sale?**
A: No, but it's recommended. Linking automatically reduces the sale's remaining amount and pre-fills the line items.

**Q: Can I issue a partial credit note?**
A: Yes! Just adjust the quantities or prices to credit only part of the original sale.

**Q: What happens to the customer's balance when I create a credit note?**
A: Their Accounts Receivable balance decreases. If linked to a sale, that sale's remaining amount also decreases.

**Q: Can I record a partial refund?**
A: Yes. Enter any amount up to the remaining amount. The credit note tracks the running paid/remaining balance.

**Q: What's the HS Code field for?**
A: Harmonized System codes are used in international trade for customs classification. Leave blank if not applicable.

**Q: Can I use both quantity and kg on the same item?**
A: No, they are mutually exclusive. If kg > 0, it is used as the unit; otherwise quantity is used.

**Q: What does the audit log track?**
A: Every action: creation, updates (with old vs new values), deletions, payments, and transaction updates - including who, when, and from what IP address.
