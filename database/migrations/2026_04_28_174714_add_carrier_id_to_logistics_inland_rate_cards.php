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
        Schema::table('logistics_inland_rate_cards', function (Blueprint $table) {
            $table->foreignId('carrier_id')->constrained('logistics_v2_carriers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_inland_rate_cards', function (Blueprint $table) {
            $table->dropColumn('carrier_id');
        });
    }
};
