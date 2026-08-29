# Opening balance controls and production repair

## Existing implementation audit

The accounting ledger used by Balance Sheet, Trial Balance, General Ledger, Profit and Loss, and Cash Flow is `transactions`. `journal_entries` is a second, manual-journal editing representation; manual journal creation writes both tables. Opening-balance conversion already had batch tables, but the controls were incomplete.

Root causes found:

- `ChartOfAccountController::store()` and `update()` called `OpeningBalanceService::replaceAccountOpeningBalance()`. An ordinary account edit therefore reversed and recreated posted opening journals. That call has been removed and the account UI is read-only for effective opening balances.
- `OpeningBalanceService::accountOpeningBalance()` selected only the latest unbatched entry. It now displays the net of all posted opening journal lines, including controlled batches and reversals.
- `OpeningBalanceConversionService::post()` used PHP floats, accepted Draft/Validated batches, trusted the free-text fiscal year, and had no database idempotency key. Posting now requires separate approval, locks the batch, resolves the configured period from the ISO accounting date, uses decimal strings, and stores stable per-line posting keys.
- `OpeningBalanceConversionController::create()` hard-coded 30 June/1 July 2026 and fiscal-year text. Defaults now come from the latest configured active range; the posted fiscal-period name is resolved by the server from the posting date.
- `OpeningBalanceConversionService::reverse()` always used the original date and allowed an empty reason. It now requires an explicit ISO reversal date, an open configured period, a reason, and `reversal_date >= original_date`.
- `JournalEntryController::update()` and `destroy()` deleted and recreated posted `journal_entries` and `transactions`. Both operations are blocked; `reverse()` uses `JournalCorrectionService` to post a linked, balanced reversal.
- `BalanceSheetController::getBalancesForAllPeriods()` already uses inclusive `date <= as-of-date` and posted `transactions`. `getBalancesByCategory()` now excludes legacy physical Current Year Earnings accounts so the calculated system row appears once. `calculateCurrentYearEarnings()` no longer invents a calendar fiscal year when configuration is missing.
- `LedgerController::buildPaginatedLedger()` already orders by account, transaction date, then immutable line ID and computes each running balance. Rows now expose posting date, source, correction type, linked original, opening batch, and poster metadata.
- There is no separate fiscal-year table in this application. `accounting_periods` is the configured date-range authority, so `AccountingPeriodResolver` requires exactly one company-owned range containing the transaction date and rejects closed ranges.

The schema migration adds only controls, linkage, indexes, and audit tables. It performs no journal cleanup and deliberately does not add the final unique system-account constraint while production duplicates may exist.

## Workflow

Opening balances are prepared as one `opening_balance_batches` record with multiple `opening_balance_lines`. The state path is Draft -> Validated -> Approved -> Posted. Only a user whose exact role is **Super Admin** or **Accountant Manager** can approve. Those privileged roles may approve a batch they prepared; every other role is rejected. The approver must belong to the batch company. A posted batch and its lines are immutable. One batch has one `journal_unique_no`; concurrent posting is serialized with `lockForUpdate()` and protected by unique company/idempotency keys.

Revenue, COGS, Expenses, Current Year Earnings, Accounts Receivable, and Accounts Payable cannot be used in the standard batch. Receivable/payable openings must use detailed outstanding documents; legacy control-only entries are reported for reconciliation. Opening Balance Equity remains a separate temporary system account and is reported as a warning while non-zero.

Previously posted account-level openings appear under **Accounting -> Conversion -> Opening Balances -> Legacy Posted Opening Balances**. The Adjust action accepts the desired target balance, calculates the exact delta using fixed-scale decimal arithmetic, and creates a balanced draft against the designated account 399 Opening Balance Equity. It never updates or deletes the earlier posted journals. The draft must still be validated, approved, and posted into an open configured period.

## Normal opening-balance entry

Use **Accounting -> Conversion -> Opening Balances**. Do not enter or edit opening amounts in Chart of Accounts.

1. Create every required posting account in Chart of Accounts without an opening amount.
2. Select **Create Batch**.
3. Set **Source System** to the real source, for example `MANUAL`, `XERO`, `QUICKBOOKS`, or `VERIFIED_IMPORT`.
4. Keep or enter a unique reference, for example `MANUAL-OB-4-20260630-001`.
5. Enter a Closing Date earlier than the Posting / Conversion Date.
6. Enter the Posting / Conversion Date carefully. This is the immutable ledger date used by the Balance Sheet, Trial Balance, Journal Report, and Ledger.
7. Add debit and credit lines. Total debit must exactly equal total credit.
8. Save Draft, review it, Validate, Approve, and Post.
9. Verify the reports as of the posting date and the preceding date.

Opening-balance date fields use an isolated Flatpickr instance. The visible format is `dd/mm/yyyy`; the submitted and stored value is ISO `yyyy-mm-dd`. An edit form must display the saved dates, not a fallback date.

### Date behavior

Reports use an inclusive cutoff:

```text
transaction date <= report as-of date
```

An opening posted on `2026-07-01` is excluded from a Balance Sheet as of `2026-06-30` and included from `2026-07-01`. If accountant approval requires it to appear on 30 June, use:

```text
Closing Date:             2026-06-29
Posting / Conversion Date: 2026-06-30
Resolved period:          FY 2025-2026
```

Do not rely on Notes or Closing Date to control ledger timing. Only Posting / Conversion Date controls the journal date.

### Accounting periods

The application must resolve exactly one company accounting period containing the Posting / Conversion Date. Validation and posting reject missing, overlapping, inactive, or locked periods.

If an approved opening must be posted on 30 June and FY2025-2026 is locked:

1. Obtain authorization to reopen FY2025-2026.
2. Temporarily unlock the period.
3. Validate, approve, and post the batch.
4. Verify the reports.
5. Relock the period immediately.

Adding an Allowed Advisor does not bypass `AccountingPeriodResolver`; the period itself must be open. If policy prohibits reopening, use an approved open date and accept that the amount will not appear before that date.

### Example balanced manual batch

```text
Posting / Conversion Date: 2026-06-30

Business Bank Account       Debit   500,000
Computer Equipment          Debit   240,000
Payables to Director        Credit  200,000
Owner's Capital             Credit  540,000
                             ----------------
Total Debit                         740,000
Total Credit                        740,000
```

Do not post the example if those amounts are already present. Standard batches reject Accounts Receivable, Accounts Payable, Current Year Earnings, and profit-and-loss accounts. Import detailed outstanding customer invoices and vendor bills for AR/AP.

## Correcting a posted batch with the wrong date

Never edit the date of a posted batch or its transactions. For example, if a batch intended for 30 June was posted on 1 July:

1. Reverse the posted batch on `2026-07-01` or a later approved open date. A reversal cannot precede its original.
2. Record a specific reason, such as `Incorrect conversion date; approved posting date is 30 June 2026`.
3. Reopen FY2025-2026 if authorized.
4. Create a replacement Draft with Closing Date `2026-06-29` and Posting / Conversion Date `2026-06-30`.
5. Copy the approved lines, Validate, Approve, and Post.
6. Confirm the replacement is present on 30 June and the original plus reversal cancel on 1 July.
7. Relock FY2025-2026.

## Deployment prerequisites

1. Deploy code in a maintenance window, with opening-balance posting temporarily unavailable to users.
2. Schedule an approved maintenance window and prevent concurrent opening-balance posting while the audit or repair is running.
3. Preview and run only the opening-balance schema migration. Do not run a blanket migration when unrelated migrations are pending:

```bash
php artisan migrate --pretend --path=database/migrations/2026_08_21_000001_harden_opening_balance_accounting.php --force
php artisan migrate --path=database/migrations/2026_08_21_000001_harden_opening_balance_accounting.php --force
```

The migration does not delete, reverse, renumber, or update accounting history. The audit and repair commands fail clearly if their schema is absent. 4. Confirm accounting periods and authorized users are configured, then deploy the UI/routes. 5. Do not add a unique `(company_id, system_account_type)` constraint until the audit reports no duplicates.

## Production dry-run

Dry-run writes only its audit plan and item snapshots; it changes zero accounting/journal rows. Run it once per company/cutover and retain the JSON as review evidence:

```bash
php artisan accounting:audit-opening-balances --company=4 --cutover-date=2026-07-01 --dry-run --format=json --output=storage/app/accounting/ob-audit-company-4.json
```

The command exits non-zero when blocked issues exist. Review every affected journal, line, account, date, configured period, batch relationship, control account, reversal, and proposed action. References are evidence only; duplicate detection uses a sorted accounting fingerprint.

## Accountant approval

The approver must establish the authoritative target from, in order: an approved/locked batch, approved migration trial balance, signed source closing trial balance, approved import, verified legacy metadata, or manual review. The software never selects a target from duplicate data alone. Mark unresolved items `MANUAL_ACCOUNTANT_REVIEW_REQUIRED` and do not apply that plan.

Confirm specifically:

- source closing date and opening posting date;
- account ownership and classification;
- correct configured accounting period;
- debit/credit and currency basis;
- historical equity versus temporary Opening Balance Equity;
- no opening amount in Current Year Earnings or profit-and-loss accounts;
- detailed AR/AP documents and every other control-account reconciliation;
- whether an old period may legally be reopened or correction must use an authorized current open period.

## Repair execution

Only a saved plan with no blocked issues can be applied. `--force` suppresses the prompt only; it cannot bypass validation.

```bash
php artisan accounting:repair-opening-balances --company=4 --cutover-date=2026-07-01 --plan=5 --apply --approved-by=1 --executed-by=100 --reason="Approved duplicate migration correction" --output=storage/app/accounting/ob-repair-company-4.json
```

Each unit locks its rows and commits independently. Draft duplicates are soft-deleted with before snapshots. Confirmed posted duplicates are preserved and offset with linked full reversals in an open period. Ambiguous balances, malformed history, cross-company records, wrong periods, and unbalanced journals are blocked. A rerun uses stable plan keys and reports `already_repaired` without another correction.

`--approved-by` and `--executed-by` are required for historical repair attribution. Neither repair command requires a backup-reference option. These options are not used during normal UI batch creation.

## Repairing the legacy reverse-before-original date chain

Use `accounting:repair-legacy-opening-date-chain` only after the audit proves this exact pattern:

- balanced originals on the later date;
- one invalid earlier reversal per original line;
- balanced equal replacement journals on the earlier target date;
- no extra lines in the selected chain.

Example:

```bash
php artisan accounting:repair-legacy-opening-date-chain --company=4 --original-date=2026-07-01 --target-date=2026-06-30 --approved-by=1 --executed-by=100 --reason="Approved correction of legacy opening-date edit chain" --clear-duplicate-obe-account=2530 --apply
```

The command:

1. locks and verifies the complete legacy chain;
2. proves the original, reversal, and replacement accounting vectors match;
3. creates one approved target-date batch;
4. creates linked corrections on or after their originals so balances do not double;
5. optionally clears the duplicate Opening Balance Equity system role from the approved account ID;
6. verifies the authoritative balance at both dates;
7. restores the target period's prior locked state;
8. rolls back the whole database transaction on any mismatch;
9. refuses a second application using its stable idempotency key.

Do not use this command to change an ordinary newly posted batch date. Use the normal reversal-and-replacement workflow above.

## Post-repair reconciliation

- Re-run the audit and retain the clean/warning report.
- Reconcile opening debits to credits and every account to the approved migration trial balance.
- Verify a 1 July opening is excluded on 30 June and included from 1 July. Verify a 30 June opening is included on both 30 June and 1 July.
- Confirm General Ledger closing balances equal Trial Balance balances and Balance Sheet account totals.
- Confirm Assets = Liabilities + Equity.
- Confirm opening-only Revenue, Expenses, COGS, Profit and Loss, and Current Year Earnings are zero.
- Confirm Opening Balance Equity is separately disclosed and cleared or formally accepted as a remaining migration item.
- Reconcile AR, AP, bank, fixed assets, inventory, tax, and payroll control accounts to their subledgers.
- Review historical reports affected by any invalid backdated reversal; the repair never silently changes a posted date.

## Rollback

Never delete a posted repair journal. Freeze further opening activity and create a separately approved controlled reversal of each repair correction in an authorized open period. Link it to the repair run, require a reason and approver, and repeat the complete reconciliation checklist. A soft-deleted draft may be restored from `before_snapshot` only after checking dependencies and uniqueness controls. Preserve both the original repair and rollback audit records.

## Remaining manual risks

- This ERP has no distinct fiscal-year entity; configured `accounting_periods` must represent the authoritative reporting ranges without overlaps.
- Existing control-account balances cannot be proved against subledgers from journal data alone; the audit warns, but an accountant must reconcile the relevant reports.
- A non-zero Opening Balance Equity requires a documented equity mapping decision.
- Wrong historical posted dates and malformed multi-step correction chains are reported, not guessed or silently rewritten.
- The final unique system-account database constraint is intentionally deferred until production duplicates have been resolved and reviewed.
- The historical repair does not decide how to clear a non-zero temporary Opening Balance Equity. Accountant-approved replacement equity accounts and amounts are still required.
