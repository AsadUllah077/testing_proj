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
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('action'); // disposition_change, workflow_stage_change, location_change, etc.
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            // Indexes
            $table->index('product_id');
            $table->index('action');
            $table->index('company_id');
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
