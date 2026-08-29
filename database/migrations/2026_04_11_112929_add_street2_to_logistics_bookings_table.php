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
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->string('origin_street2')->nullable()->after('origin_street');
            $table->string('dest_street2')->nullable()->after('dest_street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->dropColumn(['origin_street2', 'dest_street2']);
        });
    }
};
