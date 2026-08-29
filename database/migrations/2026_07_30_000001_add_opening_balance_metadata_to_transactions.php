<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'opening_balance_batch_id')) {
                $table->foreignId('opening_balance_batch_id')->nullable()->after('bank_statement_line_id')->index();
            }

            if (! Schema::hasColumn('transactions', 'source_system')) {
                $table->string('source_system')->nullable()->after('type')->index();
            }

            if (! Schema::hasColumn('transactions', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('source_system')->index();
            }

            if (! Schema::hasColumn('transactions', 'is_opening_balance')) {
                $table->boolean('is_opening_balance')->default(false)->after('source_reference')->index();
            }

            if (! Schema::hasColumn('transactions', 'reversal_of_transaction_id')) {
                $table->foreignId('reversal_of_transaction_id')->nullable()->after('is_opening_balance')->constrained('transactions')->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'reversal_of_unique_no')) {
                $table->string('reversal_of_unique_no')->nullable()->after('reversal_of_transaction_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            foreach (['reversal_of_transaction_id'] as $foreignColumn) {
                if (Schema::hasColumn('transactions', $foreignColumn)) {
                    $table->dropConstrainedForeignId($foreignColumn);
                }
            }

            foreach ([
                'reversal_of_unique_no',
                'is_opening_balance',
                'source_reference',
                'source_system',
                'opening_balance_batch_id',
            ] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
