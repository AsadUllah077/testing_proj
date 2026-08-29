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
        Schema::table('logistics_booking_legs', function (Blueprint $table) {
            if (!Schema::hasColumn('logistics_booking_legs', 'estimated_delivery_date')) {
                $table->date('estimated_delivery_date')->nullable()->after('eta');
            }
            if (!Schema::hasColumn('logistics_booking_legs', 'original_etd')) {
                $table->date('original_etd')->nullable()->after('estimated_delivery_date');
            }
            if (!Schema::hasColumn('logistics_booking_legs', 'original_eta')) {
                $table->date('original_eta')->nullable()->after('original_etd');
            }
            if (!Schema::hasColumn('logistics_booking_legs', 'timing_status')) {
                $table->string('timing_status')->default('pending')->after('original_eta');
            }
            if (!Schema::hasColumn('logistics_booking_legs', 'timing_reason')) {
                $table->text('timing_reason')->nullable()->after('timing_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_booking_legs', function (Blueprint $table) {
            $cols = ['estimated_delivery_date', 'original_etd', 'original_eta', 'timing_status', 'timing_reason'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('logistics_booking_legs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
