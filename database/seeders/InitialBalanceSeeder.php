<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialBalanceSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {

            echo "=== Setting Initial Opening Balances ===\n";

            // ----------------------------------------------
            // 1. Define ONLY Balance-Sheet account balances
            // ----------------------------------------------
            $balances = [

                // ===== ASSETS =====
                '112' => 50000,  // Main Bank Account
                '113' => 10000,  // Savings Account
                '114' => 5000,   // Cash on Hand
                '120' => 3000,   // Accounts Receivable
                '131' => 20000,  // Inventory
                '167' => 15000,  // Computer Hardware / Fixed Asset

                // ===== LIABILITIES =====
                '210' => 3000,   // Accounts Payable
                '252' => 15000,  // Long-term Loan

                // ===== EQUITY (Will be overwritten automatically) =====
                '310' => 0,      // Owner Equity (calculated automatically)
                '330' => 0,      // Owner Drawings
            ];

            // ----------------------------------------------
            // 2. Apply initial balances to accounts
            // ----------------------------------------------
            foreach ($balances as $accNumber => $value) {
                $account = ChartOfAccount::where('account_number', $accNumber)->first();

                if (!$account) {
                    echo "⚠ Account {$accNumber} not found — skipping\n";
                    continue;
                }

                $account->initial_balance = $value;
                $account->save();

                echo "✔ Set initial balance for {$accNumber} ({$account->name}) = {$value}\n";
            }

            // ----------------------------------------------
            // 3. Compute Total Assets
            // ----------------------------------------------
            $assetTotal = ChartOfAccount::where('category', 'Assets')->sum('initial_balance');

            // ----------------------------------------------
            // 4. Compute Total Liabilities
            // ----------------------------------------------
            $liabilityTotal = ChartOfAccount::where('category', 'Liabilities')->sum('initial_balance');

            // ----------------------------------------------
            // 5. Compute Owner Equity = Assets – Liabilities
            // ----------------------------------------------
            $ownerEquity = $assetTotal - $liabilityTotal;

            ChartOfAccount::where('account_number', '310')->update([
                'initial_balance' => $ownerEquity
            ]);

            echo "-----------------------------------\n";
            echo "Assets Total:      {$assetTotal}\n";
            echo "Liabilities Total: {$liabilityTotal}\n";
            echo "Owner Equity Set To: {$ownerEquity}\n";
            echo "-----------------------------------\n";
            echo "📘 Opening balances successfully applied!\n";
        });
    }
}
