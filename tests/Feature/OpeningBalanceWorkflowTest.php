<?php

namespace Tests\Feature;

use App\Exports\OpeningBalanceConversionTemplateExport;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Services\Account\OpeningBalanceConversionService;
use App\Services\Account\OpeningBalanceImportService;
use App\Services\Account\OpeningBalanceReconciliationService;
use App\Services\Account\OpeningBalanceRepairService;
use App\Services\Account\JournalCorrectionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpeningBalanceWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::disableForeignKeyConstraints();
        foreach (['activity_logs', 'audit_actions', 'transactions', 'journal_entries', 'accounting_repair_items', 'accounting_repair_runs', 'opening_balance_lines', 'opening_balance_batches', 'chart_of_accounts', 'accounting_periods', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('roles', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('role_id')->nullable();
            $t->string('first_name')->nullable(); $t->string('last_name')->nullable(); $t->string('password')->nullable();
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('accounting_periods', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->string('name'); $t->date('start_date'); $t->date('end_date');
            $t->boolean('is_active')->default(true); $t->boolean('is_locked')->default(false); $t->timestamps();
        });
        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->string('account_number'); $t->string('name');
            $t->string('category'); $t->string('type')->default('Account'); $t->string('system_account_type')->nullable();
            $t->string('sub_type')->nullable(); $t->boolean('is_system')->default(false); $t->decimal('initial_balance', 18, 4)->default(0);
            $t->date('opening_date')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('opening_balance_batches', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->string('reference'); $t->string('idempotency_key')->nullable();
            $t->string('journal_unique_no')->nullable(); $t->string('source_system'); $t->string('source_file_reference')->nullable(); $t->string('source_file_hash')->nullable();
            $t->string('batch_kind')->default('conversion'); $t->unsignedBigInteger('supersedes_batch_id')->nullable();
            $t->date('conversion_date'); $t->date('closing_date')->nullable(); $t->date('go_live_date')->nullable(); $t->string('fiscal_year')->nullable(); $t->string('status');
            $t->text('notes')->nullable(); $t->decimal('total_debit', 18, 4)->default(0); $t->decimal('total_credit', 18, 4)->default(0);
            $t->timestamp('posted_at')->nullable(); $t->timestamp('locked_at')->nullable(); $t->unsignedBigInteger('posted_by')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable(); $t->timestamp('reviewed_at')->nullable(); $t->unsignedBigInteger('approved_by')->nullable(); $t->timestamp('approved_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('opening_balance_lines', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('opening_balance_batch_id'); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('account_id');
            $t->decimal('debit', 18, 4)->default(0); $t->decimal('credit', 18, 4)->default(0); $t->text('description')->nullable();
            $t->string('source_account_code')->nullable(); $t->string('source_account_name')->nullable(); $t->string('source_reference')->nullable(); $t->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('accounting_period_id')->nullable(); $t->unsignedBigInteger('account_id')->nullable();
            $t->string('unique_no')->nullable(); $t->text('narration')->nullable(); $t->date('date'); $t->integer('item')->nullable();
            $t->string('manual_item')->nullable(); $t->text('description')->nullable(); $t->decimal('debit', 18, 4)->default(0);
            $t->decimal('credit', 18, 4)->default(0); $t->string('posting_status')->default('posted');
            $t->string('reversal_of_unique_no')->nullable(); $t->string('correction_type')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable();
            $t->unsignedBigInteger('posted_by')->nullable(); $t->timestamp('posted_at')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('accounting_period_id')->nullable(); $t->unsignedBigInteger('account_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable(); $t->unsignedBigInteger('vendor_id')->nullable(); $t->string('payable_type')->nullable(); $t->unsignedBigInteger('payable_id')->nullable();
            $t->string('reference_id')->nullable(); $t->string('unique_no')->nullable(); $t->date('date')->nullable(); $t->string('type')->nullable();
            $t->string('source_system')->nullable(); $t->string('source_reference')->nullable(); $t->string('posting_idempotency_key')->nullable();
            $t->boolean('is_opening_balance')->default(false); $t->unsignedBigInteger('opening_balance_batch_id')->nullable();
            $t->unsignedBigInteger('reversal_of_transaction_id')->nullable(); $t->string('reversal_of_unique_no')->nullable(); $t->string('correction_type')->nullable();
            $t->unsignedBigInteger('accounting_repair_run_id')->nullable(); $t->text('description')->nullable(); $t->decimal('debit', 18, 4)->default(0);
            $t->decimal('credit', 18, 4)->default(0); $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable();
            $t->unsignedBigInteger('posted_by')->nullable(); $t->timestamp('posted_at')->nullable(); $t->integer('status')->default(1); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('audit_actions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('transaction_id')->index(); $t->string('action'); $t->string('status');
            $t->unsignedBigInteger('performed_by')->nullable(); $t->text('notes')->nullable(); $t->json('meta')->nullable(); $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id')->nullable(); $t->string('loggable_type'); $t->unsignedBigInteger('loggable_id');
            $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('employee_id')->nullable(); $t->string('action'); $t->json('changes')->nullable(); $t->timestamp('created_at')->nullable();
        });
        Schema::create('accounting_repair_runs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->string('repair_type'); $t->json('parameters'); $t->boolean('dry_run');
            $t->string('status'); $t->string('backup_reference')->nullable(); $t->unsignedBigInteger('requested_by')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('completed_at')->nullable();
            $t->json('summary')->nullable(); $t->timestamps();
        });
        Schema::create('accounting_repair_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('repair_run_id'); $t->string('issue_type'); $t->string('severity'); $t->string('entity_type')->nullable();
            $t->string('entity_id')->nullable(); $t->json('related_entity_ids')->nullable(); $t->json('before_snapshot')->nullable();
            $t->json('authoritative_target')->nullable(); $t->string('proposed_action'); $t->json('after_snapshot')->nullable();
            $t->string('result')->nullable(); $t->text('error_message')->nullable(); $t->string('idempotency_key'); $t->timestamps();
        });
        Schema::enableForeignKeyConstraints();

        DB::table('accounting_periods')->insert(['company_id' => 4, 'name' => 'FY2026-2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Super Admin'],
            ['id' => 2, 'name' => 'Accountant Manager'],
            ['id' => 3, 'name' => 'Accountant'],
        ]);
        DB::table('users')->insert([
            ['id' => 7, 'company_id' => 4, 'role_id' => 2, 'first_name' => 'Manager', 'last_name' => 'Seven'],
            ['id' => 11, 'company_id' => 4, 'role_id' => 1, 'first_name' => 'Admin', 'last_name' => 'Eleven'],
            ['id' => 12, 'company_id' => 4, 'role_id' => 2, 'first_name' => 'Manager', 'last_name' => 'Twelve'],
            ['id' => 13, 'company_id' => 4, 'role_id' => 3, 'first_name' => 'Accountant', 'last_name' => 'Thirteen'],
        ]);
        DB::table('chart_of_accounts')->insert([
            ['id' => 10, 'company_id' => 4, 'account_number' => '120', 'name' => 'Loan receivable from Imran', 'category' => 'Assets', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 20, 'company_id' => 4, 'account_number' => '399', 'name' => 'Opening Balance Equity', 'category' => 'Equity', 'type' => 'Account', 'system_account_type' => 'OPENING_BALANCE_EQUITY'],
        ]);
    }

    public function test_approved_batch_posts_once_and_as_of_date_is_inclusive(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-4-FY26', 'idempotency_key' => 'request-1', 'source_system' => 'VERIFIED_IMPORT',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => '140000.0000', 'credit' => '0.0000'],
            ['company_id' => 4, 'account_id' => 20, 'debit' => '0.0000', 'credit' => '140000.0000'],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());

        $this->assertSame(2, Transaction::withoutGlobalScopes()->count());
        $this->assertSame('FY2026-2027', $batch->fresh()->fiscal_year);
        $this->assertSame('0', (string) Transaction::withoutGlobalScopes()->where('date', '<=', '2026-06-30')->sum(DB::raw('debit-credit')));
        $this->assertSame(140000.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 10)->where('date', '<=', '2026-07-01')->sum(DB::raw('debit-credit')));

        try { $service->post($batch->fresh()); } catch (ValidationException) {}
        $this->assertSame(2, Transaction::withoutGlobalScopes()->count(), 'Repeated posting must not duplicate ledger lines.');

        $this->expectException(\LogicException::class);
        Transaction::withoutGlobalScopes()->first()->update(['debit' => '1.0000']);
    }

    public function test_unbalanced_targets_receive_a_system_historical_adjustment(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-4-BAD', 'source_system' => 'VERIFIED_IMPORT',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->create(['company_id' => 4, 'account_id' => 10, 'debit' => '140000.0000', 'credit' => '0.0000']);
        app(OpeningBalanceConversionService::class)->markValidated($batch);

        $this->assertSame('Validated', $batch->fresh()->status);
        $this->assertSame(140000.0, (float) $batch->lines()->where('account_id', 20)->value('credit'));
        $this->assertSame((float) $batch->fresh()->total_debit, (float) $batch->fresh()->total_credit);
    }

    public function test_import_creates_an_ordered_draft_with_source_and_fiscal_metadata(): void
    {
        $file = UploadedFile::fake()->createWithContent('verified-opening.csv', implode("\n", [
            'xero_account_code,xero_account_name,erp_account_number,erp_account_name,debit,credit,description,source_reference',
            'X120,Loan,120,Loan receivable from Imran,0,0,,',
            'X120,Loan,120,Loan receivable from Imran,140000,0,Verified asset,XERO:X120',
            'X399,Equity,399,Opening Balance Equity,0,140000,Verified offset,XERO:X399',
        ]));

        $batch = app(OpeningBalanceImportService::class)->importToDraft($file, [
            'reference' => 'IMPORT-ORDERED-1',
            'source_system' => 'XERO',
            'closing_date' => '2026-07-01',
            'conversion_date' => '2026-07-01',
            'notes' => 'Verified import',
        ], 4);

        $this->assertSame('FY2026-2027', $batch->fiscal_year);
        $this->assertSame('verified-opening.csv', $batch->source_file_reference);
        $this->assertSame([10, 20], $batch->lines()->orderBy('id')->pluck('account_id')->all());
        $this->assertSame('140000.0000', $batch->total_debit);
        $this->assertSame('140000.0000', $batch->total_credit);
    }

    public function test_template_contains_the_company_chart_of_accounts(): void
    {
        $rows = (new OpeningBalanceConversionTemplateExport(4))->array();

        $this->assertCount(2, $rows);
        $this->assertSame(['120', '399'], collect($rows)->pluck(0)->all());
        $this->assertSame(['Eligible', 'Eligible'], collect($rows)->pluck(7)->all());

        $selectedRows = (new OpeningBalanceConversionTemplateExport(4, [20]))->array();
        $this->assertCount(1, $selectedRows);
        $this->assertSame('399', $selectedRows[0][0]);

        DB::table('chart_of_accounts')->insert([
            'id' => 30, 'company_id' => 4, 'account_number' => '200', 'name' => 'Sales',
            'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null,
        ]);
        DB::table('chart_of_accounts')->insert([
            'id' => 40, 'company_id' => 4, 'account_number' => '3140', 'name' => 'Current Year Earnings',
            'category' => 'Equity', 'type' => 'Account', 'system_account_type' => 'CURRENT_YEAR_EARNINGS',
        ]);
        $eligibleRows = (new OpeningBalanceConversionTemplateExport(4, [], 'eligible'))->array();
        $this->assertCount(3, $eligibleRows);
        $this->assertSame('Eligible - profit and loss conversion balance', collect($eligibleRows)->firstWhere(0, '200')[7]);
        $ineligibleRows = (new OpeningBalanceConversionTemplateExport(4, [], 'ineligible'))->array();
        $this->assertCount(1, $ineligibleRows);
        $this->assertSame('3140', $ineligibleRows[0][0]);
        $this->assertCount(4, (new OpeningBalanceConversionTemplateExport(4, [], 'both'))->array());
    }

    public function test_xero_style_conversion_allows_profit_and_loss_accounts_but_not_current_year_earnings(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '200', 'name' => 'Sales', 'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 40, 'company_id' => 4, 'account_number' => '3140', 'name' => 'Current Year Earnings', 'category' => 'Equity', 'type' => 'Account', 'system_account_type' => 'CURRENT_YEAR_EARNINGS'],
        ]);
        $file = UploadedFile::fake()->createWithContent('xero-conversion.csv', implode("\n", [
            'erp_account_number,erp_account_name,debit,credit',
            '120,Loan receivable from Imran,10000,0',
            '200,Sales,0,10000',
        ]));
        $batch = app(OpeningBalanceImportService::class)->importToDraft($file, [
            'reference' => 'XERO-PNL-CONVERSION-1', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01',
        ], 4);

        app(OpeningBalanceConversionService::class)->markValidated($batch);
        $this->assertSame('Validated', $batch->fresh()->status);

        $invalidBatch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'XERO-CYE-INVALID-1', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $invalidBatch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 10000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 10000],
        ]);
        app(OpeningBalanceConversionService::class)->recalculateTotals($invalidBatch);

        $this->expectException(ValidationException::class);
        app(OpeningBalanceConversionService::class)->markValidated($invalidBatch->fresh());
    }

    public function test_pnl_conversion_on_year_end_affects_old_year_profit_and_carries_cash_forward(): void
    {
        DB::table('accounting_periods')->insert([
            'company_id' => 4, 'name' => 'FY2025-2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '190', 'name' => 'Cash Account', 'category' => 'Assets', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 40, 'company_id' => 4, 'account_number' => '200', 'name' => 'Sales', 'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null],
        ]);
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'XERO-YEAR-END-PNL', 'source_system' => 'XERO', 'batch_kind' => 'conversion',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-06-30', 'go_live_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 30, 'debit' => 10000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 10000],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());

        $oldYearRevenue = Transaction::withoutGlobalScopes()->where('account_id', 40)
            ->whereBetween('date', ['2025-07-01', '2026-06-30'])->sum(DB::raw('credit-debit'));
        $newYearRevenue = Transaction::withoutGlobalScopes()->where('account_id', 40)
            ->whereBetween('date', ['2026-07-01', '2027-06-30'])->sum(DB::raw('credit-debit'));
        $cashAsAtNewYear = Transaction::withoutGlobalScopes()->where('account_id', 30)
            ->whereDate('date', '<=', '2026-07-01')->sum(DB::raw('debit-credit'));

        $this->assertSame(10000.0, (float) $oldYearRevenue);
        $this->assertSame(0.0, (float) $newYearRevenue);
        $this->assertSame(10000.0, (float) $cashAsAtNewYear);
    }

    public function test_import_reports_invalid_amount_against_the_spreadsheet_row(): void
    {
        $file = UploadedFile::fake()->createWithContent('invalid-opening.csv', implode("\n", [
            'erp_account_number,erp_account_name,debit,credit',
            '120,Loan receivable from Imran,not-a-number,0',
        ]));

        try {
            app(OpeningBalanceImportService::class)->importToDraft($file, [
                'reference' => 'IMPORT-INVALID-1',
                'source_system' => 'XERO',
                'closing_date' => '2026-07-01',
                'conversion_date' => '2026-07-01',
            ], 4);
            $this->fail('Invalid spreadsheet amounts must not create an opening balance draft.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Row 2', $exception->getMessage());
        }

        $this->assertDatabaseMissing('opening_balance_batches', ['reference' => 'IMPORT-INVALID-1']);
    }

    public function test_super_admin_and_accountant_manager_preparers_can_self_approve(): void
    {
        foreach ([11, 12] as $userId) {
            $batch = OpeningBalanceBatch::create([
                'company_id' => 4, 'reference' => 'OB-SELF-' . $userId, 'source_system' => 'MANUAL',
                'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Validated',
                'created_by' => $userId,
            ]);

            app(OpeningBalanceConversionService::class)->approve($batch, $userId);

            $this->assertSame('Approved', $batch->fresh()->status);
            $this->assertSame($userId, (int) $batch->fresh()->approved_by);
        }
    }

    public function test_other_roles_cannot_approve_an_opening_balance_batch(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-SELF-STAFF', 'source_system' => 'MANUAL',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Validated',
            'created_by' => 13,
        ]);

        $this->expectException(ValidationException::class);
        app(OpeningBalanceConversionService::class)->approve($batch, 13);
    }

    public function test_reversal_before_original_date_is_rejected(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-4-REV', 'source_system' => 'VERIFIED_IMPORT',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => '140000.0000', 'credit' => '0.0000'],
            ['company_id' => 4, 'account_id' => 20, 'debit' => '0.0000', 'credit' => '140000.0000'],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());

        $this->expectException(ValidationException::class);
        $service->reverse($batch->fresh(), '2026-06-30', 'Duplicate migration posting');
    }

    public function test_posting_date_correction_copies_all_lines_without_retyping(): void
    {
        DB::table('accounting_periods')->insert([
            'company_id' => 4,
            'name' => 'FY2025-2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-WRONG-DATE', 'source_system' => 'MANUAL',
            'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => '140000.0000', 'credit' => '0.0000'],
            ['company_id' => 4, 'account_id' => 20, 'debit' => '0.0000', 'credit' => '140000.0000'],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());
        $originalTransactionIds = $batch->fresh()->transactions()->pluck('id')->all();

        $replacement = $service->correctPostingDate($batch->fresh(), '2026-06-30', 'Entered one day late', 7);
        $repeated = $service->correctPostingDate($batch->fresh(), '2026-06-30', 'Repeated request', 7);

        $this->assertSame($replacement->id, $repeated->id);
        $this->assertSame('Reversed', $batch->fresh()->status);
        $this->assertSame('Posted', $replacement->status);
        $this->assertSame('2026-06-30', $replacement->conversion_date->format('Y-m-d'));
        $this->assertSame(2, $replacement->lines()->count());
        $this->assertSame(2, $replacement->transactions()->whereNull('reversal_of_transaction_id')->count());
        $this->assertSame(2, Transaction::withoutGlobalScopes()->whereIn('reversal_of_transaction_id', $originalTransactionIds)->count());
        $this->assertSame(140000.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 10)->whereDate('date', '<=', '2026-06-30')->sum(DB::raw('debit-credit')));
        $this->assertSame(140000.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 10)->whereDate('date', '<=', '2026-07-01')->sum(DB::raw('debit-credit')));
    }

    public function test_reversed_batch_can_be_copied_to_replacement_draft(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-REVERSE-THEN-COPY', 'source_system' => 'MANUAL',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => '20000.0000', 'credit' => '0.0000'],
            ['company_id' => 4, 'account_id' => 20, 'debit' => '0.0000', 'credit' => '20000.0000'],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());
        $service->reverse($batch->fresh(), '2026-07-01', 'Date entered incorrectly');

        $draft = $service->copyReversedBatchToDraft($batch->fresh(), '2026-06-30', 'Replace on correct date', 7);
        $repeated = $service->copyReversedBatchToDraft($batch->fresh(), '2026-06-30', 'Repeated request', 7);

        $this->assertSame($draft->id, $repeated->id);
        $this->assertSame('Draft', $draft->status);
        $this->assertSame('2026-06-30', $draft->conversion_date->format('Y-m-d'));
        $this->assertSame(2, $draft->lines()->count());
        $this->assertSame(20000.0, (float) $draft->total_debit);
        $this->assertSame(20000.0, (float) $draft->total_credit);
        $this->assertSame(0, $draft->transactions()->count());
    }

    public function test_posted_targets_can_be_revised_without_retyping_and_replace_the_prior_version(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-TARGET-V1', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'go_live_date' => '2026-07-02', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => '10000.0000', 'credit' => '0.0000'],
            ['company_id' => 4, 'account_id' => 20, 'debit' => '0.0000', 'credit' => '10000.0000'],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());

        $revision = $service->createRevisionDraft($batch->fresh(), 'Correct one target balance', 7);
        $this->assertSame(2, $revision->lines()->count());
        $revision->lines()->where('account_id', 10)->update(['debit' => '12000.0000']);
        $revision->lines()->where('account_id', 20)->update(['credit' => '12000.0000']);
        $service->markValidated($revision->fresh());
        $service->approve($revision->fresh(), 7);
        $service->post($revision->fresh());

        $this->assertSame('Reversed', $batch->fresh()->status);
        $this->assertSame('Posted', $revision->fresh()->status);
        $this->assertSame($batch->id, $revision->supersedes_batch_id);
        $this->assertSame(12000.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 10)->sum(DB::raw('debit-credit')));
        $this->assertSame(2, Transaction::withoutGlobalScopes()->where('correction_type', 'conversion_revision_reversal')->count());
    }

    public function test_later_conversion_work_resumes_and_replaces_the_complete_target_set(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '130', 'name' => 'Security Deposit', 'category' => 'Assets', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 40, 'company_id' => 4, 'account_number' => '3001', 'name' => "Owner's Capital", 'category' => 'Equity', 'type' => 'Account', 'system_account_type' => null],
        ]);

        $original = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-FIRST-ACCOUNTS', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $original->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 10000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 20, 'debit' => 0, 'credit' => 10000],
        ]);

        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($original);
        $service->approve($original->fresh(), 7);
        $service->post($original->fresh());

        $revision = $service->createRevisionDraft($original->fresh(), 'Finish remaining conversion accounts', 7);
        $revision->lines()->where('account_id', 10)->update(['debit' => 12000]);
        $revision->lines()->create(['company_id' => 4, 'account_id' => 30, 'debit' => 2500, 'credit' => 0]);
        $service->markValidated($revision->fresh());
        $service->approve($revision->fresh(), 7);
        $service->post($revision->fresh());

        $this->assertSame('Reversed', $original->fresh()->status);
        $this->assertSame('Posted', $revision->fresh()->status);
        $this->assertSame(12000.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 10)->sum(DB::raw('debit-credit')));
        $this->assertSame(2500.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 30)->sum(DB::raw('debit-credit')));
        $this->assertSame(-14500.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 20)->sum(DB::raw('debit-credit')));
        $this->assertSame(1, OpeningBalanceBatch::where('status', 'Posted')->whereDate('closing_date', '2026-07-01')->count());

        $duplicateTarget = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-LATER-DUPLICATE-ACCOUNT', 'source_system' => 'MANUAL',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $duplicateTarget->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 500, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 500],
        ]);

        try {
            $service->markValidated($duplicateTarget);
            $this->fail('A second independent conversion set must not be posted for the same balance date.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Continue or revise the existing conversion balance set', $exception->getMessage());
        }
    }

    public function test_multiple_legacy_posted_sets_are_not_implicitly_consolidated(): void
    {
        foreach ([1, 2] as $version) {
            $batch = OpeningBalanceBatch::create([
                'company_id' => 4, 'reference' => 'LEGACY-OB-' . $version, 'source_system' => 'LEGACY',
                'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Posted',
            ]);
            $batch->lines()->createMany([
                ['company_id' => 4, 'account_id' => 10, 'debit' => 1000 * $version, 'credit' => 0],
                ['company_id' => 4, 'account_id' => 20, 'debit' => 0, 'credit' => 1000 * $version],
            ]);
        }

        try {
            app(OpeningBalanceConversionService::class)->createRevisionDraft(
                OpeningBalanceBatch::where('reference', 'LEGACY-OB-2')->firstOrFail(),
                'Continue balances',
                7
            );
            $this->fail('Ambiguous legacy Posted sets must require an approved consolidation repair.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('2 legacy Posted conversion sets', $exception->getMessage());
        }

        $this->assertSame(2, OpeningBalanceBatch::where('status', 'Posted')->count());
        $this->assertDatabaseMissing('opening_balance_batches', ['batch_kind' => 'revision']);
    }

    public function test_later_import_replaces_matching_targets_and_preserves_other_current_balances(): void
    {
        DB::table('chart_of_accounts')->insert([
            'id' => 30, 'company_id' => 4, 'account_number' => '130', 'name' => 'Security Deposit',
            'category' => 'Assets', 'type' => 'Account', 'system_account_type' => null,
        ]);
        $posted = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-IMPORT-BASE', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $posted->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 10000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 20, 'debit' => 0, 'credit' => 10000],
        ]);
        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($posted);
        $service->approve($posted->fresh(), 7);
        $service->post($posted->fresh());

        $import = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-LATER-IMPORT', 'source_system' => 'XERO',
            'source_file_reference' => 'later-balances.xlsx', 'source_file_hash' => str_repeat('a', 64),
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $import->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 12000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 30, 'debit' => 2500, 'credit' => 0],
        ]);

        $revision = $service->mergeImportedDraftIntoCurrentSet($import, 7);
        $this->assertSame('Merged', $import->fresh()->status);
        $this->assertSame('import_staging', $import->fresh()->batch_kind);
        $this->assertSame(12000.0, (float) $revision->lines()->where('account_id', 10)->value('debit'));
        $this->assertSame(2500.0, (float) $revision->lines()->where('account_id', 30)->value('debit'));

        $service->markValidated($revision->fresh());
        $this->assertSame(14500.0, (float) $revision->lines()->where('account_id', 20)->value('credit'));
        $this->assertSame((float) $revision->fresh()->total_debit, (float) $revision->fresh()->total_credit);
    }

    public function test_reconciliation_warns_when_pnl_and_retained_earnings_are_both_imported(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '200', 'name' => 'Sales', 'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 40, 'company_id' => 4, 'account_number' => '950', 'name' => 'Retained Earnings', 'category' => 'Equity', 'type' => 'Account', 'system_account_type' => 'RETAINED_EARNINGS'],
        ]);
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-RECON', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 10000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 30, 'debit' => 0, 'credit' => 7000],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 3000],
        ]);

        $reconciliation = app(OpeningBalanceReconciliationService::class)->forBatch($batch);
        $this->assertTrue($reconciliation['balanced']);
        $this->assertSame('7000.0000', $reconciliation['pnl_net_result']);
        $this->assertNotEmpty($reconciliation['warnings']);
    }

    public function test_duplicate_manual_conversion_accounts_are_rejected(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-DUPLICATE-LINES', 'source_system' => 'MANUAL',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 10, 'debit' => 5000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 10, 'debit' => 5000, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 20, 'debit' => 0, 'credit' => 10000],
        ]);

        $this->expectException(ValidationException::class);
        app(OpeningBalanceConversionService::class)->markValidated($batch);
    }

    public function test_reconciled_receivables_and_payables_are_not_duplicated_by_conversion_posting(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '610', 'name' => 'Accounts Receivable', 'category' => 'Assets', 'type' => 'Account', 'system_account_type' => 'ACCOUNTS_RECEIVABLE'],
            ['id' => 40, 'company_id' => 4, 'account_number' => '450', 'name' => 'Sales Revenue', 'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null],
            ['id' => 50, 'company_id' => 4, 'account_number' => '800', 'name' => 'Accounts Payable', 'category' => 'Liabilities', 'type' => 'Account', 'system_account_type' => 'ACCOUNTS_PAYABLE'],
            ['id' => 60, 'company_id' => 4, 'account_number' => '480', 'name' => 'Office Expense', 'category' => 'Expenses', 'type' => 'Account', 'system_account_type' => null],
        ]);
        DB::table('transactions')->insert([
            ['company_id' => 4, 'account_id' => 30, 'customer_id' => 101, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'INV-CONV-1', 'date' => '2026-07-01', 'type' => 'Sale', 'debit' => 100, 'credit' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 40, 'customer_id' => null, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'INV-CONV-1', 'date' => '2026-07-01', 'type' => 'Sale', 'debit' => 0, 'credit' => 100, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 60, 'customer_id' => null, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'BILL-CONV-1', 'date' => '2026-07-01', 'type' => 'Bill', 'debit' => 60, 'credit' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 50, 'customer_id' => null, 'vendor_id' => 201, 'payable_type' => 'vendor', 'payable_id' => 201, 'unique_no' => 'BILL-CONV-1', 'date' => '2026-07-01', 'type' => 'Bill', 'debit' => 0, 'credit' => 60, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-CONTROL-MATCH', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 30, 'debit' => 100, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 60, 'debit' => 80, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 120],
            ['company_id' => 4, 'account_id' => 50, 'debit' => 0, 'credit' => 60],
        ]);

        $service = app(OpeningBalanceConversionService::class);
        $service->markValidated($batch);
        $service->approve($batch->fresh(), 7);
        $service->post($batch->fresh());

        $this->assertSame(2, $batch->fresh()->transactions()->whereNull('reversal_of_transaction_id')->count());
        $this->assertFalse($batch->fresh()->transactions()->where('account_id', 30)->exists());
        $this->assertFalse($batch->fresh()->transactions()->where('account_id', 50)->exists());
        $this->assertSame(100.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 30)->sum(DB::raw('debit-credit')));
        $this->assertSame(-60.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 50)->sum(DB::raw('debit-credit')));
        $this->assertSame(-120.0, (float) Transaction::withoutGlobalScopes()->where('account_id', 40)->sum(DB::raw('debit-credit')));
    }

    public function test_receivables_control_mismatch_blocks_validation(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 30, 'company_id' => 4, 'account_number' => '610', 'name' => 'Accounts Receivable', 'category' => 'Assets', 'type' => 'Account', 'system_account_type' => 'ACCOUNTS_RECEIVABLE'],
            ['id' => 40, 'company_id' => 4, 'account_number' => '450', 'name' => 'Sales Revenue', 'category' => 'Revenue', 'type' => 'Account', 'system_account_type' => null],
        ]);
        DB::table('transactions')->insert([
            ['company_id' => 4, 'account_id' => 30, 'customer_id' => 102, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'INV-CONV-2', 'date' => '2026-07-01', 'type' => 'Sale', 'debit' => 90, 'credit' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 40, 'customer_id' => null, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'INV-CONV-2', 'date' => '2026-07-01', 'type' => 'Sale', 'debit' => 0, 'credit' => 90, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 30, 'customer_id' => null, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'JV-CONTROL-1', 'date' => '2026-07-01', 'type' => 'Journal_Entry', 'debit' => 10, 'credit' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 20, 'customer_id' => null, 'vendor_id' => null, 'payable_type' => null, 'payable_id' => null, 'unique_no' => 'JV-CONTROL-1', 'date' => '2026-07-01', 'type' => 'Journal_Entry', 'debit' => 0, 'credit' => 10, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-CONTROL-MISMATCH', 'source_system' => 'XERO',
            'closing_date' => '2026-07-01', 'conversion_date' => '2026-07-01', 'status' => 'Draft',
        ]);
        $batch->lines()->createMany([
            ['company_id' => 4, 'account_id' => 30, 'debit' => 100, 'credit' => 0],
            ['company_id' => 4, 'account_id' => 40, 'debit' => 0, 'credit' => 100],
        ]);

        try {
            app(OpeningBalanceConversionService::class)->markValidated($batch);
            $this->fail('A control target that differs from outstanding documents must not validate.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Target: 100.0000; document ledger: 90; difference: 10.0000', $exception->getMessage());
        }

        $this->assertSame('Draft', $batch->fresh()->status);
        $this->assertSame(0, $batch->transactions()->count());
    }

    public function test_manual_journal_controlled_reversal_updates_journal_and_ledger_once(): void
    {
        $periodId = DB::table('accounting_periods')->value('id');
        foreach ([[10, 140000, 0], [20, 0, 140000]] as [$accountId, $debit, $credit]) {
            DB::table('journal_entries')->insert([
                'company_id' => 4, 'accounting_period_id' => $periodId, 'account_id' => $accountId,
                'unique_no' => 'JV-TEST-1', 'date' => '2026-07-01', 'debit' => $debit, 'credit' => $credit,
                'posting_status' => 'posted', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('transactions')->insert([
                'company_id' => 4, 'accounting_period_id' => $periodId, 'account_id' => $accountId,
                'unique_no' => 'JV-TEST-1', 'date' => '2026-07-01', 'type' => 'Journal_Entry',
                'debit' => $debit, 'credit' => $credit, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $service = app(JournalCorrectionService::class);
        $firstReference = $service->reverse(4, 'JV-TEST-1', '2026-07-02', 'Correct classification', 7);
        $secondReference = $service->reverse(4, 'JV-TEST-1', '2026-07-02', 'Repeated request', 7);

        $this->assertSame('REV-JV-TEST-1', $firstReference);
        $this->assertSame($firstReference, $secondReference);
        $this->assertSame(2, DB::table('journal_entries')->where('unique_no', $firstReference)->count());
        $this->assertSame(2, DB::table('transactions')->where('unique_no', $firstReference)->count());
        $this->assertSame(140000.0, (float) DB::table('transactions')->where('unique_no', $firstReference)->where('account_id', 20)->value('debit'));
        $this->assertSame(140000.0, (float) DB::table('transactions')->where('unique_no', $firstReference)->where('account_id', 10)->value('credit'));
    }

    public function test_opening_balance_journal_cannot_bypass_batch_reversal(): void
    {
        $batch = OpeningBalanceBatch::create([
            'company_id' => 4, 'reference' => 'OB-BATCH-GUARD', 'journal_unique_no' => 'OB-BATCH-GUARD',
            'source_system' => 'MANUAL', 'closing_date' => '2026-06-30', 'conversion_date' => '2026-07-01',
            'status' => 'Posted', 'total_debit' => 140000, 'total_credit' => 140000,
        ]);
        DB::table('journal_entries')->insert([
            ['company_id' => 4, 'account_id' => 10, 'unique_no' => 'OB-BATCH-GUARD', 'date' => '2026-07-01', 'debit' => 140000, 'credit' => 0, 'posting_status' => 'posted', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => 4, 'account_id' => 20, 'unique_no' => 'OB-BATCH-GUARD', 'date' => '2026-07-01', 'debit' => 0, 'credit' => 140000, 'posting_status' => 'posted', 'created_at' => now(), 'updated_at' => now()],
        ]);

        try {
            app(JournalCorrectionService::class)->reverse(4, $batch->journal_unique_no, '2026-07-01', 'Wrong workflow', 7);
            $this->fail('Opening balance journals must use the batch reversal workflow.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Opening balance journals must be reversed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('journal_entries', ['unique_no' => 'REV-OB-BATCH-GUARD']);
    }

    public function test_dry_run_changes_no_accounting_rows_and_repair_is_idempotent(): void
    {
        $batchId = DB::table('opening_balance_batches')->insertGetId([
            'company_id' => 4, 'reference' => 'OB-A', 'source_system' => 'VERIFIED_IMPORT', 'closing_date' => '2026-06-30',
            'conversion_date' => '2026-07-01', 'status' => 'Posted', 'total_debit' => 140000, 'total_credit' => 140000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('opening_balance_lines')->insert([
            ['opening_balance_batch_id' => $batchId, 'company_id' => 4, 'account_id' => 10, 'debit' => 140000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['opening_balance_batch_id' => $batchId, 'company_id' => 4, 'account_id' => 20, 'debit' => 0, 'credit' => 140000, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $periodId = DB::table('accounting_periods')->value('id');
        foreach (['OB-A', 'OB-B'] as $reference) {
            DB::table('transactions')->insert([
                ['company_id' => 4, 'accounting_period_id' => $periodId, 'account_id' => 10, 'unique_no' => $reference, 'date' => '2026-07-01', 'type' => 'OpeningBalance', 'is_opening_balance' => 1, 'opening_balance_batch_id' => $batchId, 'debit' => 140000, 'credit' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['company_id' => 4, 'accounting_period_id' => $periodId, 'account_id' => 20, 'unique_no' => $reference, 'date' => '2026-07-01', 'type' => 'OpeningBalance', 'is_opening_balance' => 1, 'opening_balance_batch_id' => $batchId, 'debit' => 0, 'credit' => 140000, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        $service = app(OpeningBalanceRepairService::class);
        $before = DB::table('transactions')->count();
        $plan = $service->audit(4, '2026-07-01', [], 7);
        $this->assertSame($before, DB::table('transactions')->count());
        $this->assertTrue($plan->items->contains(fn ($item) => $item->issue_type === 'EXACT_DUPLICATE_OPENING_JOURNAL' && $item->severity === 'safe'));

        $run = $service->apply($plan, 8, 9, 'Reverse verified duplicate import');
        $this->assertSame('completed', $run->status);
        $afterFirstApply = DB::table('transactions')->count();
        $this->assertSame(6, $afterFirstApply);

        $rerun = $service->apply($plan, 8, 9, 'Idempotency verification');
        $this->assertSame($afterFirstApply, DB::table('transactions')->count());
        $this->assertSame(1, $rerun->summary['already_repaired']);
    }
}
