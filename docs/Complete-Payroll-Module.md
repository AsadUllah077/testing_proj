# Complete Documentation: Payroll Module

## R&D Overview & User Guide

---

# PART 1: R&D DOCUMENTATION

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Payroll Management                                        |
| **Purpose**        | End-to-end payroll processing including salary structure configuration, pay run generation, tax calculation, accounting integration, payslip distribution, and employee self-service portal. |
| **Target Users**   | HR Managers, Payroll Administrators, Finance Teams, Employees (portal) |
| **Tech Stack**     | Laravel (PHP), Blade, jQuery, Chart.js, DomPDF, Maatwebsite Excel, Queued Jobs |
| **Multi-Tenant**   | Yes - all data isolated per company                       |

---

## 2. Module Architecture Map

### 2.1 Sub-Modules

```
PAYROLL MODULE
├── CONFIGURATION
│   ├── Pay Items (Earnings, Deductions, Reimbursements)
│   ├── Employee Pay Structures
│   ├── Tax Brackets (FBR)
│   └── Hourly Rates (for Overtime)
│
├── PROCESSING
│   ├── Pay Run Creation
│   ├── Pay Run Generation (batch payslip creation)
│   ├── Payslip Management (add/edit/remove items)
│   ├── Overtime Calculation
│   ├── Tax Calculation (progressive brackets)
│   ├── YTD (Year-to-Date) Tracking
│   └── Approval Workflow
│
├── ACCOUNTING INTEGRATION
│   ├── Post to Accounting (journal entries)
│   ├── Record Payment (bank disbursement)
│   └── Deduction Payments (tax/insurance remittance)
│
├── OUTPUT & DISTRIBUTION
│   ├── Payslip PDF Generation
│   ├── Email Payslips (individual & batch)
│   ├── Bank Payment CSV File
│   └── Remittance Reports
│
├── LIABILITIES
│   ├── Unpaid Deductions Tracking
│   ├── Liability Reports
│   └── Deduction Payment Recording
│
├── REPORTS
│   ├── Dashboard KPIs & Trends
│   ├── Payroll Summary Report
│   ├── Department Breakdown
│   └── Pay Run Comparison
│
└── EMPLOYEE SELF-SERVICE
    ├── Employee Portal Login
    ├── View Own Payslips
    ├── Download Payslip PDFs
    └── Leave Balance Display
```

### 2.2 Controller Inventory (10 Controllers)

| Controller                    | Methods | Purpose                              |
|-------------------------------|---------|--------------------------------------|
| PayrollDashboardController    | 1       | Dashboard with KPIs and trends       |
| PayRunController              | 14      | Core pay run management              |
| PayslipController             | 5       | Individual payslip management        |
| EmployeePayController         | 3       | Employee salary configuration        |
| PayItemController             | 4       | Pay item CRUD                        |
| PayrollLiabilityController    | 4       | Liability tracking and payments      |
| TaxBracketController          | 5       | Tax bracket management               |
| PayrollReportController       | 2       | Payroll reports and exports          |
| EmployeeAuthController        | 2       | Employee portal authentication       |
| PayslipPortalController       | 3       | Employee self-service payslips       |

### 2.3 Model Inventory (6 Core + 4 Related)

**Core Payroll Models:**

| Model                  | Table                    | Purpose                          |
|------------------------|--------------------------|----------------------------------|
| PayRun                 | pay_runs                 | Batch payroll processing periods |
| Payslip                | payslips                 | Individual employee payslips     |
| PayslipItem            | payslip_items            | Earnings/deductions per payslip  |
| PayItem                | pay_items                | Earning/deduction definitions    |
| EmployeePayStructure   | employee_pay_structures  | Employee recurring pay items     |
| TaxBracket             | tax_brackets             | Progressive tax slabs            |

**Related Models:** Employee, LeaveBalance, EmployeeAttendance, ChartOfAccount (for accounting integration)

### 2.4 Service Layer

| Service                 | Lines | Purpose                              |
|-------------------------|-------|--------------------------------------|
| PayrollService          | 643   | Core payroll processing engine       |
| TaxCalculationService   | ~100  | FBR progressive tax calculation      |

---

## 3. Database Schema

### 3.1 `pay_items` Table

| Column           | Type              | Description                                   |
|------------------|-------------------|-----------------------------------------------|
| id               | bigint (PK)       | Primary key                                   |
| company_id       | FK -> companies   | Multi-tenant isolation                        |
| name             | string            | Pay item name (e.g., "Basic Salary")          |
| type             | enum              | earning, deduction, reimbursement             |
| account_id       | FK -> chart_of_accounts | Linked accounting account              |
| calculation_type | enum              | fixed, percentage                             |
| percentage_value | decimal (nullable)| % value if calculation_type = percentage      |
| base_item_id     | FK (nullable)     | Reference pay item for % calculations         |
| timestamps       |                   |                                               |

### 3.2 `employee_pay_structures` Table

| Column           | Type              | Description                                   |
|------------------|-------------------|-----------------------------------------------|
| id               | bigint (PK)       | Primary key                                   |
| company_id       | FK                | Multi-tenant                                  |
| employee_id      | FK -> employees   | Employee this structure belongs to             |
| pay_item_id      | FK -> pay_items   | Which pay item                                |
| amount           | decimal(15,2)     | Fixed amount for this item                    |
| hourly_rate      | decimal(10,2)     | Rate for overtime calculation                 |
| sort_order       | int               | Display order                                 |
| timestamps       |                   |                                               |

### 3.3 `pay_runs` Table

| Column           | Type              | Description                                   |
|------------------|-------------------|-----------------------------------------------|
| id               | bigint (PK)       | Primary key                                   |
| company_id       | FK                | Multi-tenant                                  |
| period_start     | date              | Pay period start date                         |
| period_end       | date              | Pay period end date                           |
| payment_date     | date              | When employees are paid                       |
| status           | enum              | draft, posted, paid                           |
| frequency        | enum              | weekly, fortnightly, monthly                  |
| approval_status  | enum              | pending, approved, rejected                   |
| approved_by      | FK -> users       | Who approved                                  |
| approved_at      | datetime          | When approved                                 |
| approval_notes   | text (nullable)   | Rejection reason                              |
| created_by       | FK -> users       | Who created the pay run                       |
| timestamps       |                   |                                               |
| softDeletes      |                   |                                               |

### 3.4 `payslips` Table

| Column           | Type              | Description                                   |
|------------------|-------------------|-----------------------------------------------|
| id               | bigint (PK)       | Primary key                                   |
| company_id       | FK                | Multi-tenant                                  |
| pay_run_id       | FK -> pay_runs    | Parent pay run                                |
| employee_id      | FK -> employees   | Employee this payslip belongs to              |
| gross_earnings   | decimal(15,2)     | Total earnings                                |
| total_deductions | decimal(15,2)     | Total deductions                              |
| net_pay          | decimal(15,2)     | Take-home pay                                 |
| ytd_gross        | decimal(15,2)     | Year-to-date gross earnings                   |
| ytd_deductions   | decimal(15,2)     | Year-to-date deductions                       |
| ytd_net          | decimal(15,2)     | Year-to-date net pay                          |
| status           | enum              | draft, posted, paid                           |
| timestamps       |                   |                                               |
| softDeletes      |                   |                                               |

### 3.5 `payslip_items` Table

| Column            | Type              | Description                                   |
|-------------------|-------------------|-----------------------------------------------|
| id                | bigint (PK)       | Primary key                                   |
| payslip_id        | FK -> payslips    | Parent payslip                                |
| pay_item_id       | FK -> pay_items   | Which pay item                                |
| amount            | decimal(15,2)     | Calculated amount                             |
| description       | string (nullable) | Item description                              |
| paid_at           | datetime (nullable)| When this deduction was remitted              |
| payment_reference | string (nullable) | Bank/payment reference for remittance         |
| timestamps        |                   |                                               |

### 3.6 `tax_brackets` Table

| Column           | Type              | Description                                   |
|------------------|-------------------|-----------------------------------------------|
| id               | bigint (PK)       | Primary key                                   |
| company_id       | FK                | Multi-tenant                                  |
| fiscal_year      | string            | e.g., "2024-2025"                             |
| min_income       | decimal(15,2)     | Bracket minimum (annual)                      |
| max_income       | decimal(15,2) (nullable) | Bracket maximum (null = unlimited)     |
| base_tax         | decimal(15,2)     | Fixed tax for amounts below this bracket      |
| rate             | decimal(5,2)      | Percentage rate for this bracket              |
| sort_order       | int               | Bracket ordering                              |
| timestamps       |                   |                                               |

---

## 4. Core Business Logic

### 4.1 Pay Run Generation Flow

```
PayrollService::generatePayRun($data)
│
├── 1. Create PayRun record (status: draft)
│
├── 2. For each selected employee:
│   ├── createPayslipForEmployee()
│   │   ├── Fetch employee's pay structure items
│   │   ├── For each pay item:
│   │   │   ├── If calculation_type = 'fixed' → use structure amount
│   │   │   ├── If calculation_type = 'percentage' → calculate from base item
│   │   │   └── If overtime → multiply hourly_rate × overtime_hours × 1.5
│   │   ├── Sum earnings (type = 'earning' + 'reimbursement')
│   │   ├── Sum deductions (type = 'deduction')
│   │   ├── Calculate tax via TaxCalculationService
│   │   ├── net_pay = gross_earnings - total_deductions
│   │   ├── Calculate YTD from previous payslips in fiscal year
│   │   └── Create Payslip + PayslipItem records
│   └── Return payslip
│
└── 3. Return completed PayRun with all payslips
```

### 4.2 Tax Calculation (FBR Progressive)

```
TaxCalculationService::calculateMonthlyTax($annualIncome)
│
├── 1. Annualize income: monthly × 12
├── 2. Find applicable tax bracket(s)
├── 3. Calculate tax using progressive slabs:
│   ├── Amount in bracket = min(income, max) - min
│   ├── Tax for bracket = amount × rate%
│   ├── Total tax = base_tax + bracket tax
│   └── Monthly tax = annual tax / 12
└── 4. Return monthly tax amount
```

**Default FBR Tax Brackets (2024-2025):**

| Annual Income Range       | Base Tax  | Rate  |
|---------------------------|-----------|-------|
| 0 - 600,000              | 0         | 0%    |
| 600,001 - 1,200,000      | 0         | 5%    |
| 1,200,001 - 2,400,000    | 30,000    | 15%   |
| 2,400,001 - 3,600,000    | 210,000   | 25%   |
| 3,600,001 - 6,000,000    | 510,000   | 30%   |
| 6,000,001+                | 1,230,000 | 35%   |

### 4.3 Overtime Calculation

```
Overtime Pay = Hourly Rate × Overtime Hours × 1.5

Example:
  Hourly Rate = PKR 500
  Overtime Hours = 20
  Overtime Pay = 500 × 20 × 1.5 = PKR 15,000
```

### 4.4 Percentage-Based Pay Item Calculation

```
If pay_item.calculation_type = 'percentage':
  base_amount = find amount of base_item in same payslip
  amount = base_amount × (percentage_value / 100)

Example:
  Pay Item: "House Rent Allowance" (45% of Basic Salary)
  Basic Salary: PKR 100,000
  HRA = 100,000 × 45% = PKR 45,000
```

### 4.5 YTD (Year-to-Date) Calculation

For each payslip, the system calculates:
```
ytd_gross      = SUM(gross_earnings) from all payslips in fiscal year + current
ytd_deductions = SUM(total_deductions) from all payslips in fiscal year + current
ytd_net        = SUM(net_pay) from all payslips in fiscal year + current
```

### 4.6 Post to Accounting (Journal Entries)

```
PayrollService::postToAccounting($payRun)
│
├── For each payslip in pay run:
│   ├── For each earning item:
│   │   └── DR  Expense Account (from pay_item.account_id)
│   ├── For each deduction item:
│   │   └── CR  Liability Account (from pay_item.account_id)
│   └── CR  Wages Payable (net_pay amount)
│
├── Update pay run status → 'posted'
├── Update all payslip statuses → 'posted'
└── Create Transaction records in accounting system
```

**Journal Entry Example (for one employee):**

| Account              | Debit      | Credit     | Description        |
|----------------------|------------|------------|--------------------|
| Salary Expense       | 100,000    |            | Basic Salary       |
| HRA Expense          | 45,000     |            | House Rent         |
| Transport Expense    | 10,000     |            | Transport Allow.   |
| Income Tax Payable   |            | 12,500     | Employee tax       |
| EOBI Payable         |            | 1,250      | Social security    |
| Wages Payable        |            | 141,250    | Net pay            |

### 4.7 Record Payment (Bank Disbursement)

```
PayrollService::recordPayment($payRun, $bankAccountId)
│
├── For each payslip:
│   ├── DR  Wages Payable (net_pay)
│   └── CR  Bank Account (net_pay)
│
├── Update pay run status → 'paid'
└── Update all payslip statuses → 'paid'
```

### 4.8 Pay Run Deletion

```
PayrollService::deletePayRun($payRun)
│
├── If status = 'posted' or 'paid':
│   └── Reverse all accounting entries (delete transactions)
├── Delete all payslip items
├── Delete all payslips
└── Soft-delete the pay run
```

---

## 5. Approval Workflow

```
Draft ─── Submit ──→ Pending Approval
                          │
                    ┌─────┴─────┐
                    │           │
                 Approve     Reject
                    │           │
                    ▼           ▼
                Approved    Rejected (with notes)
                    │
              Post to Accounting
                    │
                  Posted
                    │
              Record Payment
                    │
                   Paid
```

| Status              | Can Edit Items? | Can Post? | Can Pay? | Can Delete? |
|---------------------|-----------------|-----------|----------|-------------|
| Draft               | Yes             | No        | No       | Yes         |
| Pending Approval    | No              | No        | No       | Yes         |
| Approved            | No              | Yes       | No       | Yes         |
| Posted              | No              | No        | Yes      | Yes (reverses)|
| Paid                | No              | No        | No       | Yes (reverses)|

---

## 6. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| **Accounting Module** | Journal entries for payroll expenses + liabilities    |
| **Chart of Accounts** | Pay items linked to expense/liability accounts        |
| **Bank Module**       | Payment recording against bank accounts               |
| **Employee/HR**       | Employee master data, department, bank details        |
| **Leave Management**  | Leave balance display on payslips                     |
| **Email System**      | Queued payslip email distribution                     |

---

## 7. File Manifest

| Category    | File                                                        | Purpose                    |
|-------------|-------------------------------------------------------------|----------------------------|
| Controller  | `app/Http/Controllers/Payroll/PayrollDashboardController.php` | Dashboard               |
| Controller  | `app/Http/Controllers/Payroll/PayRunController.php`          | Pay run management         |
| Controller  | `app/Http/Controllers/Payroll/PayslipController.php`         | Payslip management         |
| Controller  | `app/Http/Controllers/Payroll/EmployeePayController.php`     | Employee salary setup      |
| Controller  | `app/Http/Controllers/Payroll/PayItemController.php`         | Pay item CRUD              |
| Controller  | `app/Http/Controllers/Payroll/PayrollLiabilityController.php`| Liability management       |
| Controller  | `app/Http/Controllers/Payroll/TaxBracketController.php`      | Tax brackets               |
| Controller  | `app/Http/Controllers/Payroll/PayrollReportController.php`   | Reports                    |
| Controller  | `app/Http/Controllers/Employee/EmployeeAuthController.php`   | Portal login               |
| Controller  | `app/Http/Controllers/Employee/PayslipPortalController.php`  | Portal payslips            |
| Model       | `app/Models/PayRun.php`                                      | Pay run model              |
| Model       | `app/Models/Payslip.php`                                     | Payslip model              |
| Model       | `app/Models/PayslipItem.php`                                 | Payslip item model         |
| Model       | `app/Models/PayItem.php`                                     | Pay item model             |
| Model       | `app/Models/EmployeePayStructure.php`                        | Pay structure model        |
| Model       | `app/Models/TaxBracket.php`                                  | Tax bracket model          |
| Service     | `app/Services/PayrollService.php`                            | Core payroll engine (643 lines) |
| Service     | `app/Services/TaxCalculationService.php`                     | Tax calculation            |
| Job         | `app/Jobs/SendPayslipEmailJob.php`                           | Queued email sender        |
| Mail        | `app/Mail/PayslipMail.php`                                   | Payslip email template     |
| Export      | `app/Exports/PayrollLiabilityExport.php`                     | Liability Excel export     |
| Routes      | `routes/payroll.php`                                         | 31 route endpoints         |
| Migration   | `database/migrations/2026_01_12_101105_create_payroll_tables.php` | Core tables          |
| Migration   | `database/migrations/2026_01_12_103930_update_payroll_tables_for_dynamism.php` | Dynamic calc |
| Migration   | `database/migrations/2026_01_15_071823_add_payment_columns_to_payslip_items.php` | Payment tracking |
| Migration   | `database/migrations/2026_03_28_133548_add_ytd_to_payslips_table.php` | YTD fields        |
| Migration   | `database/migrations/2026_03_28_133641_add_approval_to_pay_runs_table.php` | Approval workflow |
| Migration   | `database/migrations/2026_03_28_133646_create_tax_brackets_table.php` | Tax brackets      |
| Migration   | `database/migrations/2026_03_28_133648_add_hourly_rate_to_employee_pay_structures_table.php` | Overtime |
| View        | `resources/views/pages/payroll/dashboard.blade.php`          | Dashboard                  |
| View        | `resources/views/pages/payroll/pay-item/index.blade.php`     | Pay items                  |
| View        | `resources/views/pages/payroll/pay-run/index.blade.php`      | Pay run list               |
| View        | `resources/views/pages/payroll/pay-run/create.blade.php`     | Create pay run             |
| View        | `resources/views/pages/payroll/pay-run/show.blade.php`       | Pay run detail             |
| View        | `resources/views/pages/payroll/employee-pay/index.blade.php` | Employee pay list          |
| View        | `resources/views/pages/payroll/employee-pay/edit.blade.php`  | Edit pay structure         |
| View        | `resources/views/pages/payroll/tax-brackets/index.blade.php` | Tax brackets               |
| View        | `resources/views/pages/payroll/liabilities/index.blade.php`  | Liabilities list           |
| View        | `resources/views/pages/payroll/liabilities/report.blade.php` | Liability report           |
| View        | `resources/views/pages/payroll/reports/summary.blade.php`    | Summary report             |
| View        | `resources/views/pages/employee/payslips/index.blade.php`    | Portal: payslip list       |
| View        | `resources/views/pages/employee/payslips/show.blade.php`     | Portal: payslip detail     |
| PDF         | `resources/views/pdf/payslip.blade.php`                      | Payslip PDF template       |
| PDF         | `resources/views/pdf/payroll-liability-report-pdf.blade.php` | Liability report PDF       |
| PDF         | `resources/views/pdf/payroll-summary-report.blade.php`       | Summary report PDF         |
| Email       | `resources/views/emails/payslip.blade.php`                   | Payslip email template     |

---

---

# PART 2: USER GUIDE

---

## 1. Getting Started with Payroll

### 1.1 Initial Setup Checklist

Complete these steps in order before running your first payroll:

| Step | What to Do                           | Where to Go                              |
|------|--------------------------------------|------------------------------------------|
| 1    | Create pay items (earnings/deductions)| Payroll > Pay Items                      |
| 2    | Set up tax brackets                  | Payroll > Tax Brackets                   |
| 3    | Configure employee pay structures    | Payroll > Employee Pay                   |
| 4    | Ensure accounting accounts exist     | Accounting > Chart of Accounts           |

---

## 2. Pay Items (Earnings & Deductions)

### 2.1 What Are Pay Items?

Pay items are the building blocks of payroll. They define every type of earning or deduction that can appear on a payslip.

**Three types:**
| Type              | Purpose                              | Effect on Net Pay |
|-------------------|--------------------------------------|-------------------|
| **Earning**       | Money paid to employee               | Increases         |
| **Deduction**     | Money taken from pay                 | Decreases         |
| **Reimbursement** | Expense reimbursement                | Increases         |

### 2.2 Creating Pay Items

**Navigation:** Payroll > Pay Items > Click **"Add Pay Item"**

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Name**           | Item name (e.g., "Basic Salary", "Income Tax")        | Yes      |
| **Type**           | Earning, Deduction, or Reimbursement                  | Yes      |
| **Account**        | Linked Chart of Account (for accounting integration)  | Yes      |
| **Calculation Type**| Fixed (set amount) or Percentage (of another item)   | Yes      |
| **Percentage Value**| Only if Percentage type - the % rate                 | Conditional |
| **Base Item**      | Only if Percentage type - which item to calculate from| Conditional |

### 2.3 Common Pay Item Examples

**Earnings:**
| Pay Item             | Type     | Calculation  | Example                      |
|----------------------|----------|--------------|------------------------------|
| Basic Salary         | Earning  | Fixed        | PKR 100,000/month            |
| House Rent Allowance | Earning  | 45% of Basic | PKR 45,000                   |
| Transport Allowance  | Earning  | Fixed        | PKR 10,000/month             |
| Overtime             | Earning  | Fixed        | Calculated from hourly rate  |
| Medical Allowance    | Earning  | 10% of Basic | PKR 10,000                   |

**Deductions:**
| Pay Item             | Type      | Calculation  | Example                      |
|----------------------|-----------|--------------|------------------------------|
| Income Tax           | Deduction | Fixed        | Calculated from tax brackets |
| EOBI                 | Deduction | Fixed        | PKR 1,250/month              |
| Provident Fund       | Deduction | 8.33% of Basic| PKR 8,330                   |
| Loan Repayment       | Deduction | Fixed        | PKR 5,000/month              |

---

## 3. Tax Brackets

### 3.1 Setting Up Tax Brackets

**Navigation:** Payroll > Tax Brackets

Tax brackets define how much income tax to deduct based on the employee's annual income.

**To set up FBR brackets:**
1. Click **"Seed FBR Brackets"** to auto-load Pakistan's standard tax slabs
2. Or manually create brackets by clicking **"Add Bracket"**

**Each bracket needs:**
| Field          | Description                                    |
|----------------|------------------------------------------------|
| **Fiscal Year**| e.g., "2024-2025"                              |
| **Min Income** | Annual income floor for this bracket           |
| **Max Income** | Annual income ceiling (blank for highest bracket)|
| **Base Tax**   | Fixed tax for income below this bracket        |
| **Rate**       | Tax rate (%) for income within this bracket    |

### 3.2 How Tax Is Calculated

The system uses **progressive taxation**:

**Example:** Employee earns PKR 150,000/month (PKR 1,800,000/year)

| Bracket              | Taxable Amount | Rate | Tax       |
|----------------------|----------------|------|-----------|
| 0 - 600,000         | 600,000        | 0%   | 0         |
| 600,001 - 1,200,000 | 600,000        | 5%   | 30,000    |
| 1,200,001 - 1,800,000| 600,000       | 15%  | 90,000    |
| **Annual Tax**       |                |      | **120,000** |
| **Monthly Tax**      |                |      | **10,000** |

---

## 4. Employee Pay Structure

### 4.1 Setting Up Employee Salary

**Navigation:** Payroll > Employee Pay > Click **"Edit"** on an employee

This is where you define what each employee earns and what deductions apply.

**For each employee, configure:**

1. **Pay items** - Select which earnings and deductions apply
2. **Amounts** - Set the fixed amount for each item
3. **Hourly rate** - Set for overtime calculation (optional)
4. **Bank details** - For bank file generation

### 4.2 Adding Pay Items to an Employee

1. Open the employee's pay structure
2. Click **"Add Item"**
3. Select a pay item (e.g., "Basic Salary")
4. Enter the amount (e.g., PKR 100,000)
5. For percentage items, the amount auto-calculates from the base item
6. Save

### 4.3 Overtime Setup

If an employee is eligible for overtime:
1. Set their **hourly rate** in the pay structure
2. When creating a pay run, enter their overtime hours
3. The system calculates: `hourly_rate x hours x 1.5`

---

## 5. Running Payroll (Pay Runs)

### 5.1 Creating a Pay Run

**Navigation:** Payroll > Pay Runs > Click **"Create Pay Run"**

| Field              | Description                                           | Required |
|--------------------|-------------------------------------------------------|----------|
| **Period Start**   | First day of pay period (e.g., April 1)               | Yes      |
| **Period End**     | Last day of pay period (e.g., April 30)               | Yes      |
| **Payment Date**   | When employees will be paid                           | Yes      |
| **Frequency**      | Weekly, Fortnightly, or Monthly                       | Yes      |
| **Employees**      | Select which employees to include                     | Yes      |
| **Overtime Hours** | Enter overtime hours per employee (if applicable)     | No       |

### 5.2 Generating the Pay Run

1. Fill in the form and click **"Generate"**
2. The system creates payslips for each selected employee:
   - Pulls their pay structure items
   - Calculates percentage-based items
   - Calculates overtime pay
   - Calculates income tax (from tax brackets)
   - Computes YTD totals
3. You're redirected to the **Pay Run Detail** page

### 5.3 Reviewing Payslips

On the Pay Run detail page, you see every employee's payslip:

| Column              | Description                                      |
|---------------------|--------------------------------------------------|
| **Employee**        | Employee name                                    |
| **Gross Earnings**  | Total earnings (before deductions)               |
| **Deductions**      | Total deductions (tax, insurance, etc.)          |
| **Net Pay**         | Take-home pay (earnings - deductions)            |
| **YTD Net**         | Year-to-date net pay                             |

### 5.4 Adjusting Payslips (Draft Only)

While the pay run is in **Draft** status, you can adjust individual payslips:

**Add an ad-hoc item:**
- Click on a payslip
- Click **"Add Item"**
- Select a pay item and enter the amount
- Example: One-time bonus, special deduction

**Update an item amount:**
- Click the amount field on any item
- Enter the new amount
- The system recalculates totals automatically

**Remove an item:**
- Click the delete icon next to any item
- Confirm removal

### 5.5 Approval Workflow

If your organization requires approval:

1. **Submit** the pay run for approval
2. An approver reviews the payslips
3. **Approve** to allow posting, or **Reject** with notes
4. Rejected pay runs return to draft for corrections

### 5.6 Posting to Accounting

After approval (or directly from draft if no approval required):

1. Click **"Post to Accounting"**
2. The system creates journal entries:
   - **DR** Salary/Allowance expense accounts (from each pay item's linked account)
   - **CR** Tax/deduction liability accounts
   - **CR** Wages Payable (net pay)
3. Pay run status changes to **Posted**
4. Payslips can no longer be edited

### 5.7 Recording Payment

After posting:

1. Click **"Record Payment"**
2. Select the **Bank Account** the payment comes from
3. The system creates journal entries:
   - **DR** Wages Payable
   - **CR** Bank Account
4. Pay run status changes to **Paid**

### 5.8 Bank Payment CSV

For bulk bank transfers:

1. Click **"Generate Bank File"** (available after posting)
2. Downloads a CSV file with:
   - Employee name
   - Bank name
   - Account number
   - IBAN
   - Net pay amount
3. Upload this file to your banking portal for batch transfer

---

## 6. Payslip Distribution

### 6.1 Download Payslip PDF

1. Open the pay run detail page
2. Click the **PDF** icon next to any payslip
3. A professionally formatted PDF downloads with:
   - Company information and logo
   - Employee details
   - Earnings breakdown
   - Deductions breakdown
   - Net pay
   - YTD totals
   - Leave balances

### 6.2 Email Payslips

**Individual:** Click the **email** icon next to a specific payslip

**Batch (All employees):** Click **"Email All Payslips"** at the top of the pay run page

Emails are sent via a queued job (background processing) with the payslip PDF attached.

---

## 7. Payroll Liabilities

### 7.1 What Are Payroll Liabilities?

After payroll is posted, deductions (tax, insurance, provident fund) become liabilities - amounts you've withheld from employees that need to be remitted to the relevant authorities.

### 7.2 Viewing Unpaid Deductions

**Navigation:** Payroll > Liabilities

The liabilities page shows:
- All unpaid deduction items grouped by pay item type
- Amount owed per deduction type
- Which pay run each deduction came from
- Payment status (Unpaid / Paid)

### 7.3 Recording Deduction Payments

When you remit deductions to an authority (e.g., pay income tax to FBR):

1. Select the deduction items to pay
2. Click **"Record Payment"**
3. Enter payment details (date, reference, bank account)
4. Items are marked as **Paid** with date and reference

### 7.4 Liability Report

Click **"Report"** to see a comprehensive liability summary. Export to **Excel** or **PDF**.

---

## 8. Dashboard & Reports

### 8.1 Payroll Dashboard

**Navigation:** Payroll > Dashboard

The dashboard shows at-a-glance KPIs:

| KPI                    | Description                                  |
|------------------------|----------------------------------------------|
| **Latest Gross**       | Total gross earnings from last pay run       |
| **Latest Deductions**  | Total deductions from last pay run           |
| **Latest Net Pay**     | Total net pay from last pay run              |
| **Active Employees**   | Number of employees on payroll               |
| **Pending Approvals**  | Pay runs awaiting approval                   |

**Charts:**
- **Monthly Payroll Trend** - Last 6 months of gross, deductions, net pay
- **Pay Run Status** - Breakdown of draft, posted, paid pay runs
- **Top Pay Items** - Highest-value earnings and deductions

### 8.2 Payroll Summary Report

**Navigation:** Payroll > Reports > Summary

Detailed payroll analysis:
- Department-wise breakdown of payroll costs
- Top pay items by total amount
- Compare current vs previous pay run (period-over-period)
- Export to **PDF**

---

## 9. Employee Self-Service Portal

### 9.1 Overview

Employees can access their own payslips through a secure self-service portal without needing access to the main ERP system.

### 9.2 Employee Login

**URL:** `/my/login`

Employees log in with their email and portal password (set by HR in the Employee Pay configuration).

### 9.3 Available Features

After logging in, employees can:

| Feature              | Description                                  |
|----------------------|----------------------------------------------|
| **View Payslips**    | See all their past payslips                  |
| **Payslip Detail**   | View breakdown of earnings and deductions    |
| **Download PDF**     | Download any payslip as a PDF                |
| **Leave Balances**   | View current leave balance information       |

**Important:** Employees can **only** see their own payslips. They cannot access any other employee's data.

---

## 10. Complete Payroll Workflow (Step-by-Step)

Here's the complete end-to-end process:

```
1. ONE-TIME SETUP
   ├── Create pay items (earnings + deductions)
   ├── Set up tax brackets
   └── Configure each employee's pay structure

2. MONTHLY PAYROLL CYCLE
   ├── Create pay run (select period + employees)
   ├── Enter overtime hours (if any)
   ├── Generate payslips
   ├── Review and adjust (while in draft)
   ├── Submit for approval (if required)
   ├── Post to accounting (creates journal entries)
   ├── Record payment (bank disbursement)
   ├── Generate bank file (for batch transfer)
   └── Email payslips to employees

3. POST-PAYROLL
   ├── Review payroll liabilities
   ├── Remit deductions to authorities (tax, insurance)
   ├── Record deduction payments
   ├── Generate summary report
   └── Employees view payslips in self-service portal
```

---

## 11. Frequently Asked Questions

**Q: Can I run payroll weekly?**
A: Yes. When creating a pay run, select "Weekly" as the frequency. You can also use "Fortnightly" or "Monthly".

**Q: What happens if I made a mistake in a posted pay run?**
A: You can **delete** the pay run, which automatically reverses all accounting entries. Then create a corrected pay run.

**Q: Can I add a one-time bonus to a specific employee?**
A: Yes. In the pay run detail page (while still in Draft), add an ad-hoc earning item to that employee's payslip.

**Q: How is overtime calculated?**
A: Overtime = Hourly Rate x Hours x 1.5. The hourly rate is set in the employee's pay structure.

**Q: Can employees see other employees' payslips?**
A: No. The employee portal uses a separate authentication guard and only shows the logged-in employee's own payslips.

**Q: How do I handle a salary advance/loan deduction?**
A: Create a "Loan Repayment" pay item (type: Deduction) and add it to the employee's pay structure with the monthly repayment amount.

**Q: What if tax brackets change mid-year?**
A: Create new tax brackets for the new fiscal year. The system uses the fiscal_year field to apply the correct brackets.

**Q: Can I generate payslips without posting to accounting?**
A: Yes. Payslips are created in Draft status. You only post to accounting when you're ready.

**Q: How do I set up a new employee for payroll?**
A: Go to Payroll > Employee Pay, find the employee, click Edit, add their pay items with amounts, and save.

**Q: What accounts do I need in Chart of Accounts for payroll?**
A: At minimum: Salary Expense, each allowance expense, Income Tax Payable, EOBI Payable, Wages Payable, and your Bank account. Link these to the corresponding pay items.
