<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opening_balance_batches', function (Blueprint $table) {
            $table->date('go_live_date')->nullable()->after('closing_date');
            $table->string('batch_kind', 30)->default('conversion')->after('source_file_reference');
            $table->foreignId('supersedes_batch_id')->nullable()->after('batch_kind')->constrained('opening_balance_batches')->nullOnDelete();
            $table->string('source_file_hash', 64)->nullable()->after('source_file_reference');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unique(['company_id', 'supersedes_batch_id'], 'opening_batch_single_revision_unique');
            $table->index(['company_id', 'closing_date', 'status'], 'opening_batch_balance_date_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('opening_balance_batches', function (Blueprint $table) {
            $table->dropUnique('opening_batch_single_revision_unique');
            $table->dropIndex('opening_batch_balance_date_lookup');
            $table->dropConstrainedForeignId('supersedes_batch_id');
            $table->dropColumn(['go_live_date', 'batch_kind', 'source_file_hash', 'reviewed_at', 'approved_at']);
        });
    }
};
