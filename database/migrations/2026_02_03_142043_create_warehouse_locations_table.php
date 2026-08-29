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
        Schema::create('warehouse_locations', function (Blueprint $table) {
             $table->id();
            $table->string('name');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->unique(['name', 'company_id', 'deleted_at']); // Unique name per company (considering soft deletes)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
