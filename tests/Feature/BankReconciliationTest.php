<?php

namespace Tests\Feature;

use App\Models\BankStatementLine;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Superseded by BankReconciliationControlsTest, which uses isolated fixtures and current bank-account classification.');
    }

    // use RefreshDatabase; // Be careful with RefreshDatabase on existing DBs, usually OK in testing env but User might not have separate test DB.
    // I will use DatabaseTransactions if available or just clean up manually.
    // Given the agent setup, maybe just manual cleanup?

    public function test_bank_reconciliation_page_loads()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $bankAccount = ChartOfAccount::factory()->create([
            'name' => 'Test Bank Account',
            'type' => 'Asset' // adjust as needed
        ]);

        $response = $this->get(route('account.bank-reconciliation.index', $bankAccount->id));

        $response->assertStatus(200);
        $response->assertSee('Test Bank Account');
    }

    public function test_upload_bank_statement()
    {
        $user = User::factory()->create();
        Company::factory()->create(['id' => 1]); // Ensure company 1 exists as hardcoded in controller

        $this->actingAs($user);

        $bankAccount = ChartOfAccount::factory()->create(['name' => 'Upload Bank']);

        $csvContent = "2025-01-01,100.00,Client A,Inv 123,Ref1\n2025-01-02,-50.00,Supplier B,Bill 456,Ref2";
        $file = UploadedFile::fake()->createWithContent('statement.csv', $csvContent);

        $response = $this->post(route('account.bank-reconciliation.upload', $bankAccount->id), [
            'file' => $file
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_statement_lines', [
            'bank_account_id' => $bankAccount->id,
            'amount' => 100.00,
            'payee' => 'Client A'
        ]);
        $this->assertDatabaseHas('bank_statement_lines', [
            'bank_account_id' => $bankAccount->id,
            'amount' => -50.00,
            'payee' => 'Supplier B'
        ]);
    }

    public function test_match_transaction()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $bankAccount = ChartOfAccount::factory()->create(['name' => 'Match Bank']);

        // Create Statement Line
        $line = BankStatementLine::create([
            'company_id' => 1,
            'bank_account_id' => $bankAccount->id,
            'date' => '2025-01-10',
            'amount' => 500.00,
            'status' => 'Unreconciled'
        ]);

        // Create Matching Transaction
        $transaction = Transaction::create([
            'company_id' => 1,
            'account_id' => $bankAccount->id,
            'date' => '2025-01-10',
            'debit' => 500.00,
            'credit' => 0,
            'status' => 0
        ]);

        $response = $this->post(route('account.bank-reconciliation.match'), [
            'line_id' => $line->id,
            'transaction_ids' => [$transaction->id]
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'bank_statement_line_id' => $line->id,
            'status' => 1
        ]);
    }
}
