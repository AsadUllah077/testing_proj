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
            $table->decimal('remaining_amount', 15, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn('remaining_amount');
        });
    }
};
