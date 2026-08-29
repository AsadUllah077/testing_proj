<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales approval columns
        Schema::table('sales', function (Blueprint $table) {
            $table->string('approval_status')->default('draft')->after('status'); // draft|submitted|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->index('approval_status');
        });

        // Bills approval columns
        Schema::table('bills', function (Blueprint $table) {
            $table->string('approval_status')->default('draft')->after('status'); // draft|submitted|approved|rejected
            $table->foreignId('bill_approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approval_status');
            $table->timestamp('bill_approved_at')->nullable()->after('bill_approved_by');
            $table->text('bill_rejection_reason')->nullable()->after('bill_approved_at');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejection_reason']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropConstrainedForeignId('bill_approved_by');
            $table->dropColumn(['approval_status', 'bill_approved_at', 'bill_rejection_reason']);
        });
    }
};
