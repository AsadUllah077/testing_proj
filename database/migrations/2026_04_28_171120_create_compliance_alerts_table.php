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
        Schema::create('compliance_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compliance_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_type'); // regulatory_change, deadline_reminder, penalty_warning, etc.
            $table->string('title');
            $table->text('message');
            $table->string('severity')->default('info'); // info, warning, error, critical
            $table->string('status')->default('active'); // active, acknowledged, resolved
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable(); // Additional alert-specific data
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['alert_type', 'severity']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_alerts');
    }
};
