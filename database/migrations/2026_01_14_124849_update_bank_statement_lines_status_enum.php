<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop the check constraint and re-add it
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE bank_statement_lines DROP CONSTRAINT IF EXISTS bank_statement_lines_status_check");
            DB::statement("ALTER TABLE bank_statement_lines ADD CONSTRAINT bank_statement_lines_status_check CHECK (status IN ('Unreconciled', 'Reconciled', 'Deleted', 'Partially Reconciled'))");
        } else {
            Schema::table('bank_statement_lines', function (Blueprint $table) {
                $table->string('status', 50)->change(); // Convert to string for more flexibility on other DBs
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE bank_statement_lines DROP CONSTRAINT IF EXISTS bank_statement_lines_status_check");
            DB::statement("ALTER TABLE bank_statement_lines ADD CONSTRAINT bank_statement_lines_status_check CHECK (status IN ('Unreconciled', 'Reconciled', 'Deleted'))");
        }
    }
};
