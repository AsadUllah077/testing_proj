# R&D Document: Fixed Assets Module

## 1. Module Overview

| Item               | Detail                                                    |
|--------------------|-----------------------------------------------------------|
| **Module Name**    | Fixed Assets Register & Depreciation                      |
| **Parent Module**  | Accounting                                                |
| **Purpose**        | Track physical and intangible long-term assets, calculate depreciation over their useful life, and automatically post depreciation journal entries to maintain accurate financial records. |
| **Target Users**   | Accountants, Finance Managers, Asset Managers, Auditors    |
| **Permission**     | `account.journal-entries.view`                            |

---

## 2. Problem Statement

Organizations own long-term assets (buildings, vehicles, equipment, software) that lose value over time. Tracking these assets and calculating depreciation manually is complex, error-prone, and audit-risky. Finance teams need:
- A centralized register of all fixed assets
- Automated depreciation calculations (straight-line and declining balance)
- Monthly journal entries posted without manual intervention
- A depreciation schedule showing past and future depreciation

---

## 3. Solution & Approach

### 3.1 Architecture

| Layer        | Component                                                  |
|--------------|------------------------------------------------------------|
| **Model**    | `FixedAsset`                                               |
| **Controller** | `FixedAssetController` (full CRUD + depreciation schedule)|
| **Command**  | `PostAssetDepreciation` (monthly Artisan command)          |
| **Views**    | `index`, `create`, `edit`, `show`, `_form`, `table` (Blade)|
| **Routes**   | 7 RESTful routes under `account.fixed-asset.*`             |

### 3.2 Database Schema

**Table: `fixed_assets`**

| Column                              | Type              | Description                                   |
|-------------------------------------|-------------------|-----------------------------------------------|
| id                                  | bigint (PK)       | Primary key                                   |
| company_id                          | FK -> companies   | Multi-tenant isolation                        |
| accounting_period_id                | FK (nullable)     | Linked accounting period                      |
| asset_account_id                    | FK -> chart_of_accounts | Balance sheet asset account             |
| depreciation_expense_account_id     | FK -> chart_of_accounts | Income statement expense account        |
| accumulated_depreciation_account_id | FK -> chart_of_accounts | Contra-asset account                    |
| created_by                          | FK -> users       | Audit: creator                                |
| updated_by                          | FK -> users       | Audit: last modifier                          |
| asset_number                        | string (unique)   | Auto-generated: `FA-YYYYMM-XXXX`             |
| name                                | string            | Asset name                                    |
| description                         | text (nullable)   | Detailed description                          |
| category                            | enum              | Equipment, Vehicle, Building, Furniture, Intangible, Land, Other |
| purchase_date                       | date              | When asset was acquired                       |
| in_service_date                     | date              | When asset was put into use                   |
| purchase_cost                       | decimal(15,2)     | Original acquisition cost                     |
| residual_value                      | decimal(15,2)     | Salvage value at end of useful life           |
| useful_life_months                  | int               | Estimated useful life in months               |
| depreciation_method                 | enum              | `straight_line` or `declining_balance`        |
| current_book_value                  | decimal(15,2)     | Current net book value                        |
| accumulated_depreciation            | decimal(15,2)     | Total depreciation posted to date             |
| last_depreciation_date              | date (nullable)   | Date of most recent depreciation entry        |
| status                              | enum              | `active`, `fully_depreciated`, `disposed`     |
| disposal_date                       | date (nullable)   | When asset was disposed                       |
| disposal_amount                     | decimal(15,2) (nullable) | Amount received on disposal            |
| notes                               | text (nullable)   | Additional notes                              |

**Composite Index:** `(company_id, status)`

### 3.3 Three-Account Model

Each fixed asset links to **three** Chart of Account entries:

```
1. Asset Account (Balance Sheet)
   Example: "Office Equipment" (Assets category)
   Purpose: Holds the purchase cost

2. Depreciation Expense Account (Income Statement)
   Example: "Depreciation Expense - Equipment" (Expenses category)
   Purpose: Records periodic depreciation cost

3. Accumulated Depreciation Account (Balance Sheet - Contra Asset)
   Example: "Accumulated Depreciation - Equipment" (Assets category, negative)
   Purpose: Accumulates total depreciation over time
```

### 3.4 Depreciation Calculation Methods

**Straight-Line Method:**
```
Monthly Depreciation = (Purchase Cost - Residual Value) / Useful Life (months)

Example:
  Purchase Cost     = $120,000
  Residual Value    = $12,000
  Useful Life       = 60 months (5 years)
  Monthly Depreciation = ($120,000 - $12,000) / 60 = $1,800/month
```

**Double-Declining Balance Method:**
```
Annual Rate = 2 / Useful Life (years)
Monthly Depreciation = Current Book Value x Annual Rate / 12

Example:
  Purchase Cost     = $120,000
  Useful Life       = 5 years
  Annual Rate       = 2 / 5 = 40%
  Month 1: $120,000 x 0.40 / 12 = $4,000
  Month 2: $116,000 x 0.40 / 12 = $3,867
  (Decreases each month as book value falls)
```

**Both methods respect the residual value** - depreciation stops when `current_book_value <= residual_value`.

### 3.5 Asset Number Generation

Format: `FA-YYYYMM-XXXX`
- `FA` = Fixed Asset prefix
- `YYYYMM` = Year and month of creation
- `XXXX` = Sequential 4-digit number

Example: `FA-202603-0001`, `FA-202603-0002`

### 3.6 Automated Depreciation Posting

**Command:** `php artisan assets:post-depreciation`

**Options:**
- `--month=YYYY-MM` - Target a specific month (defaults to current)
- `--dry-run` - Preview without posting

**Monthly Process:**
1. Finds all active assets with `in_service_date <= end of target month`
2. Skips assets already depreciated for that month
3. Skips fully depreciated assets (updates status if needed)
4. For each eligible asset:
   - Calculates monthly depreciation
   - Ensures depreciation doesn't reduce below residual value
   - Creates double-entry transaction:
     - **DR** Depreciation Expense Account
     - **CR** Accumulated Depreciation Account
   - Updates `current_book_value`, `accumulated_depreciation`, `last_depreciation_date`
   - Sets status to `fully_depreciated` when book value equals residual value
5. Transaction `unique_no` format: `DEP-{asset_number}-{YYYYMM}`
6. Logs to daily log file

### 3.7 Status Lifecycle

```
active  ──────────> fully_depreciated   (auto, when book value = residual)
active  ──────────> disposed            (manual, user records disposal)
```

---

## 4. Integration Points

| System                | Integration                                          |
|-----------------------|------------------------------------------------------|
| Chart of Accounts     | Three accounts per asset (asset, expense, contra)    |
| Journal Entries       | Monthly depreciation creates Transaction records     |
| Accounting Periods    | Validates period is unlocked for depreciation posting|
| Cash Flow Statement   | Fixed asset transactions appear in investing activities|
| Multi-Tenancy         | `BelongsToCompany` trait isolates data per company   |

---

## 5. Technical Decisions & Trade-offs

| Decision                              | Rationale                                                   |
|---------------------------------------|-------------------------------------------------------------|
| Three-account model per asset         | Proper double-entry accounting; matches GAAP/IFRS standards |
| Monthly (not daily) depreciation      | Standard practice; reduces journal entry volume              |
| Two depreciation methods              | Covers most use cases (SL for even, DDB for accelerated)    |
| Auto-generated asset numbers          | Prevents duplicates, ensures consistent format               |
| `--dry-run` for depreciation command  | Safe testing before actual posting                           |
| Soft deletes                          | Audit trail preserved; historical depreciation entries remain|
| Depreciation schedule in show view    | Gives complete visibility without separate report            |

---

## 6. File Manifest

| File                                                              | Purpose                        |
|-------------------------------------------------------------------|--------------------------------|
| `app/Models/FixedAsset.php`                                       | Main model with calculations   |
| `app/Http/Controllers/Account/FixedAssetController.php`           | Full CRUD + schedule builder   |
| `app/Console/Commands/PostAssetDepreciation.php`                  | Monthly depreciation command   |
| `database/migrations/2026_03_26_000002_create_fixed_assets_table.php` | Migration                  |
| `resources/views/pages/account/fixed-asset/index.blade.php`       | Register listing               |
| `resources/views/pages/account/fixed-asset/_form.blade.php`       | Create/Edit form               |
| `resources/views/pages/account/fixed-asset/show.blade.php`        | Detail + depreciation schedule |
| `resources/views/pages/account/fixed-asset/table.blade.php`       | Table partial                  |
| `routes/account.php` (lines 308-316)                              | Route definitions              |

---

## 7. Testing Considerations

| Scenario                                 | Expected Behavior                                    |
|------------------------------------------|------------------------------------------------------|
| Create asset with all fields             | Asset number auto-generated, book value = purchase cost |
| Straight-line depreciation posting       | Equal monthly amounts until residual value reached   |
| Declining balance depreciation           | Decreasing monthly amounts over time                 |
| Depreciation at residual value           | No further depreciation; status -> fully_depreciated |
| Duplicate depreciation for same month    | Skipped, no double-posting                           |
| Dry run                                  | No transactions created, output previewed            |
| Dispose an asset                         | Status -> disposed, disposal date/amount recorded    |
| Asset with 0 residual value              | Depreciates to $0                                    |
| Multi-tenant isolation                   | Company A cannot see Company B's assets              |

---

# User Guide: Fixed Assets

## What Are Fixed Assets?

Fixed Assets are long-term items your organization owns and uses over multiple years - buildings, vehicles, equipment, furniture, and software. The Fixed Assets module helps you:
- Maintain a centralized register of all your assets
- Automatically calculate how they lose value (depreciation) over time
- Post depreciation journal entries to your accounting system monthly

---

## How to Access

**Navigation:** Sidebar > **Finance** > **Fixed Assets**

---

## The Fixed Assets Register (Index Page)

### Dashboard Statistics

At the top of the page, you'll see four summary cards:

| Card               | Description                                    |
|--------------------|------------------------------------------------|
| **Total Assets**   | Total number of assets in your register        |
| **Active**         | Assets currently being depreciated             |
| **Total Cost**     | Combined purchase cost of all assets           |
| **Net Book Value** | Combined current value after depreciation      |

### Search & Filter

- **Search** - Find assets by name or asset number
- **Status filter** - Show Active, Fully Depreciated, or Disposed assets
- **Category filter** - Filter by Equipment, Vehicle, Building, etc.

---

## Adding a New Asset

### Step 1: Click "Add Asset"
Click the **"Add Asset"** button on the Fixed Assets page.

### Step 2: Fill in Asset Details

**Basic Information:**

| Field                | Description                                      | Required |
|----------------------|--------------------------------------------------|----------|
| **Name**             | Asset name (e.g., "Delivery Van #3")             | Yes      |
| **Category**         | Equipment, Vehicle, Building, Furniture, Intangible, Land, Other | Yes |
| **Description**      | Detailed description                             | No       |
| **Purchase Date**    | When the asset was acquired                      | Yes      |
| **In-Service Date**  | When the asset was put into use (depreciation starts from here) | Yes |
| **Purchase Cost**    | Original acquisition cost                        | Yes      |
| **Residual Value**   | Expected value at end of useful life (salvage value) | Yes  |
| **Useful Life**      | How many months the asset will be used           | Yes      |
| **Depreciation Method** | Straight Line or Declining Balance            | Yes      |
| **Notes**            | Additional information                           | No       |

**Account Mapping (Three Required Accounts):**

| Account                      | What It Does                               | Example                              |
|------------------------------|---------------------------------------------|--------------------------------------|
| **Asset Account**            | Holds the asset's value on your balance sheet | "Office Equipment"                  |
| **Depreciation Expense**     | Records the monthly depreciation cost       | "Depreciation Expense - Equipment"   |
| **Accumulated Depreciation** | Running total of all depreciation posted    | "Accum. Depreciation - Equipment"    |

### Step 3: Save
Click **"Save"**. The system will:
- Auto-generate an asset number (e.g., `FA-202604-0001`)
- Set the initial book value equal to purchase cost
- Set accumulated depreciation to $0
- Set status to **Active**

---

## Viewing an Asset's Details

Click on any asset to see its **detail page**, which includes:

### Asset Information
All the details you entered, plus calculated fields.

### Depreciation Progress
A visual **progress bar** showing what percentage of the asset has been depreciated:
- How much has been depreciated so far
- How much remains to be depreciated
- Monthly depreciation amount

### Depreciation Schedule
A month-by-month table showing:

| Column              | Description                                      |
|---------------------|--------------------------------------------------|
| **Month**           | The depreciation period                          |
| **Depreciation**    | Amount depreciated that month                    |
| **Accum. Depreciation** | Running total of depreciation              |
| **Book Value**      | Remaining value after depreciation               |
| **Status**          | "Posted" (journal entry exists) or "Pending"     |

---

## Understanding Depreciation Methods

### Straight-Line (Most Common)
Spreads the cost evenly over the asset's useful life.

**Example:**
- Delivery van costs **$60,000**
- Residual value: **$5,000**
- Useful life: **60 months** (5 years)
- Monthly depreciation: **($60,000 - $5,000) / 60 = $916.67/month**

Best for: Assets that provide equal benefit each year (buildings, furniture, standard equipment).

### Double-Declining Balance (Accelerated)
Higher depreciation in early years, lower in later years.

**Example:**
- Same delivery van at **$60,000**
- Annual rate: **2/5 = 40%**
- Month 1: $60,000 x 40% / 12 = **$2,000**
- Month 2: $58,000 x 40% / 12 = **$1,933**
- (Decreasing each month)

Best for: Assets that lose value quickly early on (computers, vehicles, technology).

---

## How Monthly Depreciation Works

The system automatically posts depreciation entries each month:

1. The depreciation command runs (typically monthly, scheduled or manual)
2. For each **active** asset past its in-service date:
   - Calculates the month's depreciation
   - Creates a journal entry:
     - **Debit** Depreciation Expense (increases expense)
     - **Credit** Accumulated Depreciation (increases contra-asset)
   - Reduces the asset's book value
3. When book value reaches the residual value, the asset is automatically marked as **Fully Depreciated**

---

## Managing Assets

### Editing an Asset
1. Click the **Edit** button (pencil icon)
2. Update fields as needed
3. Note: Changing useful life or residual value affects **future** depreciation only

### Disposing an Asset
When you sell or discard an asset:
1. Edit the asset
2. Change status to **Disposed**
3. Enter the **disposal date** and **disposal amount** (what you received for it)

### Deleting an Asset
1. Click the **Delete** button (trash icon)
2. Confirm the deletion
3. The asset is soft-deleted (can be recovered if needed)

---

## Understanding the Table Columns

| Column              | Description                                      |
|---------------------|--------------------------------------------------|
| **Asset Number**    | Auto-generated ID (e.g., FA-202604-0001)         |
| **Name**            | Asset name                                       |
| **Category**        | Equipment, Vehicle, Building, etc.               |
| **Purchase Cost**   | Original acquisition cost                        |
| **Book Value**      | Current value after depreciation                 |
| **Status**          | Green=Active, Blue=Fully Depreciated, Gray=Disposed |

---

## Asset Categories Explained

| Category        | Examples                                           |
|-----------------|-----------------------------------------------------|
| **Equipment**   | Machinery, printers, tools, manufacturing equipment |
| **Vehicle**     | Cars, trucks, delivery vans, forklifts             |
| **Building**    | Office buildings, warehouses, factories             |
| **Furniture**   | Desks, chairs, shelving, office furniture           |
| **Intangible**  | Software licenses, patents, trademarks              |
| **Land**        | Land plots (note: land typically doesn't depreciate) |
| **Other**       | Any assets not fitting above categories             |

---

## Frequently Asked Questions

**Q: What is residual value?**
A: The estimated value of the asset at the end of its useful life. Also called "salvage value." For example, a vehicle might be worth $5,000 for scrap after 5 years of use.

**Q: Does land depreciate?**
A: Generally no - land does not lose value over time. If you register land, set a very long useful life or a residual value equal to the purchase cost.

**Q: What happens when an asset is fully depreciated?**
A: Its status changes to "Fully Depreciated" automatically. No further depreciation entries are posted. The asset remains on your register for tracking purposes.

**Q: Can I change the depreciation method after starting?**
A: You can edit the method, but it will only affect future depreciation calculations. Past posted entries are not reversed.

**Q: What's the difference between purchase date and in-service date?**
A: Purchase date is when you bought the asset. In-service date is when you started using it. Depreciation begins from the **in-service date**, not the purchase date.

**Q: How do I know if depreciation has been posted for a month?**
A: View the asset's detail page and check the depreciation schedule. Each month shows "Posted" or "Pending" status.
