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
            $table->date('pick_date')->nullable()->after('loading_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->dropColumn('pick_date');
        });
    }
};
