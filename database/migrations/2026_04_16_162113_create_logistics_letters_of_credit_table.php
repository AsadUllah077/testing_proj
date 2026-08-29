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
        Schema::create('logistics_letters_of_credit', function (Blueprint $table) {
            $table->id();
            $table->string('lc_number')->unique();
            $table->string('lc_type')->nullable();
            $table->string('issuing_bank')->nullable();
            $table->string('advising_bank')->nullable();
            $table->string('applicant')->nullable();
            $table->string('beneficiary')->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('latest_shipment_date')->nullable();
            $table->string('incoterms')->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();
            $table->text('goods_description')->nullable();
            $table->text('documents_required')->nullable();
            $table->decimal('tolerance_pct', 5, 2)->nullable();
            $table->string('status')->default('Draft');     // Draft/Active/Expired/Utilized/Cancelled
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_letters_of_credit');
    }
};
