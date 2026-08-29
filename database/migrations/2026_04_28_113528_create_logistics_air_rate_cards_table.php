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
        Schema::create('logistics_air_rate_cards', function (Blueprint $table) {
            $table->string('ref_code', 30)->unique(); // e.g. ARC-TK-0001

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Carrier & Route

            $table->string('origin_port', 10);       // IATA e.g. LHE
            $table->string('destination_port', 10);  // IATA e.g. IST
            $table->string('via_hub', 10)->nullable(); // e.g. DXB

            // Weight Breakpoint
            $table->string('weight_breakpoint');     // min_charge, normal, +45kg, etc.

            // Rate Components (USD/KG)
            $table->decimal('charge_rate_usd_kg', 8, 4);
            $table->decimal('fuel_surcharge_fsc', 8, 4)->default(0);
            $table->decimal('security_xray', 8, 4)->default(0);
            $table->decimal('origin_handling', 8, 4)->default(0);
            $table->decimal('all_in_per_kg', 8, 4)->default(0); // computed & stored

            // Min Charge
            $table->decimal('minimum_charge_usd', 10, 2)->nullable();

            // Source / Notes
            $table->string('source_document')->nullable();
            $table->text('notes')->nullable();

            // Validity
            $table->date('rate_valid_from')->nullable();
            $table->date('rate_valid_to');

            // Status
            $table->string('status')->default('live'); // live, expired, draft

            // Ownership
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_air_rate_cards');
    }
};
