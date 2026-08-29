<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_actions', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }
        });

        DB::table('audit_actions')
            ->whereNull('company_id')
            ->update([
                'company_id' => DB::raw('(select transactions.company_id from transactions where transactions.id = audit_actions.transaction_id limit 1)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('audit_actions', function (Blueprint $table) {
            if (Schema::hasColumn('audit_actions', 'company_id')) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
