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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('title');
            $table->text('product_details')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('disposition_id')->nullable()->constrained('dispositions')->onDelete('cascade');
            $table->foreignId('work_flow_stage_id')->nullable()->constrained('workflow_stages')->onDelete('cascade');
            $table->string('last_action')->nullable();
            $table->json('specifications')->nullable();
            $table->string('product_id_value')->nullable();
            $table->string('status')->default('Active');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('disposition_id');
            $table->index('work_flow_stage_id');
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
