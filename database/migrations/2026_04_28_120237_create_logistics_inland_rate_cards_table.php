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
        Schema::create('logistics_inland_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('ref_code', 30)->unique(); // e.g. IRC-NLC-0001

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Carrier / MOU Party
            $table->string('carrier_mou_party');

            // Zone / Route
            $table->string('zone_route_description');
            $table->string('example_routes')->nullable();
            $table->string('transit_time')->nullable();         // e.g. "2–3 days"

            // Weight-based rates (PKR) — nullable for container-only zones
            $table->decimal('rate_up_to_500kg', 12, 2)->nullable();
            $table->decimal('rate_501_1000kg', 12, 2)->nullable();
            $table->decimal('rate_1001_5000kg', 12, 2)->nullable();

            // Container FTL rates (PKR)
            $table->decimal('rate_20ft_container', 12, 2)->nullable();
            $table->decimal('rate_40ft_container', 12, 2)->nullable();

            // MOU terms
            $table->date('mou_effective_date')->nullable();
            $table->date('mou_expiry_date')->nullable();
            $table->string('fuel_levy_clause')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Status
            $table->string('status')->default('active'); // active, expired, draft

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
        Schema::dropIfExists('logistics_inland_rate_cards');
    }
};
