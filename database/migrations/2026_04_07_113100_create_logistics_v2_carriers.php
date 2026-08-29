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
         if (!Schema::hasTable('logistics_v2_carriers')) {
        Schema::create('logistics_v2_carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Company Information
            $table->string('carrier_company_name');
            $table->string('trading_brand_name')->nullable();
            $table->string('transport_modes');
            $table->string('country_of_incorporation');
            $table->string('pakistan_liaison_office')->nullable();
            $table->string('ntn_scac_code')->nullable();
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('phone_pakistan_office')->nullable();
            $table->string('website')->nullable();

            // Service Coverage
            $table->string('key_origin_ports_airports')->nullable();
            $table->string('key_destination_markets')->nullable();
            $table->string('services_offered')->nullable();
            $table->string('tracking_portal_api')->nullable();

            // Compliance & Documents
            $table->string('fiata_iata_membership')->nullable();
            $table->string('insurance_marine_liability')->nullable();
            $table->string('aeo_ctpat_iso_certification')->nullable();
            $table->string('payment_terms');
            $table->decimal('credit_limit_usd', 12, 2)->nullable();
            $table->string('account_manager_internal')->nullable();
            $table->text('notes_onboarding_remarks')->nullable();
            $table->string('liability_insurance_path')->nullable();

            // Status
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_v2_carriers');
    }
};
