<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logistics_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_carrier_name');
            $table->string('logistics_mode');
            $table->string('scac_iata_code')->nullable();

            // Compliance & Insurance
            $table->string('dot_number')->nullable();
            $table->string('mc_authority')->nullable();
            $table->string('safety_rating')->nullable();

            // Documents
            $table->string('liability_insurance_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_carriers');
    }
};
