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
        Schema::create('sla_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id')->unique();
            $table->foreignId('carrier_id')->constrained('logistics_v2_carriers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('transport_mode');
            $table->string('contract_term');
            $table->decimal('min_otd_guarantee', 5, 2);
            $table->decimal('max_claim_rate', 5, 2);
            $table->string('volume_commitment')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_expiry_date')->nullable();
            $table->string('rate_lock_period')->default('no_lock');
            $table->string('penalty_clause')->default('none');
            $table->string('penalty_rate_amount')->nullable();
            $table->string('review_frequency')->default('monthly');
            $table->text('special_conditions')->nullable();
            $table->enum('status', ['draft', 'active', 'breach', 'expired', 'terminated'])->default('draft');
            $table->enum('performance_status', ['met', 'marginal', 'breach', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_contracts');
    }
};
