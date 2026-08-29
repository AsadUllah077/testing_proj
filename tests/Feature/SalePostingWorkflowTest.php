<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Transaction;
use App\Services\SaleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalePostingWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('unique_no');
            $table->string('invoice_no');
            $table->string('approval_status')->default('draft');
            $table->string('payment_status')->default('unpaid');
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('remaining_amount', 18, 4)->default(0);
            $table->boolean('email_on_approval')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sale_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('account_id');
            $table->string('unique_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('type');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->boolean('status')->default(false);
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('is_opening_balance')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_invoice_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedInteger('version');
            $table->string('event');
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_draft_journal_is_hidden_until_approval_posts_a_balanced_entry(): void
    {
        $saleId = DB::table('sales')->insertGetId([
            'company_id' => 1,
            'unique_no' => 'INV-2026-000001',
            'invoice_no' => 'CUST-001',
            'approval_status' => 'approved',
            'payment_status' => 'unpaid',
            'remaining_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sale = Sale::findOrFail($saleId);

        foreach ([[100, 0], [0, 100]] as [$debit, $credit]) {
            DB::table('transactions')->insert([
                'company_id' => 1,
                'reference_id' => $sale->id,
                'account_id' => 10,
                'unique_no' => $sale->unique_no,
                'invoice_no' => $sale->invoice_no,
                'type' => 'Sale',
                'debit' => $debit,
                'credit' => $credit,
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(0, Transaction::count());

        app(SaleService::class)->postApprovedSale($sale);

        $this->assertSame(2, Transaction::count());
        $this->assertEquals(100, Transaction::sum('debit'));
        $this->assertEquals(100, Transaction::sum('credit'));
        $this->assertDatabaseHas('sale_invoice_versions', ['sale_id' => $sale->id, 'event' => 'approved']);
    }

    public function test_unbalanced_invoice_cannot_be_posted(): void
    {
        $saleId = DB::table('sales')->insertGetId([
            'company_id' => 1,
            'unique_no' => 'INV-2026-000002',
            'invoice_no' => 'CUST-002',
            'approval_status' => 'approved',
            'remaining_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sale = Sale::findOrFail($saleId);

        DB::table('transactions')->insert([
            'company_id' => 1,
            'reference_id' => $sale->id,
            'account_id' => 10,
            'unique_no' => $sale->unique_no,
            'type' => 'Sale',
            'debit' => 100,
            'credit' => 90,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not balanced');

        app(SaleService::class)->postApprovedSale($sale);
    }
}
