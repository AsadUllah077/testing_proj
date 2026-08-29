<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_bank_charges', function (Blueprint $table) {
            $table->string('charge_currency', 3)->default('PKR');
        });

        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            $table->foreignId('bank_charge_id')
                ->nullable()
                ->unique()
                ->constrained('logistics_bank_charges')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_lc_payments', function (Blueprint $table) {
            $table->dropUnique(['bank_charge_id']);
            $table->dropConstrainedForeignId('bank_charge_id');
        });

        Schema::table('logistics_bank_charges', function (Blueprint $table) {
            $table->dropColumn('charge_currency');
        });
    }
};
