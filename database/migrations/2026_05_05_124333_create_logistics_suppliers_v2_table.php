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
        Schema::create('logistics_suppliers_v2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id'); // Mandatory
            $table->string('supplier_code')->unique()->nullable();
            
            // Company Details
            $table->string('company_name');
            $table->string('trade_name')->nullable();
            $table->string('business_type');
            $table->string('category');
            $table->string('country');
            $table->string('city')->nullable();
            $table->string('registration_no'); // NTN
            $table->string('strn_no')->nullable();
            $table->integer('year_established')->nullable();
            $table->decimal('annual_value', 15, 2)->nullable();

            // Key Contacts
            $table->string('primary_contact');
            $table->string('designation')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('finance_contact')->nullable();
            $table->string('logistics_contact')->nullable();

            // Commercial Terms
            $table->string('incoterms');
            $table->string('payment_terms')->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('moq')->nullable();
            $table->integer('lead_time')->nullable();

            // Compliance & Documents
            $table->string('ntn_cert')->nullable();
            $table->string('chamber_membership')->nullable();
            $table->string('export_license')->nullable();
            $table->string('quality_certs')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('aml_status')->default('Pending Screening');
            $table->string('pep_declared')->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('status')->default('Pending'); // Approved, Pending, Suspended, etc.
            
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_suppliers_v2');
    }
};
