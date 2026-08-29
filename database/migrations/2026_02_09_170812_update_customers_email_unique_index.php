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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_email_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['company_id', 'email'], 'customers_company_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_company_email_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email', 'customers_email_unique');
        });
    }
};
