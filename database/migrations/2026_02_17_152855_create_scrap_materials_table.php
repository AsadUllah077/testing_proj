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
        Schema::create('scrap_materials', function (Blueprint $table) {
            $table->id();
            $table->string('scrap_id')->unique();
            $table->string('item_description');

            // Changed from string to foreign key
            $table->foreignId('scrap_category_id')->nullable()->constrained('scrap_categories');
            $table->foreignId('scrap_type_id')->nullable()->constrained('scrap_types');

            $table->decimal('weight', 10, 2);
            $table->string('quantity_type');
            $table->text('scrap_reason');
            $table->string('source');
            $table->string('current_location');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'in_storage',
                'pending_disposal',
                'pending_recycling',
                'transferred',
                'disposed',
            ])->default('in_storage');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->index('scrap_id');
            $table->index('status');
            $table->index('scrap_type_id');
            $table->index('scrap_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrap_materials');
    }
};
