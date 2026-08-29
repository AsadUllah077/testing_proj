<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_lines', 'note')) {
                $table->text('note')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_lines', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
