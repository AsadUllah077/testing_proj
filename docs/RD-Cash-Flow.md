# R&D Document: Cash Flow Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Cash Flow Statements & Forecast                           |
| **Parent Module**  | Accounting > Reports                                      |
| **Purpose**        | Provide comprehensive cash flow visibility through three views: (1) Indirect Method Statement, (2) Direct Method Statement, and (3) Short-term Cash Flow Forecast with interactive charts. |
| **Target Users**   | CFOs, Finance Managers, Accountants, Financial Analysts    |
| **Permission**     | `account.reports.cash-flow`                               |

---

## 2. Problem Statement

Understanding where cash comes from and where it goes is critical for business survival. Organizations need:
- A clear picture of cash generated/consumed by operations, investments, and financing
- Multi-period comparison to identify trends
- Short-term forecasting to predict cash shortages or surpluses
- Both direct and indirect method statements for different stakeholder needs

---

## 3. Solution & Approach

The module provides **three distinct views**:

| View                    | Controller                     | Purpose                                    |
|-------------------------|--------------------------------|--------------------------------------------|
| **Indirect Method**     | `CashFlowController`          | Net income + adjustments approach           |
| **Direct Method**       | `CashFlowDirectController`    | Actual cash receipt/payment approach        |
| **Cash Flow Forecast**  | `CashFlowForecastController`  | Short-term projection (30-180 days)        |

### 3.1 Architecture

| Layer        | Component                                                |
|--------------|----------------------------------------------------------|
| **Controllers** | `CashFlowController`, `CashFlowDirectController`, `CashFlowForecastController` |
| **Models**   | `Transaction`, `Sale`, `Bill`, `ChartOfAccount`, `AccountingPeriod` |
| **Views**    | `cash-flow.blade.php`, `cash-flow-direct.blade.php`, `cash-flow-forecast/index.blade.php` |
| **Partials** | `partials/cash-flow-table.blade.php`, `partials/cash-flow-direct-table.blade.php` |
| **PDF**      | `pdf/cash-flow-pdf.blade.php`, `pdf/cash-flow-direct-pdf.blade.php` |
| **JS**       | Chart.js integration in forecast view                    |

### 3.2 Data Sources

The cash flow module does **not** have its own database table. It reads from existing tables:

| Table              | Usage                                               |
|--------------------|------------------------------------------------------|
| `transactions`     | All journal entries (debits/credits) by account/date |
| `sales`            | Outstanding receivables for AR forecast              |
| `bills`            | Outstanding payables for AP forecast                 |
| `chart_of_accounts`| Account classification for categorization            |
| `accounting_periods`| Period boundaries for multi-period comparison       |

---

## 4. Cash Flow Statement - Indirect Method

### 4.1 Calculation Flow

The indirect method starts with Net Profit and adjusts for non-cash items and working capital changes.

```
OPERATING ACTIVITIES
├── Net Profit (Revenue - COGS - Expenses)
├── + Non-Cash Expenses (Depreciation & Amortization)
├── Working Capital Changes:
│   ├── Change in Accounts Receivable
│   ├── Change in Inventory
│   └── Change in Accounts Payable
└── = Net Cash from Operating Activities

INVESTING ACTIVITIES
├── Fixed Asset Purchases/Sales
├── Investment Purchases/Sales
└── = Net Cash from Investing Activities

FINANCING ACTIVITIES
├── Loan Proceeds/Repayments
├── Equity Issuance
├── Dividend Payments
└── = Net Cash from Financing Activities

NET CASH MOVEMENT = Operating + Investing + Financing
CLOSING BALANCE = Opening Balance + Net Cash Movement
```

### 4.2 Net Profit Calculation

```
Revenue    = SUM(credit) - SUM(debit) for Revenue accounts
COGS       = SUM(debit) - SUM(credit) for COGS accounts
Expenses   = SUM(debit) - SUM(credit) for Expense accounts
Net Profit = Revenue - COGS - Expenses
```

### 4.3 Working Capital Change Formulas

| Item                  | Formula                                              |
|-----------------------|------------------------------------------------------|
| Change in AR          | Ending AR balance - Beginning AR balance             |
| Change in Inventory   | Ending Inventory - Beginning Inventory               |
| Change in AP          | Ending AP balance - Beginning AP balance             |

- Increase in AR = cash **outflow** (customers owe more)
- Increase in AP = cash **inflow** (you owe more, holding cash longer)
- Increase in Inventory = cash **outflow** (purchased more stock)

### 4.4 Account Classification for Cash Flow Categories

| Cash Flow Section   | Account Identification                                    |
|---------------------|-----------------------------------------------------------|
| Bank/Cash           | Assets category, name LIKE '%Bank%' or '%Cash%', or account_number 1000-1099 |
| Fixed Assets        | Equipment, Property, Vehicle keywords                     |
| Investments         | Investment keyword in name                                |
| Loans               | Loan, Borrowing, Note Payable keywords                    |
| Equity              | Share Capital, Common Stock keywords                      |
| Dividends           | Dividend keyword in name                                  |

### 4.5 Multi-Period Comparison

The report supports comparing across multiple periods:

| Period Type    | How Periods Are Calculated                        |
|----------------|---------------------------------------------------|
| **Month**      | Each calendar month within date range             |
| **Quarter**    | Q1 (Jan-Mar), Q2 (Apr-Jun), Q3 (Jul-Sep), Q4 (Oct-Dec) |
| **Year**       | Calendar years                                    |
| **Fiscal Year**| Based on accounting period definitions            |

Variance columns show both **absolute** (amount) and **relative** (%) change between periods.

---

## 5. Cash Flow Statement - Direct Method

### 5.1 Key Difference from Indirect

The direct method traces **actual cash transactions** rather than adjusting net income. It groups actual receipts and payments.

### 5.2 Operating Activities (Direct)

```
OPERATING ACTIVITIES (DIRECT)
├── Cash Received from Customers
│   (transactions where offset account = Revenue/AR)
├── Cash Paid to Suppliers
│   (transactions where offset account = COGS/AP)
├── Payroll / Salaries
│   (transactions where offset = Payroll/Salary accounts)
├── Operating Expenses Paid
│   (other expense account transactions)
└── = Net Cash from Operating Activities
```

### 5.3 Transaction Grouping Logic

The direct method groups transactions by `unique_no` to find offsetting entries:
1. Find all transactions with the same `unique_no`
2. Identify the cash side (debit/credit to Bank/Cash accounts)
3. Identify the offset side (the other account in the pair)
4. Categorize based on the offset account type

---

## 6. Cash Flow Forecast

### 6.1 Forecast Methodology

| Component        | Data Source                                          |
|------------------|------------------------------------------------------|
| **Current Cash** | Sum of all transactions in Bank/Cash accounts        |
| **AR Inflows**   | Outstanding sales (`remaining_amount > 0`), projected with 30-day payment terms |
| **AP Outflows**  | Outstanding bills (`remaining_amount > 0`), projected with 30-day payment terms |

### 6.2 Weekly Bucketing

The forecast aggregates data into weekly buckets:
```sql
DATE_TRUNC('week', projected_date) AS week_start
```

For each week:
```
Weekly Inflow  = Sum of AR items expected that week
Weekly Outflow = Sum of AP items expected that week
Net Flow       = Inflow - Outflow
Running Balance = Previous Balance + Net Flow
```

### 6.3 Forecast Horizons

Supported horizons: **30, 60, 90, 180 days**

### 6.4 Chart.js Visualization

The forecast renders a combination chart:
- **Green bars** = AR Inflows (expected cash in)
- **Red bars** = AP Outflows (expected cash out, displayed as negative)
- **Blue line** = Running balance with fill area

### 6.5 Interactive Transaction Table

Below the chart, a paginated table shows individual items:

| Column       | Description                                        |
|--------------|----------------------------------------------------|
| Reference    | Invoice/bill number                                |
| Type         | AR Inflow or AP Outflow (color-coded badges)       |
| Date         | Expected receipt/payment date                      |
| Amount       | Formatted with +/- sign                            |

Features: AJAX pagination (15-1000 items/page), sortable columns, click-to-sort headers.

---

## 7. Export Capabilities

| Format | Available On               | Layout                          |
|--------|----------------------------|---------------------------------|
| **PDF** | Indirect + Direct methods | Portrait (<=2 periods) or Landscape (>2 periods) |
| **CSV** | Indirect + Direct methods | Flat file with all periods      |

PDF rendering uses **DomPDF** (Barryvdh package).

---

## 8. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Chart of Accounts     | Account classification drives categorization         |
| Transactions          | Source of all actual cash flow data                   |
| Sales Module          | Outstanding AR for forecast                          |
| Bills Module          | Outstanding AP for forecast                          |
| Accounting Periods    | Period boundaries for multi-period comparison         |
| Fixed Assets          | Investing activities from depreciation transactions  |
| KPI Dashboard         | Free cash flow calculated in accounting-kpi.js       |
| DomPDF               | PDF export rendering                                 |
| Chart.js             | Forecast visualization                               |

---

## 9. Technical Decisions & Trade-offs

| Decision                              | Rationale                                                    |
|---------------------------------------|--------------------------------------------------------------|
| No dedicated table                    | Cash flow is derived from existing transaction data          |
| Two statement methods                 | Direct = detail, Indirect = standard; both needed            |
| SQL aggregation (not Eloquent)        | Performance: SUM() at DB level, not loading all rows         |
| PostgreSQL DATE_TRUNC for weekly buckets | Efficient grouping; no application-level date math        |
| UNION ALL for AR+AP forecast items    | Single query combining sales and bills for pagination        |
| Chart.js (not D3)                     | Simpler, lighter, sufficient for bar/line combo chart        |
| 30-day default payment terms          | Reasonable assumption; matches typical business terms        |

---

## 10. File Manifest

| File                                                                  | Purpose                          |
|-----------------------------------------------------------------------|----------------------------------|
| `app/Http/Controllers/Account/CashFlowController.php`                | Indirect method controller       |
| `app/Http/Controllers/Account/CashFlowDirectController.php`          | Direct method controller         |
| `app/Http/Controllers/Account/CashFlowForecastController.php`        | Forecast controller              |
| `resources/views/pages/account/cash-flow.blade.php`                   | Indirect method view             |
| `resources/views/pages/account/cash-flow-direct.blade.php`           | Direct method view               |
| `resources/views/pages/account/cash-flow-forecast/index.blade.php`   | Forecast dashboard               |
| `resources/views/pages/account/partials/cash-flow-table.blade.php`   | Indirect table partial           |
| `resources/views/pages/account/partials/cash-flow-direct-table.blade.php` | Direct table partial        |
| `resources/views/pages/account/pdf/cash-flow-pdf.blade.php`          | PDF template (indirect)         |
| `resources/views/pages/account/pdf/cash-flow-direct-pdf.blade.php`   | PDF template (direct)           |
| `public/assets/js/accounting-kpi.js`                                  | KPI dashboard charts             |
| `routes/account.php`                                                  | Route definitions                |

---

## 11. Testing Considerations

| Scenario                                 | Expected Behavior                                    |
|------------------------------------------|------------------------------------------------------|
| No transactions in period                | All sections show $0, opening = closing balance      |
| Revenue but no expenses                  | Operating shows positive cash flow                   |
| Multi-period comparison (3 months)       | Three columns with variance between each             |
| Forecast with no outstanding AR/AP       | Flat balance line, no bars on chart                  |
| Forecast with overdue items              | Items appear in current/past weeks                   |
| PDF export (portrait, <=2 periods)       | Correctly formatted PDF download                     |
| PDF export (landscape, >2 periods)       | Wide layout for multiple columns                     |
| CSV export                               | Flat file with correct totals matching UI            |
| Company isolation                        | Each company sees only its own data                  |

---

# User Guide: Cash Flow Reports & Forecast

## What Is Cash Flow?

Cash flow shows how money moves in and out of your business. Unlike profit (which includes non-cash items), cash flow tells you the **actual cash** available to pay bills, invest, and grow. The system provides three views:

1. **Cash Flow Statement (Indirect Method)** - Standard format starting from net profit
2. **Cash Flow Statement (Direct Method)** - Shows actual cash receipts and payments
3. **Cash Flow Forecast** - Predicts future cash position based on outstanding invoices and bills

---

## How to Access

| View                   | Navigation                                          |
|------------------------|------------------------------------------------------|
| Cash Flow (Indirect)   | Sidebar > **Reports** > **Cash Flow**               |
| Cash Flow (Direct)     | Sidebar > **Reports** > **Cash Flow (Direct)**      |
| Cash Flow Forecast     | Sidebar > **Finance** > **Cash Flow Forecast**      |

---

## Cash Flow Statement (Indirect Method)

### What It Shows

This report starts with your **Net Profit** and adjusts for items that affect profit but not cash (and vice versa). It's the most common format used in financial reporting.

### Using the Report

#### Step 1: Select Filters

| Filter          | Options                                    |
|-----------------|---------------------------------------------|
| **Date Range**  | From date - To date                         |
| **Compare By**  | Month, Quarter, Year, Fiscal Year           |

#### Step 2: Read the Report

The report has three main sections:

**A. Operating Activities** (Day-to-day business)
| Line Item                        | What It Means                              |
|----------------------------------|--------------------------------------------|
| Net Profit                       | Revenue minus all expenses                 |
| + Depreciation & Amortization    | Added back (non-cash expense)              |
| Change in Accounts Receivable    | Negative = customers owe more (cash tied up) |
| Change in Inventory              | Negative = bought more stock (cash spent)  |
| Change in Accounts Payable       | Positive = you owe more (cash preserved)   |
| **Net Cash from Operations**     | **Actual cash generated by the business**  |

**B. Investing Activities** (Long-term assets)
| Line Item                        | What It Means                              |
|----------------------------------|--------------------------------------------|
| Purchase of Fixed Assets         | Cash spent on equipment, vehicles, etc.    |
| Sale of Fixed Assets             | Cash received from selling assets          |
| Investment Changes               | Cash in/out from investments               |
| **Net Cash from Investing**      | **Cash impact of investment decisions**    |

**C. Financing Activities** (How you fund the business)
| Line Item                        | What It Means                              |
|----------------------------------|--------------------------------------------|
| Loan Proceeds                    | Cash received from new loans               |
| Loan Repayments                  | Cash paid to reduce loans                  |
| Equity Changes                   | Cash from investors/shareholders           |
| Dividends Paid                   | Cash distributed to owners                 |
| **Net Cash from Financing**      | **Cash impact of financing decisions**     |

**Summary:**
```
Opening Balance (cash at start of period)
+ Net Cash from Operations
+ Net Cash from Investing
+ Net Cash from Financing
= Closing Balance (cash at end of period)
```

### Multi-Period Comparison

When you compare multiple periods (e.g., 3 months), the report shows:
- A column for each period
- **Variance** columns showing the change between periods (both amount and %)
- This helps you spot trends: Is operating cash flow improving? Are investing outflows increasing?

### Exporting

- Click **"Export PDF"** for a formatted PDF document
- Click **"Export CSV"** for a spreadsheet-compatible file

---

## Cash Flow Statement (Direct Method)

### What's Different?

Instead of adjusting net profit, the direct method shows **actual cash receipts and payments**:

**Operating Activities show:**
- Cash received from customers (actual collections)
- Cash paid to suppliers (actual vendor payments)
- Payroll paid (actual salary payments)
- Operating expenses paid (actual expense payments)

### When to Use Each Method

| Method       | Best For                                        |
|--------------|--------------------------------------------------|
| **Indirect** | Standard financial reporting, investor presentations |
| **Direct**   | Detailed cash flow analysis, operational insights |

Both methods produce the **same total** for net cash from operations - they just get there differently.

---

## Cash Flow Forecast

### What It Shows

The forecast predicts your **future cash position** by looking at:
- Your **current cash balance** (from bank/cash accounts)
- **Expected inflows** (outstanding customer invoices that will be paid)
- **Expected outflows** (vendor bills you need to pay)

### Using the Forecast

#### Step 1: Select Horizon
Choose how far ahead to look: **30, 60, 90, or 180 days**

#### Step 2: Read the Summary Cards

| Card                  | Description                                    |
|-----------------------|------------------------------------------------|
| **Current Cash**      | Your cash balance right now                    |
| **Expected Inflows**  | Total AR expected to be collected              |
| **Expected Outflows** | Total AP expected to be paid                   |
| **Net Forecast**      | Current Cash + Inflows - Outflows              |

#### Step 3: Read the Chart

The interactive chart shows weekly projections:

| Visual Element     | Meaning                                         |
|--------------------|--------------------------------------------------|
| **Green bars**     | Expected cash IN (customer payments)             |
| **Red bars**       | Expected cash OUT (vendor payments)              |
| **Blue line**      | Running cash balance over time                   |

**Warning sign:** If the blue line dips below zero, you may face a cash shortfall that week!

#### Step 4: Browse the Transaction Table

Below the chart, a detailed table lists every expected inflow and outflow:

| Column       | Description                                     |
|--------------|--------------------------------------------------|
| **Reference**| Invoice or bill number                           |
| **Type**     | "AR Inflow" (green) or "AP Outflow" (red)       |
| **Date**     | When the payment is expected                     |
| **Amount**   | The expected amount (+ for inflows, - for outflows) |

You can:
- **Sort** by clicking column headers (Date, Reference, Amount)
- **Page** through results using the pagination controls
- **Change rows per page** (15, 25, 50, 100, up to 1000)

---

## Practical Tips

### Reading Cash Flow Like a Pro

1. **Positive operating cash flow is essential** - This means your core business generates cash. Negative operating cash flow is a warning sign even if you're profitable on paper.

2. **Negative investing cash flow can be good** - It means you're investing in growth (buying assets, expanding). Just make sure operating cash flow covers it.

3. **Watch the forecast blue line** - If it trends downward or goes negative, you need to:
   - Speed up customer collections
   - Delay non-critical vendor payments
   - Arrange financing

4. **Compare periods** - Use the multi-period view to see trends over time. Is cash flow improving or deteriorating?

---

## Frequently Asked Questions

**Q: Why does my cash flow differ from my profit?**
A: Profit includes non-cash items (depreciation) and accruals (revenue recognized but not yet collected). Cash flow shows actual money movement.

**Q: What does "Change in Accounts Receivable" mean?**
A: If AR increased, it means customers owe you more money that you haven't collected yet. This is a negative cash flow adjustment because the revenue was booked but cash wasn't received.

**Q: How accurate is the forecast?**
A: The forecast uses actual outstanding invoices and bills with a 30-day payment assumption. Actual timing may vary based on customer/vendor payment behavior.

**Q: Why do Direct and Indirect methods show the same operating total?**
A: They must! They're just two ways of presenting the same information. The indirect starts with profit and adjusts; the direct traces actual cash. Both arrive at the same net cash from operations.

**Q: Can I export the forecast?**
A: The forecast view does not currently have PDF/CSV export. The Cash Flow Statements (indirect and direct) both support PDF and CSV export.

**Q: What accounts are considered "Cash" accounts?**
A: Bank accounts, Cash accounts, and accounts with numbers in the 1000-1099 range are treated as cash equivalents for cash flow purposes.
