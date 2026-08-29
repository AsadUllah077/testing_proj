<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDatabaseSequences extends Command
{
    protected $signature = 'db:fix-sequences';
    protected $description = 'Fix PostgreSQL sequences that are out of sync';

    public function handle()
    {
        $tables = [
            'bills',
            'sales',
            'vendors',
            'customers',
            'users',
            'chart_of_accounts',
            'bill_items',
            'sale_items',
            'journal_entries',
            'transactions',
            'employees',
            'purchase_orders',
            'credit_notes',
        ];

        foreach ($tables as $table) {
            $sequenceName = $table . '_id_seq';

            try {
                $maxId = DB::table($table)->max('id') ?? 0;
                $nextVal = $maxId + 1;

                DB::statement("SELECT setval('$sequenceName', $nextVal, false)");
                $this->info("Fixed sequence for $table: set to $nextVal");
            } catch (\Exception $e) {
                $this->warn("Could not fix sequence for $table: " . $e->getMessage());
            }
        }

        $this->info('Done fixing sequences!');
        return 0;
    }
}
