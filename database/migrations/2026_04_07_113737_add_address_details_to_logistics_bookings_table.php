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
            $table->string('origin_company')->nullable();
            $table->string('origin_street')->nullable();
            $table->string('origin_city')->nullable();
            $table->string('origin_state')->nullable();
            
            $table->string('dest_company')->nullable();
            $table->string('dest_street')->nullable();
            $table->string('dest_city')->nullable();
            $table->string('dest_state')->nullable();

            $table->string('reference_po')->nullable();
            $table->string('reference_billing')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            //
        });
    }
};
