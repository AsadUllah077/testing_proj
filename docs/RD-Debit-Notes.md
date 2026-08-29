# R&D Document: Debit Notes Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Debit Notes                                               |
| **Parent Module**  | Accounting > Purchases                                    |
| **Purpose**        | Issue debit adjustments against vendors for returned goods, billing errors, or agreed credits. Reduces Accounts Payable and reverses purchase expenses with proper double-entry journal entries. |
| **Target Users**   | Accountants, Finance Managers, Accounts Payable Team       |
| **Permission**     | `account.bill-invoice.view` (menu visibility)             |

---

## 2. Problem Statement

When goods received from vendors are defective, overcharged, or returned, the original purchase bill cannot simply be deleted. Organizations need a formal document that:
- Reduces what is owed to the vendor (Accounts Payable)
- Reverses the purchase expense and input tax
- Links to the original bill for traceability
- Generates proper accounting journal entries automatically

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer          | Component                                                |
|----------------|----------------------------------------------------------|
| **Model**      | `DebitNote`, `DebitNoteItem`                             |
| **Controller** | `DebitNoteController` (CRUD + AJAX for vendor bills)     |
| **Views**      | `index`, `create`, `edit`, `show`, `_form`, `table` (Blade) |
| **Routes**     | 9 routes under `account.debit-note.*`                    |

### 3.2 Database Schema

**Table: `debit_notes`**

| Column               | Type              | Description                                   |
|----------------------|-------------------|-----------------------------------------------|
| id                   | bigint (PK)       | Primary key                                   |
| company_id           | FK -> companies   | Multi-tenant isolation                        |
| accounting_period_id | FK -> accounting_periods (nullable) | Linked accounting period      |
| vendor_id            | FK -> vendors     | Vendor receiving the debit                    |
| account_id           | FK -> chart_of_accounts (nullable) | Default expense account       |
| bill_id              | FK -> bills (nullable) | Original bill being debited              |
| created_by           | FK -> users       | Audit: creator                                |
| updated_by           | FK -> users       | Audit: last modifier                          |
| unique_no            | string            | Auto-generated: `DRN` + YYYYMM + 3-digit seq |
| ref_no               | string (nullable) | External reference number                     |
| bill_unique_no       | string (nullable) | Linked bill's unique number (denormalized)    |
| date                 | date              | Debit note date                               |
| note                 | text (nullable)   | Internal notes                                |
| sub_total            | decimal(15,2)     | Sum before discounts/tax                      |
| tax                  | decimal(15,2)     | Total tax amount                              |
| discount             | decimal(15,2)     | Total discount amount                         |
| total                | decimal(15,2)     | Final total                                   |
| status               | tinyint           | 0=Draft, 1=Confirmed (always 1 on creation)  |
| timestamps           |                   | created_at, updated_at                        |
| softDeletes          |                   | deleted_at                                    |

**Indexes:** `(company_id, status, date)`, `(vendor_id)`, `(bill_id)`

**Table: `debit_note_items`**

| Column              | Type              | Description                    |
|---------------------|-------------------|--------------------------------|
| id                  | bigint (PK)       | Primary key                    |
| debit_note_id       | FK                | Parent debit note              |
| account_id          | FK (nullable)     | Expense account for this line  |
| tax_id              | FK (nullable)     | Applied tax rate               |
| description         | string            | Line item description          |
| quantity            | decimal(15,2)     | Quantity (default: 1)          |
| price               | decimal(15,2)     | Unit price                     |
| discount_percentage | decimal(5,2)      | Per-line discount %            |
| sub_total           | decimal(15,2)     | qty x price (before discount)  |
| tax_amount          | decimal(15,2)     | Tax amount                     |
| total               | decimal(15,2)     | sub_total - discount + tax     |
| timestamps          |                   | created_at, updated_at         |

### 3.3 Unique Number Generation

Format: `DRN` + `YYYYMM` + 3-digit sequence

Example: `DRN202604001`, `DRN202604002`

Generated by `DebitNote::generateUniqueNo()` which finds the max existing number for the current month and increments.

### 3.4 Calculation Logic

```
For each item:
  sub_total = quantity x price
  discount_amount = sub_total x (discount_percentage / 100)
  amount_after_discount = sub_total - discount_amount
  tax_amount = amount_after_discount x (tax_rate / 100)
  total = amount_after_discount + tax_amount

Totals:
  subTotal = sum of all sub_totals
  taxTotal = sum of all tax_amounts
  total    = subTotal + taxTotal   (all rounded to 2 decimals)
```

### 3.5 Journal Entry Logic

**Debit Note Journal (unique_no: DRN-YYYYMM-XXX):**

```
DR  Accounts Payable            [Total - reduces what you owe the vendor]
CR  Expense Account(s)          [Per-item amount after discount - reverses cost]
CR  Input Tax Account           [Total tax - reverses input tax claimed]
```

**Detailed Breakdown:**

| Entry | Account                    | Debit        | Credit       | Purpose                        |
|-------|----------------------------|--------------|--------------|--------------------------------|
| 1     | Accounts Payable           | Total amount |              | Reduces vendor liability       |
| 2     | Expense Account (item 1)   |              | After disc.  | Reverses purchase cost         |
| 3     | Expense Account (item 2)   |              | After disc.  | Reverses purchase cost         |
| ...   | ...                        |              | ...          | One per item account           |
| N     | Input Tax (Purchase Tax)   |              | Tax total    | Reverses input tax credit      |

**Account Resolution:**
- **Accounts Payable**: `ChartOfAccount::getAccountsPayableId()` - searches for "accounts payable" or "payable" in Liabilities, or account_number like `210%`
- **Input Tax**: `ChartOfAccount::getPurchaseTaxAccountId()` - searches for "tax refund receivable" or "input tax" in Assets, or account_number like `152%`

**All transactions are created with:**
- `type` = `'DebitNote'`
- `reference_id` = debit note ID
- `unique_no` = debit note unique_no
- `status` = 1

### 3.6 Bill Integration

When creating a debit note, users can optionally link it to an existing purchase bill:

1. **Vendor Selection** triggers AJAX call to `getVendorBills()`
2. Returns all bills for that vendor with: id, unique_no, date, total, remaining_amount
3. **Bill Selection** triggers AJAX call to `getBillItems()`
4. Returns bill's line items mapped to debit note format: description, account_id, quantity, price, discount_percentage, tax_id, tax_rate
5. Line items **auto-populate** the debit note form
6. `bill_unique_no` is stored for reference

### 3.7 Status Model

Unlike Credit Notes, Debit Notes use a simpler status:

```
0 = Draft       (not currently used - all created as Confirmed)
1 = Confirmed   (default on creation)
```

Status badge colors: `emerald` for Confirmed, `gray` for Draft.

---

## 4. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Purchases/Bills Module| Links to original bill; auto-populates line items    |
| Journal Entries       | Auto-creates balanced DR AP / CR Expense + Tax entries|
| Accounting Periods    | Auto-assigns period based on debit note date         |
| Chart of Accounts     | Expense accounts (items), AP, Input Tax              |
| Tax Rates             | Per-line tax calculation                             |
| Vendor Module         | Links to vendor; AJAX Select2 search                 |
| Transaction Ledger    | Debit note transactions visible in ledger reports    |

---

## 5. Technical Decisions & Trade-offs

| Decision                              | Rationale                                                   |
|---------------------------------------|-------------------------------------------------------------|
| Controller-based logic (no Service)   | Simpler feature; doesn't need the complexity of a service layer |
| Always Confirmed (status=1)           | Draft state not needed; debit notes should always post immediately |
| Delete + recreate on update           | Simpler than diffing changes; ensures journal consistency   |
| Denormalized bill_unique_no           | Quick reference without join for display purposes           |
| No payment tracking                   | Debit notes reduce AP directly; payment is tracked on the bill side |
| No PDF export                         | Feature may be added later; show view serves as printable document |
| Soft deletes                          | Preserves audit trail                                       |
| Inline JavaScript (no separate file)  | All calculation logic is in _form.blade.php for co-location |

---

## 6. Comparison: Credit Notes vs Debit Notes

| Aspect              | Credit Notes                    | Debit Notes                     |
|---------------------|---------------------------------|---------------------------------|
| **Direction**       | Customer-facing (Sales side)    | Vendor-facing (Purchase side)   |
| **Linked To**       | Sale Invoice                    | Purchase Bill                   |
| **Affects**         | Accounts Receivable (reduces)   | Accounts Payable (reduces)      |
| **Reverses**        | Revenue + Sales Tax             | Expense + Input Tax             |
| **Number Format**   | CRN202604001                    | DRN202604001                    |
| **Has Payments**    | Yes (refund tracking)           | No (AP reduced directly)        |
| **Has Audit Logs**  | Yes (dedicated table)           | No (relies on soft deletes)     |
| **Has PDF Export**  | Yes (single + batch)            | No                              |
| **Multi-currency**  | Yes                             | No                              |
| **Service Layer**   | Yes (CreditNoteService)         | No (logic in controller)        |
| **Status Options**  | Draft, Approved, Cancelled, Refunded | Draft, Confirmed            |

---

## 7. File Manifest

| File                                                                  | Purpose                        |
|-----------------------------------------------------------------------|--------------------------------|
| `app/Models/DebitNote.php`                                            | Main model                     |
| `app/Models/DebitNoteItem.php`                                        | Line item model                |
| `app/Http/Controllers/Account/DebitNoteController.php`                | Controller (401 lines)         |
| `database/migrations/2026_03_25_000002_create_debit_notes_table.php`  | Migration (both tables)        |
| `resources/views/pages/account/debit-note/index.blade.php`           | List view with stats           |
| `resources/views/pages/account/debit-note/create.blade.php`          | Create wrapper                 |
| `resources/views/pages/account/debit-note/edit.blade.php`            | Edit wrapper                   |
| `resources/views/pages/account/debit-note/show.blade.php`            | Detail + journal entries       |
| `resources/views/pages/account/debit-note/_form.blade.php`           | Reusable form (580 lines)      |
| `resources/views/pages/account/debit-note/table.blade.php`           | Table partial                  |
| `routes/account.php` (lines 295-309)                                  | Route definitions              |
| `config/modules.php`                                                  | Module registration            |

---

## 8. Testing Considerations

| Scenario                                   | Expected Behavior                                    |
|--------------------------------------------|------------------------------------------------------|
| Create debit note with all fields          | Record saved, unique_no generated, journals posted   |
| Create debit note linked to a bill         | Bill items auto-populate, bill_unique_no stored      |
| Create standalone debit note (no bill)     | Works normally without bill linkage                  |
| Journal entry balance                      | DR (AP) = CR (Expenses) + CR (Input Tax)             |
| Edit debit note                            | Old journals deleted, new journals recreated         |
| Delete debit note                          | Soft delete; journals deleted                        |
| Create with discount on items              | Expense credited at after-discount amount            |
| Create with tax on items                   | Input tax account credited separately                |
| Vendor AJAX search                         | Returns matching vendors with pagination             |
| Vendor bill AJAX                           | Returns vendor's bills after selection               |
| Bill items AJAX                            | Returns bill's line items for auto-fill              |
| Multi-tenant isolation                     | Company A cannot see Company B's debit notes         |
| Minimum 1 line item                        | Validation prevents empty debit notes                |
| Accounting period assignment               | Auto-finds period matching debit note date           |

---

# User Guide: Debit Notes

## What Are Debit Notes?

A Debit Note is a document you issue to a **vendor (supplier)** to reduce the amount you owe them. It's the purchase-side equivalent of a Credit Note. Common reasons include:
- **Returned goods** - You sent defective items back to the vendor
- **Billing error** - The vendor overcharged you
- **Short delivery** - You received fewer items than invoiced
- **Quality issue** - Goods didn't meet agreed specifications
- **Agreed credit** - Vendor gave you a post-purchase discount

A debit note **reduces your Accounts Payable** - meaning you owe the vendor less money.

---

## How to Access

**Navigation:** Sidebar > **Finance** > **Debit Notes**

---

## Creating a Debit Note

### Step 1: Click "New Debit Note"
Click the **"New Debit Note"** button on the Debit Notes page.

### Step 2: Select Vendor and Bill

**Left Panel (Items):**

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Vendor**         | Search and select the vendor                          | Yes      |
| **Linked Bill**    | Original purchase bill (loads after vendor selected)  | No       |

After selecting a vendor, the **Bill dropdown** populates with that vendor's bills showing:
- Bill number
- Date
- Total amount
- Remaining amount

**If you select a bill:** The line items from that bill **automatically fill** the debit note form. You can then adjust quantities, prices, or remove items.

**If you don't select a bill:** You can add items manually (for standalone debit notes).

### Step 3: Fill in Details

**Right Panel (Metadata):**

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Date**           | Debit note date                                       | Yes      |
| **Expense Account**| Default expense account for line items                | Yes      |
| **Reference No**   | External reference (e.g., vendor's credit memo #)     | No       |
| **Notes**          | Internal notes about why this debit note was issued   | No       |

### Step 4: Add/Edit Line Items

Each line item has:

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Description**    | What the item is                                      | Yes      |
| **Account**        | Expense account to reverse                            | Yes      |
| **Quantity**       | Number of units being returned/credited               | Yes      |
| **Unit Price**     | Price per unit                                        | Yes      |
| **Discount %**     | Per-line discount percentage                          | No       |
| **Tax**            | Tax rate to reverse                                   | No       |

- Click **"Add Item"** to add more lines
- Click the delete icon to remove a line (minimum 1 required)
- Totals calculate **in real-time** as you type

### Step 5: Review the Journal Entry Preview

The form shows a **journal entry preview** on the right side so you can verify the accounting entries before saving.

### Step 6: Save
Click **"Submit"**. The system will:
- Generate a unique number (e.g., `DRN202604001`)
- Calculate all totals
- Create journal entries automatically
- Set status to **Confirmed**
- Redirect to the list page

---

## Viewing a Debit Note

Click on any debit note to see its **detail page**:

### Information Displayed
- **Header**: Unique number, reference, date, vendor, linked bill
- **Line Items**: Full table with description, account, quantity, price, discount, tax, total
- **Totals**: Subtotal, tax, total
- **Journal Entries**: The actual accounting entries posted
- **Sidebar**: Status, date, accounting period, notes, created by, AP impact notification

---

## Managing Debit Notes

### Editing a Debit Note
1. Click the **Edit** button on the debit note detail page
2. Modify vendor, bill, items, or any other fields
3. Click **Save**
4. The system:
   - Deletes all old journal entries and items
   - Recalculates everything from scratch
   - Creates new journal entries
   - Updates accounting period if date changed

### Deleting a Debit Note
1. Click the **Delete** button
2. Confirm the deletion
3. The system:
   - Deletes all journal entries
   - Deletes all line items
   - Soft-deletes the debit note (preserved for audit)

---

## Understanding the Table Columns

| Column              | Description                                              |
|---------------------|----------------------------------------------------------|
| **Ref/No**          | Auto-generated debit note number (DRN...) + reference    |
| **Vendor**          | Vendor name                                              |
| **Linked Bill**     | Original bill number (if linked)                         |
| **Date**            | Debit note date                                          |
| **Sub-Total**       | Total before tax                                         |
| **Tax**             | Tax amount                                               |
| **Total**           | Final total                                              |
| **Actions**         | View, Edit, Delete                                       |

### Filtering & Search
- **Search** - Find by debit note number, reference, or vendor name
- **Vendor filter** - Filter by specific vendor
- **Date range** - Filter from/to dates

### Dashboard Statistics (Top Cards)

| Card                 | Description                                   |
|----------------------|-----------------------------------------------|
| **Total Debit Notes**| Total number of debit notes in the system     |
| **This Month**       | Number created in the current month           |
| **Total Value**      | Combined total of all debit notes             |

---

## How the Accounting Works

### Example: Debit Note for Returned Goods

**Original Bill:** You purchased $5,000 of supplies from Vendor ABC + 10% tax = $5,500 total

**Debit Note for partial return ($2,000 worth):**

| Account              | Debit    | Credit   | Explanation                    |
|----------------------|----------|----------|--------------------------------|
| Accounts Payable     | $2,200   |          | Reduces what you owe vendor    |
| Supplies Expense     |          | $2,000   | Reverses the purchase cost     |
| Input Tax            |          | $200     | Reverses the input tax credit  |

**Result:** You now owe the vendor $3,300 instead of $5,500.

### With Per-Item Discount

If a line item has a 10% discount:

```
Item: Office Supplies, Qty: 10, Price: $100, Discount: 10%, Tax: 15%
  Sub-total:          10 x $100 = $1,000
  Discount:           $1,000 x 10% = $100
  After discount:     $1,000 - $100 = $900
  Tax:                $900 x 15% = $135
  Line total:         $900 + $135 = $1,035
```

Journal:
```
DR  Accounts Payable     $1,035
CR  Office Supplies                $900
CR  Input Tax                      $135
```

---

## Common Use Cases

| Scenario                      | How to Handle                                    |
|-------------------------------|--------------------------------------------------|
| **Return defective goods**    | Link to original bill, adjust quantities to returned amount |
| **Vendor overcharged**        | Link to original bill, adjust prices to the difference |
| **Short delivery**            | Link to original bill, reduce quantities to the shortage |
| **Agreed post-purchase discount** | Create standalone (no bill link), enter discount amount |
| **Wrong items received**      | Link to original bill, include only the wrong items |

---

## Frequently Asked Questions

**Q: What's the difference between a Debit Note and a Credit Note?**
A: A **Debit Note** is issued to your **vendor** (reduces what you owe). A **Credit Note** is issued to your **customer** (reduces what they owe you). They are mirror images of each other.

**Q: Do I have to link a debit note to a bill?**
A: No. You can create a standalone debit note. However, linking to a bill makes it easy to auto-fill items and provides better traceability.

**Q: Does the debit note reduce the bill's remaining amount?**
A: The debit note reduces your **Accounts Payable** balance via journal entries. The bill itself shows the original amount; the AP reduction is reflected in the ledger.

**Q: Can I issue a partial debit note?**
A: Yes. Just include only the items or quantities you're debiting. You don't have to credit the entire bill.

**Q: What if I can't find the Accounts Payable or Input Tax account?**
A: The system looks for accounts named "Accounts Payable" (or similar) in Liabilities, and "Input Tax" (or similar) in Assets. Make sure these accounts exist in your Chart of Accounts.

**Q: Can I create multiple debit notes against the same bill?**
A: Yes. There's no limit on how many debit notes can reference the same bill.

**Q: Is there a PDF export for debit notes?**
A: Not currently. You can use the browser's print function on the detail page as a workaround.

**Q: What happens if I delete a debit note?**
A: All associated journal entries are deleted, and the Accounts Payable adjustment is reversed. The debit note is soft-deleted (still in the database for audit purposes).
