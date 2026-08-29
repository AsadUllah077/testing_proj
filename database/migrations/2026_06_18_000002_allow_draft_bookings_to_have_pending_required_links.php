<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logistics_bookings')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            if (Schema::hasColumn('logistics_bookings', 'hs_code_id')) {
                DB::statement('ALTER TABLE logistics_bookings ALTER COLUMN hs_code_id DROP NOT NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_bookings ALTER COLUMN carrier_id DROP NOT NULL');
            }

            if (Schema::hasTable('logistics_booking_items') && Schema::hasColumn('logistics_booking_items', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_booking_items ALTER COLUMN carrier_id DROP NOT NULL');
            }

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('logistics_bookings', 'hs_code_id')) {
                DB::statement('ALTER TABLE logistics_bookings MODIFY hs_code_id BIGINT UNSIGNED NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_bookings MODIFY carrier_id BIGINT UNSIGNED NULL');
            }

            if (Schema::hasTable('logistics_booking_items') && Schema::hasColumn('logistics_booking_items', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_booking_items MODIFY carrier_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('logistics_bookings')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            if (Schema::hasTable('logistics_booking_items') && Schema::hasColumn('logistics_booking_items', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_booking_items ALTER COLUMN carrier_id SET NOT NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'hs_code_id')) {
                DB::statement('ALTER TABLE logistics_bookings ALTER COLUMN hs_code_id SET NOT NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_bookings ALTER COLUMN carrier_id SET NOT NULL');
            }

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('logistics_booking_items') && Schema::hasColumn('logistics_booking_items', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_booking_items MODIFY carrier_id BIGINT UNSIGNED NOT NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'hs_code_id')) {
                DB::statement('ALTER TABLE logistics_bookings MODIFY hs_code_id BIGINT UNSIGNED NOT NULL');
            }

            if (Schema::hasColumn('logistics_bookings', 'carrier_id')) {
                DB::statement('ALTER TABLE logistics_bookings MODIFY carrier_id BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};
