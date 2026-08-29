<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_scan_logs', function (Blueprint $table) {
            // Store the resolved warehouse location name for easy display
            $table->string('warehouse_location_name')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('product_scan_logs', function (Blueprint $table) {
            $table->dropColumn('warehouse_location_name');
        });
    }
};
