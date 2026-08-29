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
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->string('fiscal_year', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->string('fiscal_year', 9)->change();
        });
    }
};
