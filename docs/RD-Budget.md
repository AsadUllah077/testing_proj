# R&D Document: Budget Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Budget Management                                         |
| **Parent Module**  | Accounting                                                |
| **Purpose**        | Allow organizations to set financial targets per account per accounting period, then compare budgeted amounts against actual transactions to monitor spending and revenue performance. |
| **Target Users**   | Finance Managers, CFOs, Department Heads, Accountants      |
| **Permission**     | `account.dashboard.view`                                  |

---

## 2. Problem Statement

Without budgets, organizations lack visibility into whether spending and revenue are on track. Finance teams need a way to:
- Set target amounts for each expense, revenue, and COGS account per period
- Compare budgeted vs actual amounts in real time
- Identify over-budget accounts early to take corrective action

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer        | Component                                           |
|--------------|-----------------------------------------------------|
| **Model**    | `Budget`                                             |
| **Controller** | `BudgetController` (index, upsert, destroy, report)|
| **Views**    | `index.blade.php` (inline editing), `report.blade.php` (variance report) |
| **Routes**   | 4 routes under `account.budget.*`                    |

### 3.2 Database Schema

**Table: `budgets`**

| Column               | Type              | Description                                   |
|----------------------|-------------------|-----------------------------------------------|
| id                   | bigint (PK)       | Primary key                                   |
| company_id           | FK -> companies   | Multi-tenant isolation                        |
| accounting_period_id | FK -> accounting_periods | Budget period                          |
| account_id           | FK -> chart_of_accounts  | Target account                         |
| created_by           | FK -> users       | Audit: creator                                |
| updated_by           | FK -> users       | Audit: last modifier                          |
| amount               | decimal(15,2)     | Budgeted amount                               |
| notes                | text (nullable)   | Optional notes/justification                  |
| timestamps           |                   | created_at, updated_at                        |

**Unique Constraint:** `(company_id, accounting_period_id, account_id)` - ensures only one budget entry per account per period per company.

**Composite Index:** `(company_id, accounting_period_id)`

### 3.3 Budget Entry Mechanism (Upsert Pattern)

The system uses Laravel's `updateOrCreate()` to handle budget entries:

```
POST /account/budget/upsert
{
    accounting_period_id: 5,
    account_id: 42,
    amount: 15000.00,
    notes: "Q2 marketing budget"
}
```

- If a budget for that account + period + company exists -> **update** the amount
- If not -> **create** a new record
- Both `created_by` and `updated_by` are set to the current user

### 3.4 Budget vs Actual Calculation

The variance report computes actuals from the `transactions` table:

**For Revenue accounts:**
```
Actual = SUM(credit) - SUM(debit)   [credit is income]
```

**For Expense / COGS accounts:**
```
Actual = SUM(debit) - SUM(credit)   [debit is cost]
```

**Variance Calculation:**
```
Variance = Budget - Actual
  Positive = Under budget (favorable)
  Negative = Over budget (unfavorable)

Used % = (Actual / Budget) * 100
```

### 3.5 Account Filtering

Only three account categories are included in budgets:
- **Expenses** - Operating costs, overhead
- **Revenue** - Income streams
- **COGS** (Cost of Goods Sold) - Direct production costs

Other categories (Assets, Liabilities, Equity) are excluded as they are balance sheet accounts not typically budgeted by period.

---

## 4. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Chart of Accounts     | Provides the account list for budget targets         |
| Accounting Periods    | Defines the time boundaries for budgets              |
| Transactions          | Source of actual amounts for variance calculation     |
| Multi-Tenancy         | `BelongsToCompany` trait isolates data by company    |

---

## 5. Technical Decisions & Trade-offs

| Decision                              | Rationale                                                   |
|---------------------------------------|-------------------------------------------------------------|
| Inline AJAX editing (no separate form)| Fast, spreadsheet-like UX; no page navigation needed        |
| 600ms debounce on save                | Prevents excessive API calls during rapid typing            |
| `updateOrCreate` (upsert)            | Single endpoint handles both create and update              |
| No separate budget permissions        | Reuses `account.dashboard.view` for simplicity              |
| Only Expenses/Revenue/COGS           | Balance sheet accounts don't need periodic budgeting        |
| Per-period budgets (not annual split) | Keeps the model simple; each period is independent          |

---

## 6. File Manifest

| File                                                              | Purpose                    |
|-------------------------------------------------------------------|----------------------------|
| `app/Models/Budget.php`                                           | Budget model               |
| `app/Http/Controllers/Account/BudgetController.php`               | Controller (4 methods)     |
| `database/migrations/2026_03_26_000001_create_budgets_table.php`  | Migration                  |
| `resources/views/pages/account/budget/index.blade.php`            | Inline editing view        |
| `resources/views/pages/account/budget/report.blade.php`           | Variance report view       |
| `routes/account.php` (lines 300-305)                              | Route definitions          |

---

## 7. Testing Considerations

| Scenario                                 | Expected Behavior                                    |
|------------------------------------------|------------------------------------------------------|
| Set budget for account in period         | Record created via upsert                            |
| Update existing budget amount            | Existing record updated (not duplicated)             |
| Delete a budget entry                    | Record removed, actuals unaffected                   |
| View report with no transactions         | Actual = 0, Variance = Budget amount                 |
| View report with over-budget account     | Row highlighted red, negative variance shown         |
| Budget = 0 but actuals exist             | Used % shown as N/A or infinity handled              |
| Switch accounting period                 | Different budget set loaded for that period           |
| Multi-tenant isolation                   | Company A cannot see Company B's budgets             |

---

# User Guide: Budget Management

## What Is Budget Management?

The Budget module lets you set financial targets for each account in your Chart of Accounts, then compare those targets against actual transactions. This helps you monitor whether your organization is spending and earning as planned.

---

## How to Access

**Navigation:** Sidebar > **Finance** > **Budget**

---

## Setting Up Budgets

### Step 1: Select an Accounting Period

At the top of the Budget page, use the **Period Selector** dropdown to choose the accounting period you want to budget for (e.g., "January 2026", "Q1 2026").

### Step 2: Enter Budget Amounts

The page displays a table with all your **Expense**, **Revenue**, and **COGS** accounts organized by category:

| Column      | Description                                      |
|-------------|--------------------------------------------------|
| **Account** | Account name and number from your Chart of Accounts |
| **Category**| Expense, Revenue, or COGS                        |
| **Amount**  | Editable field - enter your budget target        |
| **Notes**   | Editable field - optional justification or memo  |

### Step 3: Type and Save

1. Click on the **Amount** field next to any account
2. Type the budget amount (e.g., `15000`)
3. The system **automatically saves** after you stop typing (600ms delay)
4. A brief toast notification confirms the save
5. Repeat for all accounts you want to budget

**No save button needed** - the system saves as you type!

### Step 4: Add Notes (Optional)

Click the **Notes** field next to any budget entry to add context:
- "Increased 10% from last quarter"
- "New marketing campaign approved"
- "Includes contractor fees"

Notes are also auto-saved.

---

## Deleting a Budget Entry

1. Click the **Delete** button (trash icon) next to the budget entry
2. Confirm the deletion
3. The budget target is removed; this does not affect actual transactions

---

## Viewing the Budget vs Actual Report

### How to Access
Click the **"View Report"** button on the Budget page, or navigate to **Budget > Report**.

### Understanding the Report

The report shows a comprehensive comparison:

#### Summary Cards (Top)

| Card                | Description                                    |
|---------------------|------------------------------------------------|
| **Total Budgeted**  | Sum of all budget amounts for the period       |
| **Total Actual**    | Sum of all actual transactions for the period  |
| **Total Variance**  | Budget - Actual (positive = under, negative = over) |

#### Detailed Table

| Column         | Description                                              |
|----------------|----------------------------------------------------------|
| **Account**    | Account name with category label                         |
| **Budget**     | The target amount you set                                |
| **Actual**     | Real transactions from the accounting system             |
| **Variance**   | Budget minus Actual                                      |
| **Used %**     | Percentage of budget consumed                            |
| **Progress**   | Visual progress bar                                      |

#### Color Coding

| Progress Bar Color | Meaning                    |
|--------------------|----------------------------|
| **Green**          | Under 80% of budget used   |
| **Yellow**         | 80-100% of budget used     |
| **Red**            | Over 100% (over budget!)   |

Rows where actual spending exceeds the budget are **highlighted in red** for immediate visibility.

---

## How Actuals Are Calculated

The system automatically calculates actual amounts from your accounting transactions:

- **Revenue accounts**: Total credits minus debits (because credits represent income)
- **Expense/COGS accounts**: Total debits minus credits (because debits represent costs)

You don't need to enter actuals manually - they come directly from your journal entries, invoices, and bills.

---

## Tips for Effective Budgeting

1. **Set budgets at the start of each period** - Enter targets before the period begins
2. **Review the report weekly** - Catch overspending early
3. **Use notes** - Document why budgets were set at certain levels for future reference
4. **Compare across periods** - Switch between periods to see trends
5. **Focus on variance** - Large negative variances need immediate attention

---

## Frequently Asked Questions

**Q: Which accounts can I budget?**
A: Only Expense, Revenue, and COGS accounts. Balance sheet accounts (Assets, Liabilities, Equity) are not budgeted.

**Q: Can I budget the same account differently in different periods?**
A: Yes! Each accounting period has its own independent budget. You can set different amounts for Q1 vs Q2.

**Q: What happens if I don't set a budget for an account?**
A: It simply won't appear in the variance report. Unbudgeted accounts still track transactions normally.

**Q: Can multiple users edit budgets?**
A: Yes, anyone with the `account.dashboard.view` permission can create and edit budgets. The system tracks who created and last modified each entry.

**Q: Does deleting a budget affect my transactions?**
A: No. Budgets are targets only. Deleting a budget entry has no impact on actual accounting transactions.

**Q: How often does the "Actual" column update?**
A: In real time. Every time you view the report, it queries the latest transactions from your accounting system.
