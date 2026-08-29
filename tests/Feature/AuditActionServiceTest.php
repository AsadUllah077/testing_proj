<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\Account\AuditActionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditActionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    public function test_freeze_and_dismiss_update_sale_invoice_balance(): void
    {
        $sale = Sale::create([
            'company_id' => 1,
            'unique_no' => 'SO-00001',
            'invoice_no' => 'INV-001',
            'total_after_discount' => 100,
            'total' => 100,
            'paid_amount' => 60,
            'remaining_amount' => 40,
            'approval_status' => 'approved',
            'status' => 1,
        ]);

        $payment = $this->createTransaction([
            'company_id' => 1,
            'unique_no' => 'PAY-00001',
            'reference_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'type' => 'Sale',
            'debit' => 60,
            'credit' => 0,
        ]);
        $this->createTransaction([
            'company_id' => 1,
            'unique_no' => 'PAY-00001',
            'reference_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'type' => 'Sale',
            'debit' => 0,
            'credit' => 60,
        ]);

        $service = app(AuditActionService::class);
        $service->freeze($payment->id, 'test freeze');

        $sale->refresh();
        $this->assertSame(1, (int) $sale->status);
        $this->assertSame(0.0, (float) $sale->paid_amount);
        $this->assertSame(100.0, (float) $sale->remaining_amount);
        $this->assertSame('approved', $sale->approval_status);
        $this->assertSame(0, Transaction::withoutGlobalScopes()->where('unique_no', 'PAY-00001')->min('status'));

        $service->dismiss($payment->id, 'test dismiss');

        $sale->refresh();
        $this->assertSame(1, (int) $sale->status);
        $this->assertSame(60.0, (float) $sale->paid_amount);
        $this->assertSame(40.0, (float) $sale->remaining_amount);
        $this->assertSame('approved', $sale->approval_status);
        $this->assertSame(1, Transaction::withoutGlobalScopes()->where('unique_no', 'PAY-00001')->min('status'));
    }

    public function test_reverse_and_dismiss_update_bill_balance_and_journal_entries(): void
    {
        $bill = Bill::create([
            'company_id' => 1,
            'unique_no' => 'BILL-000001',
            'invoice_no' => 'BILL-001',
            'total_after_discount' => 200,
            'total' => 200,
            'paid_amount' => 50,
            'remaining_amount' => 150,
            'approval_status' => 'approved',
            'status' => 1,
        ]);

        $payment = $this->createTransaction([
            'company_id' => 1,
            'unique_no' => 'VPR-00001',
            'reference_id' => $bill->id,
            'invoice_no' => $bill->invoice_no,
            'type' => 'BillPayment',
            'debit' => 50,
            'credit' => 0,
        ]);
        $this->createTransaction([
            'company_id' => 1,
            'unique_no' => 'VPR-00001',
            'reference_id' => $bill->id,
            'invoice_no' => $bill->invoice_no,
            'type' => 'BillPayment',
            'debit' => 0,
            'credit' => 50,
        ]);

        $journalId = $this->createJournalEntry('VPR-00001');

        $service = app(AuditActionService::class);
        $service->reverse($payment->id, 'test reverse');

        $bill->refresh();
        $this->assertSame(1, (int) $bill->status);
        $this->assertSame(0.0, (float) $bill->paid_amount);
        $this->assertSame(200.0, (float) $bill->remaining_amount);
        $this->assertSame('approved', $bill->approval_status);
        $this->assertSame(2, Transaction::withoutGlobalScopes()->withTrashed()->where('unique_no', 'VPR-00001')->whereNotNull('deleted_at')->count());
        $this->assertDatabaseMissing('journal_entries', ['id' => $journalId, 'deleted_at' => null]);

        $service->dismiss($payment->id, 'test restore');

        $bill->refresh();
        $this->assertSame(1, (int) $bill->status);
        $this->assertSame(50.0, (float) $bill->paid_amount);
        $this->assertSame(150.0, (float) $bill->remaining_amount);
        $this->assertSame('approved', $bill->approval_status);
        $this->assertSame(0, Transaction::withoutGlobalScopes()->withTrashed()->where('unique_no', 'VPR-00001')->whereNotNull('deleted_at')->count());
        $this->assertDatabaseHas('journal_entries', ['id' => $journalId, 'deleted_at' => null]);
    }

    public function test_freezing_original_invoice_journal_does_not_change_sale_balance(): void
    {
        $sale = Sale::create([
            'company_id' => 1,
            'unique_no' => 'SO-00002',
            'invoice_no' => 'INV-002',
            'total_after_discount' => 100,
            'total' => 100,
            'paid_amount' => 20,
            'remaining_amount' => 80,
            'approval_status' => 'approved',
            'status' => 1,
        ]);

        $invoiceJournal = $this->createTransaction([
            'company_id' => 1,
            'unique_no' => $sale->unique_no,
            'reference_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'type' => 'Sale',
            'debit' => 100,
            'credit' => 0,
        ]);

        app(AuditActionService::class)->freeze($invoiceJournal->id, 'freeze invoice journal');

        $sale->refresh();
        $this->assertSame(1, (int) $sale->status);
        $this->assertSame(20.0, (float) $sale->paid_amount);
        $this->assertSame(80.0, (float) $sale->remaining_amount);
        $this->assertSame('approved', $sale->approval_status);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('sales', function (Blueprint $table) {
            $this->invoiceColumns($table);
            $table->unsignedBigInteger('customer_id')->nullable();
        });

        Schema::create('bills', function (Blueprint $table) {
            $this->invoiceColumns($table);
            $table->unsignedBigInteger('vendor_id')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('accounting_period_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('type')->nullable();
            $table->decimal('debit', 18, 2)->nullable();
            $table->decimal('credit', 18, 2)->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('unique_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->string('action');
            $table->string('status');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('loggable_type')->nullable();
            $table->unsignedBigInteger('loggable_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('action')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
        });
    }

    private function invoiceColumns(Blueprint $table): void
    {
        $table->id();
        $table->unsignedBigInteger('company_id')->nullable();
        $table->string('unique_no')->nullable();
        $table->string('invoice_no')->nullable();
        $table->decimal('total_after_discount', 18, 2)->default(0);
        $table->decimal('total', 18, 2)->default(0);
        $table->decimal('paid_amount', 18, 2)->default(0);
        $table->decimal('remaining_amount', 18, 2)->default(0);
        $table->string('approval_status')->default('approved');
        $table->integer('status')->default(1);
        $table->timestamps();
        $table->softDeletes();
    }

    private function createTransaction(array $attributes): Transaction
    {
        return Transaction::create(array_merge([
            'status' => 1,
        ], $attributes));
    }

    private function createJournalEntry(string $uniqueNo): int
    {
        return \DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'unique_no' => $uniqueNo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
