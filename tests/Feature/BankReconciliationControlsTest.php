<?php

namespace Tests\Feature;

use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Transaction;
use App\Services\Account\BankReconciliationControlService;
use App\Services\Account\BankReconciliationReportService;
use App\Services\Account\BankStatementImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsBankReconciliationSchema;
use Tests\TestCase;

class BankReconciliationControlsTest extends TestCase
{
    use BuildsBankReconciliationSchema;

    private int $companyId;
    private int $bankId;
    private int $otherBankId;
    private int $expenseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildBankReconciliationSchema();
        $this->companyId = DB::table('companies')->insertGetId([
            'name' => 'Bank Control Test Co',
        ]);
        $this->bankId = $this->account(1001, 'Operating Bank', 'Assets', 'BANK');
        $this->otherBankId = $this->account(1002, 'Other Bank', 'Assets', 'BANK');
        $this->expenseId = $this->account(6001, 'Bank Fees', 'Expenses', null);
        DB::table('accounting_periods')->insert([
            'company_id' => $this->companyId, 'name' => 'FY 2026', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => 1, 'is_locked' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_matching_enforces_bank_direction_and_remaining_amount(): void
    {
        $line = $this->line(100);
        $wrongBank = $this->transaction($this->otherBankId, 100, 0, 'WRONG-BANK');
        $this->expectException(ValidationException::class);
        app(BankReconciliationControlService::class)->reconcileExisting($line, [$wrongBank->id]);
    }

    public function test_matching_rejects_over_allocation_and_records_valid_match(): void
    {
        $line = $this->line(100);
        $valid = $this->transaction($this->bankId, 60, 0, 'MATCH-60');
        app(BankReconciliationControlService::class)->reconcileExisting($line, [$valid->id], 91);
        $this->assertDatabaseHas('bank_statement_lines', ['id' => $line->id, 'status' => 'Partially Reconciled', 'remaining_amount' => 40]);
        $this->assertDatabaseHas('bank_reconciliation_matches', ['transaction_id' => $valid->id, 'origin' => 'existing', 'confidence' => 91]);

        $tooLarge = $this->transaction($this->bankId, 50, 0, 'MATCH-50');
        $this->expectException(ValidationException::class);
        app(BankReconciliationControlService::class)->reconcileExisting($line->fresh(), [$tooLarge->id]);
    }

    public function test_generated_posting_is_reversed_not_deleted(): void
    {
        $line = $this->line(-25);
        $bankLeg = $this->transaction($this->bankId, 0, 25, 'BANK-FEE', $line->id, 'bank_reconciliation');
        $this->transaction($this->expenseId, 25, 0, 'BANK-FEE', null, 'bank_reconciliation');
        $controls = app(BankReconciliationControlService::class);
        $controls->recordGeneratedMatch($line, $bankLeg, 25, 'generated');
        $controls->refreshLine($line);
        $controls->unreconcile($line->fresh(), 'Fee was posted to the wrong account');

        $this->assertDatabaseHas('transactions', ['id' => $bankLeg->id, 'unique_no' => 'BANK-FEE']);
        $this->assertDatabaseHas('transactions', ['reversal_of_transaction_id' => $bankLeg->id, 'debit' => 25, 'credit' => 0]);
        $this->assertDatabaseHas('bank_statement_lines', ['id' => $line->id, 'status' => 'Unreconciled', 'remaining_amount' => -25]);
        $this->assertDatabaseHas('bank_reconciliation_matches', ['transaction_id' => $bankLeg->id, 'reversal_reason' => 'Fee was posted to the wrong account']);
    }

    public function test_import_is_idempotent_and_reports_duplicate_rows(): void
    {
        $metadata = [
            'statement_number' => 'AUG-2026', 'start_date' => '2026-08-01', 'end_date' => '2026-08-31',
            'opening_balance' => 0, 'closing_balance' => 100, 'currency' => 'USD',
            'source_filename' => 'aug.csv', 'file_hash' => hash('sha256', 'aug-file'),
        ];
        $service = app(BankStatementImportService::class);
        $first = $service->importChunk($this->companyId, $this->bankId, [['2026-08-05', '100', 'Customer', 'Receipt', 'R-1']], $metadata);
        $second = $service->importChunk($this->companyId, $this->bankId, [['2026-08-05', '100', 'Customer', 'Receipt', 'R-1']], $metadata + ['statement_import_id' => $first['statement_import_id']]);

        $this->assertSame(1, $first['imported']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('bank_statement_lines', 1);
    }

    public function test_finalized_statement_lines_are_immutable(): void
    {
        $statement = BankStatementImport::create([
            'company_id' => $this->companyId, 'bank_account_id' => $this->bankId, 'statement_number' => 'LOCKED',
            'start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'opening_balance' => 0,
            'closing_balance' => 100, 'currency' => 'USD', 'status' => 'finalized',
        ]);
        $line = $this->line(100, $statement->id, true);
        $this->expectException(ValidationException::class);
        app(BankReconciliationControlService::class)->assertEditable($line);
    }

    public function test_formal_report_uses_selected_statement_cutoff(): void
    {
        $statement = BankStatementImport::create([
            'company_id' => $this->companyId, 'bank_account_id' => $this->bankId, 'statement_number' => 'REPORT',
            'start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'opening_balance' => 0,
            'closing_balance' => 120, 'currency' => 'USD', 'status' => 'in_progress',
        ]);
        $line = $this->line(120, $statement->id);
        $transaction = $this->transaction($this->bankId, 120, 0, 'REPORT-MATCH', $line->id);
        $line->update(['status' => 'Reconciled', 'remaining_amount' => 0]);
        $report = app(BankReconciliationReportService::class)->build($this->companyId, $this->bankId, $statement->id);

        $this->assertSame(120.0, $report['declaredClosingBalance']);
        $this->assertSame(120.0, $report['ledgerBalance']);
        $this->assertSame(0.0, $report['variance']);
    }

    private function account(int $number, string $name, string $category, ?string $subType): int
    {
        return DB::table('chart_of_accounts')->insertGetId([
            'company_id' => $this->companyId, 'account_number' => $number, 'name' => $name,
            'category' => $category, 'type' => 'Account', 'sub_type' => $subType,
            'is_system' => 0, 'initial_balance' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function line(float $amount, ?int $statementId = null, bool $locked = false): BankStatementLine
    {
        return BankStatementLine::create([
            'company_id' => $this->companyId, 'accounting_period_id' => DB::table('accounting_periods')->value('id'),
            'bank_account_id' => $this->bankId, 'bank_statement_import_id' => $statementId,
            'date' => '2026-08-05', 'amount' => $amount, 'remaining_amount' => $amount,
            'status' => 'Unreconciled', 'unique_no' => app(BankReconciliationControlService::class)->uniqueNumber($this->companyId),
            'is_locked' => $locked,
        ]);
    }

    private function transaction(int $accountId, float $debit, float $credit, string $uniqueNo, ?int $lineId = null, ?string $source = null): Transaction
    {
        return Transaction::create([
            'company_id' => $this->companyId, 'accounting_period_id' => DB::table('accounting_periods')->value('id'),
            'account_id' => $accountId, 'date' => '2026-08-05', 'type' => 'test', 'description' => 'Test posting',
            'debit' => $debit, 'credit' => $credit, 'status' => 1, 'unique_no' => $uniqueNo,
            'bank_statement_line_id' => $lineId, 'source_system' => $source,
        ]);
    }
}
