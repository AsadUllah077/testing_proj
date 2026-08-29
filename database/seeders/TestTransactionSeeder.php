<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Schema;

class TestTransactionSeeder extends Seeder
{
    public function run()
    {
        $companyId   = 1;
        $periodId    = 20;
        $vendorId    = 2;
        $customerId  = 2;

        // Reset transaction table (PostgreSQL safe)
        Schema::disableForeignKeyConstraints();
        Transaction::truncate();
        Schema::enableForeignKeyConstraints();

        // -----------------------------
        // 📌 All Test Transactions
        // -----------------------------
        $transactions = [

            // ===== SALES =====
            ['2025-01-15', '112', 5000, 0, 'Cash sale of products'],
            ['2025-01-15', '410', 0, 5000, 'Cash sale of products'],

            ['2025-01-16', '120', 3000, 0, 'Credit sale to customer ABC Corp'],
            ['2025-01-16', '410', 0, 3000, 'Credit sale to customer ABC Corp'],

            // ===== EXPENSES =====
            ['2025-01-17', '610', 1200, 0, 'Monthly office rent payment'],
            ['2025-01-17', '112', 0, 1200, 'Monthly office rent payment'],

            ['2025-01-18', '620', 300, 0, 'Electricity bill for January'],
            ['2025-01-18', '112', 0, 300, 'Electricity bill for January'],

            ['2025-01-19', '640', 250, 0, 'Purchase of office supplies'],
            ['2025-01-19', '112', 0, 250, 'Purchase of office supplies'],

            // ===== PURCHASES =====
            ['2025-01-20', '131', 2000, 0, 'Purchase inventory from XYZ Suppliers'],
            ['2025-01-20', '210', 0, 2000, 'Purchase inventory from XYZ Suppliers'],

            ['2025-01-21', '167', 1500, 0, 'Purchase of computer equipment'],
            ['2025-01-21', '112', 0, 1500, 'Purchase of computer equipment'],

            // ===== COGS =====
            ['2025-01-10', '510', 5000, 0, 'Raw material purchase - January'],
            ['2025-01-10', '112', 0, 5000, 'Raw material purchase - January'],

            ['2025-01-12', '511', 2200, 0, 'Direct labor wages for production - Week 2'],
            ['2025-01-12', '112', 0, 2200, 'Direct labor wages for production - Week 2'],

            ['2025-01-15', '513', 400, 0, 'Freight cost for incoming raw materials'],
            ['2025-01-15', '112', 0, 400, 'Freight cost for incoming raw materials'],

            ['2025-01-19', '517', 275, 0, 'Packaging materials used for finished goods'],
            ['2025-01-19', '112', 0, 275, 'Packaging materials used for finished goods'],

            ['2025-01-21', '519', 750, 0, 'Warehouse handling cost for inventory'],
            ['2025-01-21', '112', 0, 750, 'Warehouse handling cost for inventory'],

            // ===== JOURNAL =====
            ['2025-01-22', '112', 10000, 0, 'Owner capital investment'],
            ['2025-01-22', '310', 0, 10000, 'Owner capital investment'],

            ['2025-01-23', '112', 15000, 0, 'Business loan from ABC Bank'],
            ['2025-01-23', '252', 0, 15000, 'Business loan from ABC Bank'],

            // ===== COLLECTION / PAYMENTS =====
            ['2025-01-24', '112', 1500, 0, 'Collection from ABC Corp'],
            ['2025-01-24', '120', 0, 1500, 'Collection from ABC Corp'],

            ['2025-01-25', '210', 800, 0, 'Payment to XYZ Suppliers'],
            ['2025-01-25', '112', 0, 800, 'Payment to XYZ Suppliers'],

            ['2025-01-26', '330', 500, 0, 'Owner personal withdrawal'],
            ['2025-01-26', '112', 0, 500, 'Owner personal withdrawal'],
        ];

        // -----------------------------
        // 📌 Insert Transactions
        // -----------------------------
        $dataToInsert = [];
        $notFound = [];

        foreach ($transactions as [$date, $accountNumber, $debit, $credit, $desc]) {

            $account = ChartOfAccount::where('account_number', $accountNumber)->first();

            if (!$account) {
                $notFound[] = $accountNumber;
                continue;
            }

            $dataToInsert[] = [
                'date'                 => $date,
                'account_id'           => $account->id,
                'debit'                => $debit,
                'credit'               => $credit,
                'description'          => $desc,
                'company_id'           => $companyId,
                'accounting_period_id' => $periodId,
                'vendor_id'            => $vendorId,
                'customer_id'          => $customerId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ];
        }

        Transaction::insert($dataToInsert);

        // -----------------------------
        // 📌 Console Output
        // -----------------------------
        echo "\n===============================\n";
        echo "  Test Transactions Inserted\n";
        echo "===============================\n\n";
        echo "Total inserted: " . count($dataToInsert) . "\n";

        if (!empty($notFound)) {
            echo "\n⚠ Missing Accounts:\n";
            foreach ($notFound as $acc) {
                echo "- Account number not found: $acc\n";
            }
        }


        $this->displaySummary();
    }

    private function displaySummary()
    {
        $totalDebit  = Transaction::sum('debit');
        $totalCredit = Transaction::sum('credit');

        echo "\n=== TRANSACTION SUMMARY ===\n";
        echo "Total Debit:  " . number_format($totalDebit, 2) . "\n";
        echo "Total Credit: " . number_format($totalCredit, 2) . "\n";

        echo "Balanced? → ";
        echo $totalDebit == $totalCredit
            ? "✅ YES, Ledger is balanced\n"
            : "❌ NO, Ledger is imbalanced\n";

        echo "\n=== ACCOUNT BALANCES ===\n";

        ChartOfAccount::with('transactions')->chunk(50, function ($accounts) {
            foreach ($accounts as $account) {
                $balance = $account->transactions->sum('debit') - $account->transactions->sum('credit');

                if ($balance != 0) {
                    $side = $balance > 0 ? 'Debit' : 'Credit';
                    $balance = number_format(abs($balance), 2);
                    echo "{$account->account_number} - {$account->name}: {$balance} ({$side})\n";
                }
            }
        });
    }
}
