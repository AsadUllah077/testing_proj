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
        Schema::table('logistics_booking_events', function (Blueprint $table) {
            $table->date('new_date')->nullable()->after('actual_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_booking_events', function (Blueprint $table) {
            $table->dropColumn('new_date');
        });
    }
};
