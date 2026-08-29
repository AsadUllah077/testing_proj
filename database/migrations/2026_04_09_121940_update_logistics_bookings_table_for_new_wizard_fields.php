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
            $table->string('pickup_first_name')->nullable()->after('pickup_time');
            $table->string('pickup_last_name')->nullable()->after('pickup_first_name');
            $table->string('pickup_email')->nullable()->after('pickup_last_name');
            $table->string('pickup_phone')->nullable()->after('pickup_email');
            
            $table->string('delivery_first_name')->nullable()->after('delivery_location_name');
            $table->string('delivery_last_name')->nullable()->after('delivery_first_name');
            
            $table->decimal('carrier_rate', 18, 2)->nullable()->after('carrier_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_first_name', 
                'pickup_last_name', 
                'pickup_email', 
                'pickup_phone',
                'delivery_first_name',
                'delivery_last_name',
                'carrier_rate'
            ]);
        });

        Schema::table('logistics_booking_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
