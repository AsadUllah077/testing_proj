# Payroll Review and Feature Roadmap

> Implementation status (2026-08-11): the identified corrections, Payroll Control Center, salary-advance planner, final settlement, supplementary payroll, controlled reversal, cost allocation, scenario simulator, employee portal additions, compensation review, variance explanations, and audit pack are implemented. See `docs/payroll-control-suite-guide.md` for operating steps and example entries.

## Current Module Coverage

The payroll module already includes:

- Employee pay structures
- Earnings, deductions, and reimbursement pay items
- Weekly, fortnightly, and monthly pay runs
- Pay-run approval and accounting posting
- Payroll payment and bank-file export
- Individual and bulk payslip downloads and email
- Payroll anomaly detection
- Income-tax brackets and automatic tax calculation
- Salary advances integrated with accounting
- Payroll liabilities and statutory-body payments
- Statutory compliance calendar and alerts
- Employee payroll history and month comparison
- Payroll calendar, analytics, forecasting, and overtime reports

## Important Findings

### 1. Payroll Tax Can Use the Wrong Fiscal Year

Payroll generation calls the tax service without explicitly providing the fiscal year of the pay run. When no fiscal year is supplied, the tax service resolves the fiscal year using the current system date.

This can produce an incorrect tax calculation when processing:

- Historical payroll
- Future payroll
- Delayed payroll
- Payroll near a fiscal-year boundary

Required correction:

- Resolve the fiscal year from the pay run's `period_end` or `payment_date`.
- Pass that fiscal year explicitly to every payroll tax calculation.
- Store the fiscal year and tax-bracket version used on the pay run or payslip for auditability.

Relevant files:

- `app/Services/PayrollService.php`
- `app/Services/TaxCalculationService.php`

### 2. Salary Advances Are Recovered in Full

The current payroll calculation adds the complete outstanding `remaining_amount` of an issued salary advance as the next payroll deduction.

Possible consequences:

- Very low or negative net salary
- No agreed installment schedule
- No maximum deduction percentage
- Poor employee recovery reporting

Required correction:

- Add installment amount or recovery percentage.
- Add recovery start date and expected completion date.
- Limit recovery so net pay does not fall below an approved threshold.
- Allocate every recovery to the correct advance and payroll.

Relevant file:

- `app/Services/PayrollService.php`

### 3. Payroll Deductions Can Be Paid Before Posting

The deduction-payment service validates the selected items and company, but it does not require every related pay run to be in `posted` or `paid` status.

This could pay tax, pension, or another statutory liability before the payroll liability has been posted to the general ledger.

Required correction:

- Require all selected items to belong to posted or paid pay runs.
- Reject draft, cancelled, or rejected payroll items.
- Lock selected items during payment to prevent duplicate payment.
- Verify the liability-account balance before creating the payment journal.

Relevant files:

- `app/Http/Controllers/Payroll/PayRunController.php`
- `app/Services/PayrollService.php`
- `app/Http/Controllers/Payroll/PayrollLiabilityController.php`

### 4. Payslip Adjustments Do Not Recalculate Tax or YTD

When a draft payslip item is added, edited, or removed, the system recalculates gross earnings, deductions, and net pay. It does not recalculate income tax or year-to-date values.

Example:

An accountant increases a taxable allowance by 100,000. Gross and net pay change, but income tax may remain based on the previous taxable earnings.

Required correction:

- Recalculate taxable earnings.
- Recalculate income tax using the pay-run fiscal year.
- Recalculate gross, deductions, net pay, and YTD totals together.
- Rerun anomaly detection after each draft adjustment.

Relevant file:

- `app/Http/Controllers/Payroll/PayslipController.php`

### 5. Overlapping Pay Runs Are Not Prevented

Pay-run creation validates the dates but does not reject another pay run covering the same employees and overlapping payroll dates.

Possible consequences:

- Duplicate salary expense
- Duplicate tax deductions
- Duplicate salary payments
- Incorrect YTD figures

Required correction:

- Detect overlapping pay periods by company, frequency, and employee.
- Block duplicate active pay runs.
- Allow an override only through a controlled supplementary or correction payroll type.

Relevant file:

- `app/Http/Controllers/Payroll/PayRunController.php`

### 6. Some Employee Validation Is Not Company-Scoped

Some requests use validation such as `exists:employees,id` without requiring the employee to belong to the logged-in company.

Required correction:

- Use a company-scoped `Rule::exists()` for every employee, pay run, payslip, pay item, and account reference.
- Confirm parent-child ownership, such as ensuring a payslip belongs to the pay run in its URL.

Areas to review:

- Salary advances
- Bulk employee pay updates
- Bulk payslip downloads
- Payslip email and download routes

### 7. Critical Payroll Anomalies Do Not Block Posting

The anomaly detector identifies missing items, unusual gross or net pay, deduction caps, overtime spikes, YTD differences, and zero or negative net pay. These findings are currently advisory.

Required correction:

- Block approval or posting when critical anomalies exist.
- Allow warnings to proceed with a documented explanation.
- Add an authorized override with user, reason, date, and audit log.
- Recheck anomalies immediately before posting.

Relevant files:

- `app/Services/Payroll/PayrollAnomalyDetector.php`
- `app/Http/Controllers/Payroll/PayRunController.php`
- `resources/views/pages/payroll/pay-run/show.blade.php`

## Best New Feature: Payroll Control Center

The highest-value addition is a Payroll Control Center with a mandatory preflight gate before approval and accounting posting.

### Recommended Workflow

```text
Draft -> Calculated -> Reviewed -> Approved -> Posted -> Salary Paid -> Liabilities Settled
```

Each transition should be recorded with the responsible user and timestamp.

### Payroll Preflight Checks

#### Employee Readiness

- Employee is active for the payroll period.
- Joining and leaving dates are respected.
- Bank name, account number, and IBAN are complete.
- Employee has a valid pay structure.
- Required tax and identity information is available.
- No employee appears twice in overlapping payroll runs.

#### Calculation Readiness

- Every payslip has earnings or a documented zero-pay reason.
- Gross earnings equal the sum of earning items.
- Deductions equal the sum of deduction items.
- Net pay equals gross less deductions.
- Taxable earnings and income tax are recalculated.
- YTD gross, deductions, tax, and net pay are continuous.
- No unexplained negative net salary exists.
- Salary-advance recovery follows the approved installment schedule.

#### Accounting Readiness

- Every pay item has a company-owned chart-of-account mapping.
- Wages Payable and Employee Advances system accounts exist.
- The accounting period is open.
- The proposed payroll journal balances.
- The proposed payroll reference does not already exist.
- Payroll liabilities equal payslip deductions.
- The bank account belongs to the current company.

#### Approval Readiness

- No critical anomaly remains unresolved.
- Warnings contain review notes.
- The creator and approver are different users.
- Approval is invalidated when a draft payslip changes.
- Posting performs the complete preflight check again.

### Control Center Screen

The screen should show:

- Current payroll stage
- Employee readiness exceptions
- Calculation exceptions
- Accounting mapping exceptions
- Critical anomalies and warnings
- Previous-month variance
- Payroll journal preview
- Salary-payment status
- Outstanding statutory liabilities
- Approval and override history

### Safety Rules

- The Control Center should not silently correct payroll values.
- Critical errors should block approval and posting.
- Overrides should require a permission and written reason.
- Posted payroll should be corrected through reversal or supplementary payroll, not direct editing.

## Other Valuable Features

### 1. Employee Loan and Salary-Advance Planner

Add:

- Installment amount
- Installment frequency
- Recovery start date
- Maximum deduction percentage
- Remaining installment schedule
- Early settlement
- Pause or reschedule approval
- Employee advance statement
- Accounting reconciliation between Employee Advances and outstanding schedules

Example:

An employee receives an advance of 600,000 and agrees to six monthly deductions of 100,000. Each posted payroll applies one installment and updates the remaining balance.

### 2. Final Settlement

Create a controlled settlement when an employee resigns or is terminated.

The calculation should include:

- Salary through the final working date
- Unpaid overtime
- Leave encashment
- Gratuity or end-of-service benefit
- Notice pay or notice deduction
- Bonus or commission due
- Outstanding salary advance or loan
- Tax adjustment
- Other recoveries
- Final net payment
- Final accounting journal

The employee should receive a settlement statement, and HR, payroll, and finance should approve it separately.

### 3. Retroactive and Supplementary Payroll

Support changes effective from an earlier period, such as:

- Backdated salary increase
- Missed allowance
- Overtime correction
- Tax correction
- Deduction correction
- Bonus paid outside the normal payroll

The system should calculate only the difference between the original and corrected amounts and post a linked supplementary payroll.

### 4. Payroll Reversal Workflow

Posted or paid payroll should not be directly deleted.

A controlled reversal should:

- Link to the original pay run
- Reverse the payroll journal
- Reverse salary-payment entries when applicable
- Restore payroll liabilities
- Restore salary-advance balances correctly
- Mark original payslips as reversed
- Preserve the complete audit history

### 5. Payroll Cost Allocation

Allocate payroll expenses and liabilities by:

- Department
- Branch
- Project
- Cost center
- Location
- Funding source

Example:

An employee works 60% on Project A and 40% on Project B. Salary expense, employer contributions, and related payroll costs should follow the same allocation.

### 6. Payroll Scenario Simulator

Allow managers to model payroll changes without modifying live pay structures.

Scenarios can include:

- Percentage salary increase
- New hiring plan
- Department expansion
- Bonus pool
- Tax-bracket change
- Overtime increase
- Allowance restructuring
- Employee turnover

Results should show monthly and annual gross payroll, tax, net payroll, employer cost, department cost, and budget variance.

### 7. Employee Payroll Portal

Employees should be able to view:

- Current and historical payslips
- YTD gross, tax, deductions, and net pay
- Tax certificate
- Salary-advance statement
- Leave balances shown on payslips
- Bank details awaiting confirmation
- Payroll questions and payslip explanations

Sensitive bank-detail changes should require verification and payroll approval.

### 8. Compensation Review Cycle

Add an annual or periodic salary-review workflow:

- Current salary
- Market benchmark
- Manager recommendation
- HR recommendation
- Approved increase
- Effective date
- Budget impact
- Increment letter
- Automatic future pay-structure update

### 9. Payroll Variance Explanation

For each employee, automatically show why payroll changed from the previous period.

Example:

| Reason | Difference |
|---|---:|
| Basic salary increase | +50,000 |
| Overtime | +20,000 |
| Higher income tax | -8,000 |
| Salary-advance installment | -25,000 |
| Net difference | +37,000 |

The reviewer should be able to confirm or comment on material variances.

### 10. Payroll Audit Pack

Generate a single month-end payroll pack containing:

- Pay-run summary
- Employee payroll register
- Payroll variance report
- Approval history
- Anomaly report and overrides
- Bank-payment file total
- Payroll journal
- Liability schedule
- Tax and statutory payment status
- Employee advance reconciliation

This provides a complete audit file for management, internal audit, and external auditors.

## Recommended Implementation Order

1. Correct fiscal-year tax selection.
2. Block deduction payments for unposted payroll.
3. Recalculate tax and YTD after draft payslip edits.
4. Add company-scoped validation and parent-child ownership checks.
5. Prevent duplicate or overlapping pay runs.
6. Add salary-advance installments and deduction limits.
7. Make critical payroll anomalies block approval and posting.
8. Build the Payroll Control Center and preflight gate.
9. Add final settlement and controlled payroll reversal.
10. Add retro payroll, cost allocation, scenario planning, and the audit pack.

## Accounting Principles

Every new payroll feature should preserve these rules:

- Salary expense and liabilities are recognized when payroll is posted.
- Bank or cash is reduced only when salary or a liability is paid.
- Every journal remains balanced.
- Posted payroll is immutable and corrected through linked reversals or supplementary payroll.
- Employee, account, pay item, and pay-run references are company-scoped.
- Payroll liability balances reconcile to unpaid payslip deductions.
- Employee Advances reconcile to outstanding employee recovery schedules.
- Tax calculations retain the fiscal year and bracket version used.
- Every approval, override, posting, payment, and reversal is auditable.
