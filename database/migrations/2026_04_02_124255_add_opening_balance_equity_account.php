<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companyId = DB::table('companies')->value('id');

        // ── 1. Ensure Opening Balance Equity account exists (raw insert, no observers) ──
        $obeParentId = DB::table('chart_of_accounts')
            ->where('account_number', '300')
            ->value('id');

        $existing = DB::table('chart_of_accounts')
            ->where('account_number', '399')
            ->first();

        if (! $existing) {
            DB::table('chart_of_accounts')->insert([
                'account_number'  => '399',
                'name'            => 'Opening Balance Equity',
                'category'        => 'Equity',
                'type'            => 'Account',
                'is_system'       => true,
                'parent_id'       => $obeParentId,
                'initial_balance' => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $obeId = DB::table('chart_of_accounts')
            ->where('account_number', '399')
            ->value('id');

        // ── 2. Convert existing initial_balance values to journal entries ────
        $accounts = DB::table('chart_of_accounts')
            ->where('initial_balance', '!=', 0)
            ->where('type', 'Account')
            ->where('id', '!=', $obeId)
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        // Use the earliest active accounting period; fall back to today
        $period = DB::table('accounting_periods')
            ->where('is_active', true)
            ->orderBy('start_date')
            ->first();

        $date     = $period ? $period->start_date : now()->toDateString();
        $periodId = $period?->id;

        foreach ($accounts as $account) {
            $amount = abs((float) $account->initial_balance);
            if ($amount <= 0) continue;

            $ref = 'OBE-' . str_pad($account->id, 5, '0', STR_PAD_LEFT);

            // Idempotent — skip if already posted
            if (DB::table('transactions')->where('unique_no', $ref)->exists()) {
                continue;
            }

            // Debit-normal: Assets, Expenses, COGS → DR account / CR OBE
            // Credit-normal: Liabilities, Equity, Revenue → DR OBE / CR account
            $isDebitNormal = in_array($account->category, ['Assets', 'Expenses', 'COGS']);

            DB::table('transactions')->insert([
                [
                    'unique_no'            => $ref,
                    'date'                 => $date,
                    'account_id'           => $account->id,
                    'debit'                => $isDebitNormal ? $amount : 0,
                    'credit'               => $isDebitNormal ? 0 : $amount,
                    'description'          => 'Opening balance — ' . $account->name,
                    'accounting_period_id' => $periodId,
                    'type'                 => 'OpeningBalance',
                    'company_id'           => $companyId,
                    'created_by'           => null,
                    'updated_by'           => null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ],
                [
                    'unique_no'            => $ref,
                    'date'                 => $date,
                    'account_id'           => $obeId,
                    'debit'                => $isDebitNormal ? 0 : $amount,
                    'credit'               => $isDebitNormal ? $amount : 0,
                    'description'          => 'Opening balance — ' . $account->name,
                    'accounting_period_id' => $periodId,
                    'type'                 => 'OpeningBalance',
                    'company_id'           => $companyId,
                    'created_by'           => null,
                    'updated_by'           => null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ],
            ]);

            // Clear the field — transactions are now the source of truth
            DB::table('chart_of_accounts')
                ->where('id', $account->id)
                ->update(['initial_balance' => 0, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('transactions')->where('type', 'OpeningBalance')->delete();
        DB::table('chart_of_accounts')->where('account_number', '399')->delete();
    }
};
