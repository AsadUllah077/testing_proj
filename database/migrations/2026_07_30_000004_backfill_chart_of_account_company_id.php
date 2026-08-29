<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chart_of_accounts', 'company_id')) {
            return;
        }

        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if ($companyId) {
            DB::table('chart_of_accounts')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        //
    }
};
