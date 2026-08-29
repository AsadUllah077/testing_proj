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
        Schema::create('logistics_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // cancel, confirm, reschedule, etc.
            $table->string('reason_text');
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        // Seed initial reasons
        $reasons = [
            // Cancellation Reasons
            ['type' => 'cancel', 'reason_text' => 'Customs clearance problems'],
            ['type' => 'cancel', 'reason_text' => 'Incomplete documentation'],
            ['type' => 'cancel', 'reason_text' => 'Tariff or trade restrictions'],
            ['type' => 'cancel', 'reason_text' => 'Port congestion'],
            ['type' => 'cancel', 'reason_text' => 'Container shortages'],
            ['type' => 'cancel', 'reason_text' => 'Demurrage and detention'],
            ['type' => 'cancel', 'reason_text' => 'Payment or credit issues'],
            ['type' => 'cancel', 'reason_text' => 'Security holds'],
            ['type' => 'cancel', 'reason_text' => 'Operational disruptions'],
            ['type' => 'cancel', 'reason_text' => 'Incorrect booking details'],
            ['type' => 'cancel', 'reason_text' => 'Regulatory compliance failures'],
            ['type' => 'cancel', 'reason_text' => 'Overbooking by carrier'],
            
            // Confirmation Reasons
            ['type' => 'confirm', 'reason_text' => 'Documentation verified'],
            ['type' => 'confirm', 'reason_text' => 'Payment confirmed'],
            ['type' => 'confirm', 'reason_text' => 'Space allocated by carrier'],
            ['type' => 'confirm', 'reason_text' => 'Customer approved'],
            
            // Reschedule Reasons
            ['type' => 'reschedule', 'reason_text' => 'Vessel delay'],
            ['type' => 'reschedule', 'reason_text' => 'Equipment unavailability'],
            ['type' => 'reschedule', 'reason_text' => 'Shipper request'],
            ['type' => 'reschedule', 'reason_text' => 'Documentation pending'],
            ['type' => 'reschedule', 'reason_text' => 'Port congestion'],
        ];

        foreach ($reasons as $reason) {
            DB::table('logistics_reasons')->insert(array_merge($reason, [
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_reasons');
    }
};
