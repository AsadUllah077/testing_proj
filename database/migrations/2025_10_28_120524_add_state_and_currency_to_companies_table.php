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
        Schema::table('companies', function (Blueprint $table) {
             // Add new foreign keys (assuming related tables exist)
            $table->foreignId('state_id')
                  ->nullable()
                  ->constrained('states')
                  ->nullOnDelete()
                  ->after('country_id');

            $table->foreignId('currency_id')
                  ->nullable()
                  ->constrained('currencies')
                  ->nullOnDelete()
                  ->after('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
              // Drop foreign keys and columns if rolled back
            $table->dropForeign(['state_id']);
            $table->dropColumn('state_id');

            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
