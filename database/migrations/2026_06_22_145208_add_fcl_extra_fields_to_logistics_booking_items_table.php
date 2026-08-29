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
        Schema::table('logistics_booking_items', function (Blueprint $table) {
            $table->string('container_number')->nullable();
            $table->integer('pallet_count')->nullable();
            $table->json('pallets')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_booking_items', function (Blueprint $table) {
            $table->dropColumn(['container_number', 'pallet_count', 'pallets']);
        });
    }
};
