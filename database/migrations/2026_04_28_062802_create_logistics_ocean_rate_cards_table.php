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
        Schema::create('logistics_ocean_rate_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Reference
            $table->string('ref_code', 20)->unique(); // e.g. RC-0841

            // Source & Carrier
            $table->foreignId('carrier_id')->constrained('logistics_v2_carriers')->cascadeOnDelete();               // e.g. maersk, msc
            $table->string('rate_type');             // nac, spot, gri, pss
            $table->string('nac_quote_reference')->nullable();
            $table->string('source_document')->nullable();

            // Route & Equipment
            $table->string('origin_port', 10);       // UNLOCODE e.g. PKQCT
            $table->string('destination_port', 10);  // UNLOCODE e.g. GBLGP
            $table->string('equipment');             // 20ft_standard, 40ft_hc, etc.
            $table->unsignedSmallInteger('transit_time_days')->nullable();

            // Rate Components (USD)
            $table->decimal('ocean_freight', 10, 2);
            $table->decimal('baf', 10, 2)->default(0);
            $table->decimal('caf', 10, 2)->default(0);
            $table->decimal('eis_isps_other', 10, 2)->default(0);
            $table->decimal('thc_origin', 10, 2)->default(0);
            $table->decimal('thc_destination', 10, 2)->default(0);
            $table->decimal('all_in_total', 10, 2)->default(0); // computed & stored

            // Demurrage / Detention
            $table->unsignedTinyInteger('free_days_demurrage')->nullable();
            $table->decimal('demurrage_rate_after_free', 8, 2)->nullable();
            $table->unsignedTinyInteger('free_days_detention')->nullable();
            $table->decimal('detention_rate_after_free', 8, 2)->nullable();

            // Validity
            $table->date('valid_from');
            $table->date('valid_to');
            $table->unsignedSmallInteger('agreed_volume_teu_month')->nullable();
            $table->text('remarks_gri_pss_notes')->nullable();

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
        Schema::dropIfExists('logistics_ocean_rate_cards');
    }
};
