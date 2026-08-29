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
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique('vendors_email_unique');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->unique(['company_id', 'email'], 'vendors_company_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique('vendors_company_email_unique');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->unique('email', 'vendors_email_unique');
        });
    }
};
