<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index(['company_id', 'account_number']);
                $table->index(['company_id', 'system_account_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'company_id')) {
                $table->dropIndex(['company_id', 'account_number']);
                $table->dropIndex(['company_id', 'system_account_type']);
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
