# R&D Document: Recurring Invoices Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Recurring Invoices                                        |
| **Parent Module**  | Accounting > Sales                                        |
| **Purpose**        | Automate the creation of sale invoices on a recurring schedule (weekly, monthly, quarterly, yearly) to reduce manual data entry and ensure timely billing. |
| **Target Users**   | Accountants, Finance Managers, Billing Administrators      |
| **Permission**     | `account.sale.view`                                       |

---

## 2. Problem Statement

Organizations that bill customers on a regular cycle (subscriptions, retainers, maintenance contracts) must manually create identical invoices every period. This is error-prone, time-consuming, and leads to missed or delayed invoicing.

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer        | Component                                           |
|--------------|-----------------------------------------------------|
| **Model**    | `RecurringInvoice`, `RecurringInvoiceItem`           |
| **Controller** | `RecurringInvoiceController` (CRUD + toggle status)|
| **Command**  | `GenerateRecurringInvoices` (Artisan scheduled task) |
| **Views**    | `index`, `create`, `edit`, `_form`, `table` (Blade)  |
| **Routes**   | 7 RESTful routes under `account.recurring-invoice.*` |

### 3.2 Database Schema

**Table: `recurring_invoices`**

| Column               | Type              | Description                                   |
|----------------------|-------------------|-----------------------------------------------|
| id                   | bigint (PK)       | Primary key                                   |
| company_id           | FK -> companies   | Multi-tenant isolation                        |
| customer_id          | FK -> customers   | Linked customer                               |
| account_id           | FK -> chart_of_accounts | Revenue account for posting            |
| accounting_period_id | FK (nullable)     | Linked accounting period                      |
| title                | string            | Schedule name/description                     |
| description          | text              | Detailed description                          |
| frequency            | enum              | weekly, monthly, quarterly, yearly            |
| interval             | tinyint           | Every N frequencies (e.g., every 2 months)    |
| start_date           | date              | When the schedule begins                      |
| end_date             | date (nullable)   | When the schedule ends (null = indefinite)    |
| next_run_date        | date              | Next invoice generation date                  |
| last_run_date        | date (nullable)   | Last invoice generation date                  |
| status               | enum              | active, paused, completed, cancelled          |
| sub_total            | decimal(15,2)     | Subtotal before tax/discount                  |
| tax                  | decimal(15,2)     | Total tax amount                              |
| discount             | decimal(15,2)     | Total discount amount                         |
| total                | decimal(15,2)     | Final total                                   |
| payment_terms_days   | tinyint           | Default payment terms (default: 30)           |
| note                 | text (nullable)   | Additional notes                              |
| invoices_generated   | int               | Counter of invoices generated                 |
| max_occurrences      | int (nullable)    | Limit on total invoices (null = unlimited)    |
| created_by           | FK -> users       | Audit: creator                                |
| updated_by           | FK -> users       | Audit: last modifier                          |

**Table: `recurring_invoice_items`**

| Column              | Type              | Description                    |
|---------------------|-------------------|--------------------------------|
| id                  | bigint (PK)       | Primary key                    |
| recurring_invoice_id| FK                | Parent recurring invoice       |
| account_id          | FK                | Revenue/income account         |
| tax_id              | FK (nullable)     | Applied tax rate               |
| description         | string            | Line item description          |
| quantity            | decimal(10,2)     | Quantity                       |
| price               | decimal(15,2)     | Unit price                     |
| discount_percentage | decimal(5,2)      | Per-item discount %            |
| sub_total           | decimal(15,2)     | Line subtotal                  |
| tax_amount          | decimal(15,2)     | Line tax                       |
| total               | decimal(15,2)     | Line total                     |

**Composite Index:** `(company_id, status, next_run_date)` for efficient daily query.

### 3.3 Frequency Calculation Logic

```
Weekly    -> add (interval) weeks
Monthly   -> add (interval) months
Quarterly -> add (3 x interval) months
Yearly    -> add (interval) years
```

### 3.4 Automated Generation Flow

1. **Scheduler** runs `invoices:generate-recurring` daily at 07:00 UTC
2. Queries all active recurring invoices where `next_run_date <= today`
3. For each due schedule:
   - Validates accounting period is unlocked
   - Creates `Sale` record (invoice number: `REC-{id}-{date}-{seq}`)
   - Creates `SaleItem` records from template items
   - Posts double-entry transactions: **DR** Accounts Receivable / **CR** Revenue
   - Updates `invoices_generated`, `last_run_date`, `next_run_date`
   - Auto-completes if max occurrences or end date reached
4. Logs all activity to `storage/logs/recurring-invoices.log`

### 3.5 Expiration Logic

A schedule is considered expired when **either** condition is met:
- `end_date` is set and `end_date < today`
- `max_occurrences` is set and `invoices_generated >= max_occurrences`

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
| Sales Module          | Generates `Sale` + `SaleItem` records                |
| Journal Entries       | Posts double-entry DR/CR transactions                |
| Accounting Periods    | Validates period is unlocked before posting          |
| Chart of Accounts     | Links to Revenue accounts                            |
| Tax Rates             | Applies tax calculations per line item               |
| Customer Module       | Links invoices to customers (Select2 AJAX search)    |

---

## 5. Technical Decisions & Trade-offs

| Decision                          | Rationale                                                   |
|-----------------------------------|-------------------------------------------------------------|
| Daily scheduler (not real-time)   | Batch processing is simpler, predictable, and auditable     |
| Soft deletes                      | Preserves audit trail; deleted schedules don't affect generated invoices |
| Template-based (not cloning)      | Line items are templates; edits apply from next run only    |
| `--dry-run` flag                  | Allows previewing without side effects for debugging        |
| Unique composite index            | Prevents duplicate schedules per company/period             |
| DB transaction wrapping           | Ensures atomicity; partial invoice creation is impossible   |

---

## 6. File Manifest

| File                                                              | Purpose                    |
|-------------------------------------------------------------------|----------------------------|
| `app/Models/RecurringInvoice.php`                                 | Main model                 |
| `app/Models/RecurringInvoiceItem.php`                             | Line item model            |
| `app/Http/Controllers/Account/RecurringInvoiceController.php`     | CRUD controller            |
| `app/Console/Commands/GenerateRecurringInvoices.php`              | Artisan command            |
| `database/migrations/2026_03_25_000001_create_recurring_invoices_table.php` | Migration      |
| `resources/views/pages/account/recurring-invoice/index.blade.php` | List view                  |
| `resources/views/pages/account/recurring-invoice/_form.blade.php` | Create/Edit form           |
| `resources/views/pages/account/recurring-invoice/table.blade.php` | Table partial              |
| `routes/account.php` (lines 255-267)                              | Route definitions          |
| `routes/console.php` (lines 42-46)                                | Scheduler config           |

---

## 7. Testing Considerations

| Scenario                                 | Expected Behavior                               |
|------------------------------------------|--------------------------------------------------|
| Create schedule with all fields          | Record saved, next_run_date calculated           |
| Toggle active -> paused                  | Status changes, no invoices generated while paused |
| Daily run with due invoices              | Sale + SaleItems + Transactions created          |
| Daily run with locked period             | Skipped with error log                           |
| Max occurrences reached                  | Status auto-set to completed                     |
| End date passed                          | Status auto-set to completed                     |
| Dry run flag                             | No records created, output shown                 |
| Edit schedule                            | Changes apply from next run only                 |
| Delete schedule                          | Soft delete; existing invoices unaffected        |

---

# User Guide: Recurring Invoices

## What Are Recurring Invoices?

Recurring Invoices allow you to set up automated billing schedules. Instead of manually creating the same invoice every week or month, you define a template once, and the system generates invoices automatically on your chosen schedule.

---

## How to Access

**Navigation:** Sidebar > **Finance** > **Recurring Invoices**

---

## Creating a Recurring Invoice Schedule

### Step 1: Open the Form
Click the **"New Schedule"** button on the Recurring Invoices page.

### Step 2: Fill in Schedule Settings

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Title**          | A descriptive name (e.g., "Monthly Hosting - ABC Corp") | Yes    |
| **Customer**       | Search and select the customer to bill                | Yes      |
| **Revenue Account**| The income account to post revenue to                 | Yes      |
| **Frequency**      | How often to generate: Weekly, Monthly, Quarterly, Yearly | Yes  |
| **Interval**       | Every N periods (e.g., "Every 2 months")              | Yes      |
| **Start Date**     | When the first invoice should be generated            | Yes      |
| **End Date**       | When to stop generating (leave blank for indefinite)  | No       |
| **Max Occurrences**| Total number of invoices to generate (leave blank for unlimited) | No |
| **Payment Terms**  | Days until payment is due (default: 30)               | Yes      |
| **Notes**          | Additional notes to include on generated invoices     | No       |

### Step 3: Add Line Items

1. Click **"Add Item"** to add invoice lines
2. For each item, fill in:
   - **Description** - What the charge is for
   - **Account** - The specific revenue account
   - **Quantity** - Number of units
   - **Unit Price** - Price per unit
   - **Tax** - Select applicable tax rate
   - **Discount %** - Per-item discount (optional)
3. At least **one line item** is required
4. Add more items by clicking **"Add Item"** again

### Step 4: Save
Click **"Save Schedule"**. The system will calculate totals and set the first `next_run_date` based on your start date.

---

## Managing Recurring Invoices

### Dashboard Overview

The index page shows four statistics cards at the top:
- **Active** - Schedules currently generating invoices
- **Paused** - Schedules temporarily stopped
- **Completed** - Schedules that have finished their run
- **Due Today** - Schedules that will generate an invoice today

### Filtering & Search

- **Search bar** - Find by title or customer name
- **Status filter** - Filter by Active, Paused, Completed, Cancelled
- **Per-page** - Choose how many records to display

### Pause / Resume a Schedule

1. Find the schedule in the list
2. Click the **Pause** button (pause icon) to temporarily stop generation
3. Click **Resume** (play icon) to restart it
4. No invoices are generated while paused; the schedule picks up from where it left off

### Edit a Schedule

1. Click the **Edit** button (pencil icon)
2. Modify any fields (frequency, amounts, line items, etc.)
3. Click **Save**
4. Changes take effect from the **next scheduled run**; already-generated invoices are not affected

### Delete a Schedule

1. Click the **Delete** button (trash icon)
2. Confirm the deletion
3. The schedule is soft-deleted; previously generated invoices remain in the system

---

## How Invoices Are Generated Automatically

The system runs an automated job **daily at 07:00 UTC**:

1. It checks all **active** schedules whose **next run date** is today or earlier
2. For each qualifying schedule, it creates a **Sale Invoice** with:
   - Invoice number format: `REC-{schedule_id}-{date}-{sequence}`
   - All line items copied from the template
   - Double-entry accounting entries posted automatically
3. The schedule's counter increments and the next run date is calculated
4. If the schedule has reached its end date or max occurrences, it is automatically marked as **Completed**

### Overdue Indicator

If a schedule's next run date has passed but the invoice wasn't generated (e.g., server was down), it appears with a **red "Overdue"** indicator in the list. The next time the daily job runs, it will catch up.

---

## Understanding the Table Columns

| Column         | Description                                              |
|----------------|----------------------------------------------------------|
| **Title**      | Schedule name with customer shown below                  |
| **Frequency**  | How often (e.g., "Monthly", "Every 2 weeks")            |
| **Next Run**   | Date of next scheduled invoice generation                |
| **Amount**     | Total amount per invoice                                 |
| **Generated**  | Number of invoices created so far (e.g., "5 / 12")      |
| **Status**     | Color-coded badge: Green=Active, Amber=Paused, Blue=Completed, Red=Cancelled |
| **Actions**    | Pause/Resume, Edit, Delete                               |

---

## Frequently Asked Questions

**Q: What happens if I edit a schedule?**
A: Changes apply from the next run. Already-generated invoices are not modified.

**Q: Can I generate an invoice manually?**
A: Currently, invoices are generated by the daily automated job. You cannot trigger manual generation from the UI.

**Q: What if the accounting period is locked?**
A: The system skips that schedule and logs an error. It will retry on the next daily run.

**Q: Can I set a schedule to run on a specific day of the month?**
A: The schedule runs based on the start date plus the frequency interval. For example, if you start on the 15th with monthly frequency, invoices generate on the 15th each month.

**Q: How do I stop a recurring invoice permanently?**
A: Either **delete** the schedule or change its status to **Cancelled** by editing it.
