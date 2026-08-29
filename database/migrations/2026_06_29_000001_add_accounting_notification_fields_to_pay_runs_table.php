<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->string('accounting_notification_status', 30)->default('none')->after('approval_notes');
            $table->timestamp('accounting_notified_at')->nullable()->after('accounting_notification_status');
            $table->foreignId('accounting_notified_by')->nullable()->after('accounting_notified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_notified_by');
            $table->foreignId('accounting_posted_by')->nullable()->after('accounting_posted_at')->constrained('users')->nullOnDelete();

            $table->index(['company_id', 'status', 'approval_status', 'accounting_notification_status'], 'pay_runs_accounting_notification_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropIndex('pay_runs_accounting_notification_idx');
            $table->dropConstrainedForeignId('accounting_notified_by');
            $table->dropConstrainedForeignId('accounting_posted_by');
            $table->dropColumn([
                'accounting_notification_status',
                'accounting_notified_at',
                'accounting_posted_at',
            ]);
        });
    }
};
