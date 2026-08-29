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
        Schema::create('logistics_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_company_name');
            $table->string('tax_id');
            $table->string('business_type'); // sole_proprietorship, partnership, llc, corporation, cooperative
            $table->string('country_of_registration'); // US, GB, DE, FR, etc.

            // Contact Details
            $table->string('primary_contact_name')->nullable();
            $table->string('official_email')->nullable();
            $table->text('registered_office_address')->nullable();

            // Logistics Preferences
            $table->string('default_incoterms')->nullable(); // EXW, FCA, FOB, etc.
            $table->string('pref_port_of_loading')->nullable();
            $table->integer('lead_time_days')->nullable();

            // Payment Details
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->string('payment_currency')->nullable(); // USD, EUR, GBP, etc.

            // Document
            $table->string('business_license_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_suppliers');
    }
};
