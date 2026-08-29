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
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('type')->constrained('vendors')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->after('vendor_id')->constrained('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['vendor_id', 'customer_id']);
        });
    }
};
