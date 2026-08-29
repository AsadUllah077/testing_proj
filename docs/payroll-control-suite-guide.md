# Payroll Control Suite: Operating Guide and Example Entries

## Purpose

This guide explains the payroll controls and workflows added to the ERP. Use the examples as training data in a new test company before using the workflows in production.

## Navigation

- **Payroll > Payroll Control Center**: mandatory payroll preflight, warning review, and journal preview.
- **Payroll > Payroll Operations**: final settlements, cost allocations, scenarios, compensation reviews, payroll variance, bank requests, and audit packs.
- **Payroll > Salary Advances**: installment schedules, issue, pause, reschedule, recovery, and early settlement.
- **Payroll > Pay Runs**: regular, supplementary, payment, reversal, payslips, and accounting posting.
- **Employee portal > My Payslips**: payslips, fiscal YTD, tax certificate, salary-advance statement, and bank-change requests.

## Required Setup

Before processing payroll:

1. Create an open accounting period that covers the payroll payment date.
2. Confirm the company has `Wages Payable` and `Employee Advances` system accounts.
3. Map every pay item to a chart-of-account owned by the same company.
4. Create earning accounts such as Basic Salary Expense, Allowance Expense, Overtime Expense, and Bonus Expense.
5. Create deduction liability accounts such as Income Tax Payable, Pension Payable, and Employee Insurance Payable.
6. Complete each employee's joining/leaving dates, bank details, salary, and pay structure.
7. Use different users for preparation and approval.

## Example Chart of Accounts

| Account | Category | Payroll use |
|---|---|---|
| 6100 Basic Salary Expense | Expense | Basic salary earning |
| 6110 Allowance Expense | Expense | Taxable/non-taxable allowances |
| 6120 Overtime Expense | Expense | Overtime earning |
| 6130 Bonus Expense | Expense | Bonus and supplementary bonus |
| 1410 Employee Advances | Asset | Advance issue, recovery, and employee receivable |
| 2210 Wages Payable | Liability | Net salary payable |
| 2220 Income Tax Payable | Liability | Employee income-tax deduction |
| 2230 Pension Payable | Liability | Employee pension deduction |
| 1010 Main Bank | Asset | Salary, liability, and advance payments |

All account IDs are selected from the UI. Never import or type another company's `company_id` or account ID.

## 1. Employee Pay Example

Employee: **Ayesha Khan (EMP-0001)**

| Pay item | Type | Taxable | Amount | Account |
|---|---|---:|---:|---|
| Basic Salary | Earning | Yes | 500,000 | 6100 Basic Salary Expense |
| House Allowance | Earning | Yes | 50,000 | 6110 Allowance Expense |
| Medical Allowance | Earning | No, if legally exempt | 20,000 | 6110 Allowance Expense |
| Income Tax | Deduction | No | Automatic | 2220 Income Tax Payable |
| Pension | Deduction | No | 15,000 | 2230 Pension Payable |

The income-tax amount is recalculated from taxable earnings using the fiscal year containing the pay run's period end. The fiscal year and calculation snapshot are retained on the payslip.

## 2. Regular Payroll

Example:

- Period start: `2026-07-01`
- Period end: `2026-07-31`
- Payment date: `2026-08-05`
- Frequency: Monthly
- Employees: Ayesha Khan and other eligible employees

Steps:

1. Open **Payroll > Pay Runs > Create**.
2. Enter the dates and select employees.
3. Generate the draft pay run.
4. Review every payslip. Editing a taxable line recalculates income tax, gross, deductions, net, and fiscal YTD and invalidates prior approval.
5. Open **Control Center** from the pay-run page.
6. Run preflight.
7. Correct all blockers. A blocker cannot be overridden.
8. Review each warning and enter a written reason where proceeding is justified.
   For a large run with the same documented review conclusion, use **Review All Warnings**; the system still writes one audit record per warning. Blockers are never included.
9. A user other than the creator approves the pay run.
10. Post to accounting. Preflight runs again immediately before posting.
11. Record salary payment from the company bank account.
12. Pay statutory deductions only after payroll is posted.

Example payroll journal:

| Account | Debit | Credit |
|---|---:|---:|
| Basic Salary Expense | 500,000 | 0 |
| Allowance Expense | 70,000 | 0 |
| Income Tax Payable | 0 | 45,000 |
| Pension Payable | 0 | 15,000 |
| Wages Payable | 0 | 510,000 |
| **Total** | **570,000** | **570,000** |

Salary payment journal:

| Account | Debit | Credit |
|---|---:|---:|
| Wages Payable | 510,000 | 0 |
| Main Bank | 0 | 510,000 |

## 3. Payroll Control Center

Preflight checks:

- Employee company, join/leave dates, bank details, and pay structures.
- Overlapping regular payroll.
- Payslip line totals, negative net pay, and anomaly thresholds.
- Exact salary-advance links.
- Company-owned pay-item account mappings.
- Wages Payable and Employee Advances accounts.
- Open accounting period on the posting/payment date.
- Balanced proposed payroll journal.
- Maker-checker approval.

`Blocker` means source data must be corrected. `Warning` requires documented review. `Pass` requires no action.

## 4. Salary Advance Example

Advance to Ayesha: `600,000`

- Advance date: `2026-08-10`
- Recovery method: Fixed installment
- Recovery frequency: Once per month
- Installment: `100,000`
- Maximum net deduction: `50%`
- Recovery start: `2026-09-01`
- Expected installments: 6

Steps:

1. Create the advance in **Payroll > Salary Advances**.
2. A reviewer approves it.
3. Issue it from Main Bank.
4. Accounting posts Debit Employee Advances / Credit Main Bank for `600,000`.
5. Each eligible posted payroll applies the scheduled installment to that exact advance ID.
6. Pause or reschedule recovery when formally approved outside the system's payroll permission process.
7. Use **Settle** if the employee repays early into bank/cash.

Payroll recovery credits Employee Advances instead of a liability. Reversal restores the exact recovery and installment count.

## 5. Supplementary Payroll

Example: a July overtime amount of `20,000` was omitted.

1. Open the posted July pay run.
2. Select **Supplementary**.
3. The linked supplementary run starts with zero payslips.
4. Add only `Overtime = 20,000` for the affected employee.
5. Review tax and net adjustment.
6. Run preflight, approve, and post normally.

Do not enter the employee's full monthly salary in a supplementary run. Enter only differences.

## 6. Payroll Reversal

Use reversal when a posted payroll is fundamentally wrong.

1. Reverse paid statutory liabilities first.
2. Open the posted or paid pay run and select **Reverse**.
3. Enter an open-period reversal date and a specific reason.
4. The system creates opposite entries for payroll and salary payment transactions.
5. Salary-advance recoveries are restored.
6. The original pay run becomes cancelled/reversed but remains in audit history.
7. Create a corrected regular or supplementary payroll.

Never delete or directly edit posted payroll.

## 7. Final Settlement Example

Employee last day: `2026-09-15`; settlement date: `2026-09-20`.

| Description | Type | Amount | Account |
|---|---|---:|---|
| Salary through last day | Earning | 250,000 | Basic Salary Expense |
| Leave encashment | Earning | 75,000 | Allowance Expense |
| Gratuity | Earning | 300,000 | Gratuity Expense |
| Final income tax | Deduction | 40,000 | Income Tax Payable |
| Advance recovery | Deduction | 100,000 | Employee Advances |

Gross `625,000`, deductions `140,000`, net settlement `485,000`.

Approval order is mandatory: HR review, payroll review, finance approval. Each stage requires a different user, and the creator cannot approve. Finance then posts the balanced settlement journal. If deductions exceed earnings, the amount due from the employee is debited to Employee Advances instead of creating negative Wages Payable.

## 8. Cost Allocation

Example for employee gross pay `500,000`:

| Department | Project | Cost center | Percentage | Allocated gross |
|---|---|---|---:|---:|
| Consulting | Project A | CC-A | 60% | 300,000 |
| Implementation | Project B | CC-B | 40% | 200,000 |

1. Open **Payroll Operations > Cost Allocation**.
2. Add effective-dated rows totaling exactly 100%.
3. Optionally enter branch, project, location, and funding source.
4. Select a posted pay run in the allocated payroll report.

The report is a management allocation schedule; the core payroll GL remains controlled by pay-item accounts.

## 9. Scenario Simulator

Example assumptions:

- Salary increase: `10%`
- New hires: `5`
- Average new-hire salary: `300,000`
- Bonus pool: `2,000,000`
- Turnover: `5%`
- Overtime increase: `8%`
- Allowance increase: `3%`
- Tax change: `2%`
- Employer cost: `7%`
- Annual budget: `100,000,000`

Results show projected monthly/annual gross, tax, net, employer cost, headcount, and budget variance. Scenarios never modify live employee pay.

## 10. Compensation Review

Example:

- Current salary: loaded automatically
- Market benchmark: `620,000`
- Proposed salary: `600,000`
- Effective date: `2026-10-01`
- Manager recommendation: performance and expanded responsibilities
- HR recommendation: supported by grade and market benchmark

A different user approves the final amount. On or after the effective date, **Apply** updates employee salary and the Basic Salary pay-structure item. Download the increment letter from the review row.

## 11. Payroll Variance and Audit Pack

1. Open **Payroll Operations > Variance**.
2. Select a posted pay run.
3. Review automatic pay-item reasons against the previous period.
4. Enter a written explanation for material changes.
5. Download **Audit Pack**.

The pack contains payroll totals, preflight findings, journal entries, approval evidence, variance explanations, deduction liability status, and employee-advance reconciliation.

## 12. Employee Portal

Employees can:

- View/download current and historical payslips.
- See fiscal YTD gross, deductions, and net.
- Download a July-to-June tax certificate.
- See advance balances and installment progress.
- Submit bank-detail changes for payroll approval.
- Ask the payslip assistant about earnings and deductions.

Bank details are changed only after payroll reviews and approves the request.

## Production Checklist

Before first production use:

1. Take a database backup.
2. Run migrations in staging and complete one full payroll cycle.
3. Reconcile payroll journal debit and credit.
4. Reconcile Wages Payable to unpaid net salaries.
5. Reconcile deduction liabilities to unpaid payslip deductions.
6. Reconcile Employee Advances to outstanding schedules.
7. Test creator/approver separation with real roles.
8. Test supplementary payroll and reversal in an open period.
9. Confirm bank and statutory payment files with the receiving institutions.
10. Restrict payroll approval, posting, reversal, and employee-pay permissions to authorized users.

## Migration Commands

```bash
php artisan migrate --path=database/migrations/2026_08_11_000002_create_payroll_control_suite.php --force
php artisan migrate --path=database/migrations/2026_08_11_000003_extend_payroll_control_workflows.php --force
php artisan view:cache
```

The migrations are additive. Existing posted payroll is not recalculated automatically.
