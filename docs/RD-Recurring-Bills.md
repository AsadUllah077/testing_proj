# R&D Document: Recurring Bills Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Recurring Bills                                           |
| **Parent Module**  | Accounting > Purchases                                    |
| **Purpose**        | Automate the creation of vendor bills on a recurring schedule (weekly, monthly, quarterly, yearly) to ensure timely expense recognition and vendor payments. |
| **Target Users**   | Accountants, Finance Managers, Accounts Payable Team       |
| **Permission**     | `account.purchase.view`                                   |

---

## 2. Problem Statement

Organizations with recurring vendor obligations (rent, utilities, subscriptions, maintenance contracts) must manually create the same bills every period. This leads to missed deadlines, late payments, and inaccurate financial reporting.

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer        | Component                                           |
|--------------|-----------------------------------------------------|
| **Model**    | `RecurringBill`, `RecurringBillItem`                 |
| **Controller** | `RecurringBillController` (CRUD + toggle status)  |
| **Command**  | `GenerateRecurringBills` (Artisan scheduled task)    |
| **Views**    | `index`, `create`, `edit`, `_form`, `table` (Blade)  |
| **Routes**   | 7 RESTful routes under `account.recurring-bill.*`    |

### 3.2 Database Schema

**Table: `recurring_bills`**

| Column               | Type              | Description                                   |
|----------------------|-------------------|-----------------------------------------------|
| id                   | bigint (PK)       | Primary key                                   |
| company_id           | FK -> companies   | Multi-tenant isolation                        |
| vendor_id            | FK -> vendors     | Linked vendor                                 |
| account_id           | FK -> chart_of_accounts | Expense account for posting              |
| accounting_period_id | FK (nullable)     | Linked accounting period                      |
| title                | string            | Schedule name/description                     |
| description          | text              | Detailed description                          |
| frequency            | enum              | weekly, monthly, quarterly, yearly            |
| interval             | tinyint           | Every N frequencies (e.g., every 3 months)    |
| start_date           | date              | When the schedule begins                      |
| end_date             | date (nullable)   | When the schedule ends (null = indefinite)    |
| next_run_date        | date              | Next bill generation date                     |
| last_run_date        | date (nullable)   | Last bill generation date                     |
| status               | enum              | active, paused, completed, cancelled          |
| sub_total            | decimal(15,2)     | Subtotal before tax/discount                  |
| tax                  | decimal(15,2)     | Total tax amount                              |
| discount             | decimal(15,2)     | Total discount amount                         |
| total                | decimal(15,2)     | Final total                                   |
| payment_terms_days   | tinyint           | Default payment terms (default: 30)           |
| bills_generated      | int               | Counter of bills generated                    |
| max_occurrences      | int (nullable)    | Limit on total bills (null = unlimited)       |
| created_by           | FK -> users       | Audit: creator                                |
| updated_by           | FK -> users       | Audit: last modifier                          |

**Table: `recurring_bill_items`**

| Column              | Type              | Description                    |
|---------------------|-------------------|--------------------------------|
| id                  | bigint (PK)       | Primary key                    |
| recurring_bill_id   | FK                | Parent recurring bill          |
| account_id          | FK                | Expense account                |
| tax_id              | FK (nullable)     | Applied tax rate               |
| description         | string            | Line item description          |
| quantity            | decimal(10,2)     | Quantity                       |
| price               | decimal(15,2)     | Unit price                     |
| discount_percentage | decimal(5,2)      | Per-item discount %            |
| sub_total           | decimal(15,2)     | Line subtotal                  |
| tax_amount          | decimal(15,2)     | Line tax                       |
| total               | decimal(15,2)     | Line total                     |

**Composite Index:** `(company_id, status, next_run_date)`

### 3.3 Frequency Calculation Logic

```
Weekly    -> add (interval) weeks
Monthly   -> add (interval) months
Quarterly -> add (3 x interval) months
Yearly    -> add (interval) years
```

### 3.4 Automated Generation Flow

1. **Scheduler** runs `bills:generate-recurring` daily at 07:00 UTC
2. Queries all active recurring bills where `next_run_date <= today`
3. For each due schedule:
   - Validates accounting period is unlocked
   - Creates `Bill` record (invoice number: `RECB-{id}-{date}-{seq}`, unique_no: `BI-{padded_id}`)
   - Creates `BillItem` records from template items
   - Posts double-entry transactions: **DR** Expense Account / **CR** Accounts Payable
   - Updates `bills_generated`, `last_run_date`, `next_run_date`
   - Auto-completes if max occurrences or end date reached
4. Logs all activity to `storage/logs/recurring-bills.log`

### 3.5 Expiration Logic

A schedule is considered expired when **either** condition is met:
- `end_date` is set and `end_date < today`
- `max_occurrences` is set and `bills_generated >= max_occurrences`

### 3.6 Status State Machine

```
active <---> paused     (user toggle)
active  ---> completed  (auto, when expired)
active  ---> cancelled  (user action)
```

---

## 4. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Purchases Module      | Generates `Bill` + `BillItem` records                |
| Journal Entries       | Posts double-entry DR Expense / CR Accounts Payable  |
| Accounting Periods    | Validates period is unlocked before posting          |
| Chart of Accounts     | Links to Expense accounts                            |
| Tax Rates             | Applies tax calculations per line item               |
| Vendor Module         | Links bills to vendors (Select2 AJAX search)         |

---

## 5. Technical Decisions & Trade-offs

| Decision                          | Rationale                                                   |
|-----------------------------------|-------------------------------------------------------------|
| Mirrors Recurring Invoices design | Consistent UX and codebase; same patterns for AR and AP     |
| Daily scheduler (not real-time)   | Batch processing is simpler, predictable, and auditable     |
| Soft deletes                      | Preserves audit trail; deleted schedules don't affect generated bills |
| Template-based approach           | Edits apply from next run only; existing bills unaffected   |
| `--dry-run` flag                  | Safe previewing for debugging                               |
| DB transaction wrapping           | Ensures atomicity of bill + items + journal entries         |

---

## 6. Comparison: Recurring Invoices vs Recurring Bills

| Aspect            | Recurring Invoices           | Recurring Bills              |
|-------------------|------------------------------|------------------------------|
| Direction         | Revenue (money in)           | Expense (money out)          |
| Linked Entity     | Customer                     | Vendor                       |
| Account Category  | Revenue                      | Expense                      |
| Generated Record  | Sale                         | Bill                         |
| Journal Entry     | DR Receivable / CR Revenue   | DR Expense / CR Payable      |
| Invoice Prefix    | `REC-`                       | `RECB-`                      |
| Log File          | `recurring-invoices.log`     | `recurring-bills.log`        |

---

## 7. File Manifest

| File                                                              | Purpose                    |
|-------------------------------------------------------------------|----------------------------|
| `app/Models/RecurringBill.php`                                    | Main model (121 lines)     |
| `app/Models/RecurringBillItem.php`                                | Line item model (35 lines) |
| `app/Http/Controllers/Account/RecurringBillController.php`        | CRUD controller (289 lines)|
| `app/Console/Commands/GenerateRecurringBills.php`                 | Artisan command (218 lines)|
| `database/migrations/2026_04_02_000001_create_recurring_bills_table.php` | Migration (73 lines)|
| `resources/views/pages/account/recurring-bill/index.blade.php`    | List view                  |
| `resources/views/pages/account/recurring-bill/_form.blade.php`    | Create/Edit form           |
| `resources/views/pages/account/recurring-bill/table.blade.php`    | Table partial              |
| `routes/account.php`                                              | Route definitions          |
| `routes/console.php` (lines 49-52)                                | Scheduler config           |

---

## 8. Testing Considerations

| Scenario                                 | Expected Behavior                               |
|------------------------------------------|--------------------------------------------------|
| Create schedule with all fields          | Record saved, next_run_date calculated           |
| Toggle active -> paused                  | Status changes, no bills generated while paused  |
| Daily run with due bills                 | Bill + BillItems + Transactions created          |
| Daily run with locked period             | Skipped with error log                           |
| Max occurrences reached                  | Status auto-set to completed                     |
| End date passed                          | Status auto-set to completed                     |
| Dry run flag                             | No records created, output shown                 |
| Edit schedule                            | Changes apply from next run only                 |
| Delete schedule                          | Soft delete; existing bills unaffected           |

---

# User Guide: Recurring Bills

## What Are Recurring Bills?

Recurring Bills allow you to automate vendor payments that happen on a regular cycle. Instead of manually creating the same bill every month for rent, utilities, or subscriptions, you set up a template once and the system generates bills automatically.

---

## How to Access

**Navigation:** Sidebar > **Finance** > **Recurring Bills**

---

## Creating a Recurring Bill Schedule

### Step 1: Open the Form
Click the **"New Schedule"** button on the Recurring Bills page.

### Step 2: Fill in Schedule Settings

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Title**          | A descriptive name (e.g., "Monthly Office Rent")      | Yes      |
| **Description**    | Detailed description of the recurring expense         | Yes      |
| **Vendor**         | Search and select the vendor                          | Yes      |
| **Expense Account**| The expense account to post costs to                  | Yes      |
| **Frequency**      | How often to generate: Weekly, Monthly, Quarterly, Yearly | Yes  |
| **Interval**       | Every N periods (e.g., "Every 1 month")               | Yes      |
| **Start Date**     | When the first bill should be generated               | Yes      |
| **End Date**       | When to stop generating (leave blank for indefinite)  | No       |
| **Max Occurrences**| Total number of bills to generate (leave blank for unlimited) | No |
| **Payment Terms**  | Days until payment is due (default: 30)               | Yes      |
| **Notes**          | Additional notes to include on generated bills        | No       |

### Step 3: Add Line Items

1. Click **"Add Item"** to add bill lines
2. For each item, fill in:
   - **Description** - What the expense is for
   - **Account** - The specific expense account
   - **Quantity** - Number of units
   - **Unit Price** - Price per unit
   - **Tax Rate** - Select applicable tax rate
   - **Discount %** - Per-item discount (optional)
3. At least **one line item** is required

### Step 4: Save
Click **"Save Schedule"**. The system will calculate totals and set the first `next_run_date`.

---

## Managing Recurring Bills

### Dashboard Overview

The index page shows four statistics cards:
- **Active** - Schedules currently generating bills
- **Paused** - Schedules temporarily stopped
- **Completed** - Schedules that have finished
- **Due Today** - Schedules generating a bill today

### Pause / Resume a Schedule

- Click **Pause** (pause icon) to temporarily stop bill generation
- Click **Resume** (play icon) to restart
- While paused, no bills are generated; the schedule resumes from where it stopped

### Edit a Schedule

1. Click the **Edit** button (pencil icon)
2. Modify fields as needed
3. Click **Save**
4. Changes take effect from the **next scheduled run** only

### Delete a Schedule

1. Click the **Delete** button (trash icon)
2. Confirm deletion
3. Previously generated bills remain in the system

---

## How Bills Are Generated Automatically

The system runs an automated job **daily at 07:00 UTC**:

1. Checks all **active** schedules whose **next run date** is today or earlier
2. For each qualifying schedule, creates a **Bill** with:
   - Bill number format: `RECB-{schedule_id}-{date}-{sequence}`
   - All line items copied from the template
   - Journal entry posted: **DR** Expense / **CR** Accounts Payable
3. The schedule's counter increments and the next run date advances
4. If end date or max occurrences reached, status becomes **Completed**

---

## Understanding the Table Columns

| Column         | Description                                              |
|----------------|----------------------------------------------------------|
| **Title**      | Schedule name with vendor shown below                    |
| **Frequency**  | How often (e.g., "Monthly", "Every 2 weeks")            |
| **Next Run**   | Date of next scheduled bill generation                   |
| **Amount**     | Total amount per bill                                    |
| **Generated**  | Number of bills created so far (e.g., "3 / 12")         |
| **Status**     | Color-coded: Green=Active, Amber=Paused, Blue=Completed, Red=Cancelled |
| **Actions**    | Pause/Resume, Edit, Delete                               |

---

## Common Use Cases

| Scenario                  | Recommended Setup                                |
|---------------------------|--------------------------------------------------|
| Monthly office rent       | Frequency: Monthly, Interval: 1, No end date     |
| Quarterly insurance       | Frequency: Quarterly, Interval: 1                |
| Annual software license   | Frequency: Yearly, Interval: 1, Max: 3 years     |
| Bi-weekly cleaning        | Frequency: Weekly, Interval: 2                   |

---

## Frequently Asked Questions

**Q: What's the difference between Recurring Invoices and Recurring Bills?**
A: Recurring Invoices are for billing your **customers** (revenue). Recurring Bills are for paying your **vendors** (expenses).

**Q: What happens if I edit a schedule?**
A: Changes apply from the next run. Already-generated bills are not modified.

**Q: What if the accounting period is locked?**
A: The system skips that bill and logs an error. It retries on the next daily run.

**Q: Can I track which bills came from a recurring schedule?**
A: Yes, generated bills have a reference field showing "Recurring: {title}" and the invoice number starts with `RECB-`.

**Q: How do I end a recurring bill permanently?**
A: Either **delete** the schedule, set an **end date**, or change status to **Cancelled** via edit.
