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
        Schema::create('logistics_entities', function (Blueprint $table) {
            $table->id();
            $table->string('business_legal_name');
            $table->string('entity_type'); // Importer, Exporter, Both
            $table->string('tax_id_vat_number');
            $table->string('registration_country');

            // Contact Details
            $table->string('contact_full_name');
            $table->string('contact_email');
            $table->string('contact_phone_number');

            // Registered Address
            $table->string('registered_street_address');
            $table->string('registered_city');
            $table->string('registered_state_province');
            $table->string('registered_postal_code');

            // Mailing Address
            $table->string('mailing_street_address')->nullable();
            $table->string('mailing_city')->nullable();
            $table->string('mailing_state_province')->nullable();
            $table->string('mailing_postal_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_clients');
    }
};
