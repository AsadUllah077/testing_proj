<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Transaction;
use App\Services\BillService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillPostingWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('bills', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('vendor_id')->nullable(); $t->unsignedBigInteger('currency_id')->nullable(); $t->string('unique_no'); $t->string('invoice_no'); $t->string('approval_status')->default('draft'); $t->string('payment_status')->default('unpaid'); $t->decimal('paid_amount',18,4)->default(0); $t->decimal('remaining_amount',18,4)->default(0); $t->boolean('email_on_approval')->default(false); $t->timestamps(); $t->softDeletes(); });
        Schema::create('bill_items', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('bill_id'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('vendors', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('company_id'); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('currencies', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('code'); $t->string('symbol')->nullable(); $t->timestamps(); });
        Schema::create('transactions', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('reference_id'); $t->unsignedBigInteger('account_id'); $t->string('unique_no')->nullable(); $t->string('invoice_no')->nullable(); $t->string('type'); $t->decimal('debit',18,4)->default(0); $t->decimal('credit',18,4)->default(0); $t->boolean('status')->default(false); $t->unsignedBigInteger('posted_by')->nullable(); $t->timestamp('posted_at')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->boolean('is_opening_balance')->default(false); $t->timestamps(); $t->softDeletes(); });
        Schema::create('bill_invoice_versions', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('bill_id'); $t->unsignedInteger('version'); $t->string('event'); $t->json('snapshot'); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps(); });
    }

    public function test_draft_journal_is_hidden_until_approval_posts_balanced_entry(): void
    {
        $id = DB::table('bills')->insertGetId(['company_id'=>1,'unique_no'=>'BILL-2026-000001','invoice_no'=>'V-001','approval_status'=>'approved','remaining_amount'=>100,'created_at'=>now(),'updated_at'=>now()]);
        $bill = Bill::findOrFail($id);
        foreach ([[100,0],[0,100]] as [$debit,$credit]) DB::table('transactions')->insert(['company_id'=>1,'reference_id'=>$id,'account_id'=>10,'unique_no'=>$bill->unique_no,'invoice_no'=>$bill->invoice_no,'type'=>'Bill','debit'=>$debit,'credit'=>$credit,'status'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $this->assertSame(0, Transaction::count());
        app(BillService::class)->postApprovedBill($bill);
        $this->assertSame(2, Transaction::count());
        $this->assertEquals(Transaction::sum('debit'), Transaction::sum('credit'));
        $this->assertDatabaseHas('bill_invoice_versions', ['bill_id'=>$id,'event'=>'approved']);
    }

    public function test_unbalanced_bill_cannot_be_posted(): void
    {
        $id = DB::table('bills')->insertGetId(['company_id'=>1,'unique_no'=>'BILL-2026-000002','invoice_no'=>'V-002','approval_status'=>'approved','remaining_amount'=>100,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('transactions')->insert(['company_id'=>1,'reference_id'=>$id,'account_id'=>10,'unique_no'=>'BILL-2026-000002','type'=>'Bill','debit'=>100,'credit'=>90,'status'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $this->expectException(\RuntimeException::class); $this->expectExceptionMessage('not balanced');
        app(BillService::class)->postApprovedBill(Bill::findOrFail($id));
    }
}
