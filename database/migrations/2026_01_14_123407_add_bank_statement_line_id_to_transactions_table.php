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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_statement_line_id')->nullable()->after('account_id');
            $table->foreign('bank_statement_line_id')->references('id')->on('bank_statement_lines')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_statement_line_id']);
            $table->dropColumn('bank_statement_line_id');
        });
    }
};
