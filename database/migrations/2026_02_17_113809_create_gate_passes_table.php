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
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->string('gate_pass_id')->unique();
            $table->string('source_location');
            $table->dateTime('departure_date');
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('prepared_by');
            $table->enum('status', [
                'SECURITY_APPROVAL_PENDING',
                'QC_APPROVAL_PENDING',
                'QC_AND_SECURITY_APPROVED',
                'REJECTED'
            ])->default('QC_APPROVAL_PENDING');
            $table->text('qc_remarks')->nullable();
            $table->string('security_incharge_name')->nullable();
            $table->timestamp('qc_approved_at')->nullable();
            $table->timestamp('security_approved_at')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prepared_by')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->index('gate_pass_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_passes');
    }
};
