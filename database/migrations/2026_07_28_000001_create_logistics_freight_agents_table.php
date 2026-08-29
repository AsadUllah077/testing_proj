<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_freight_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_search');
            $table->string('email')->nullable();
            $table->string('contact', 50)->nullable();
            $table->timestamps();

            // Supports tenant-scoped prefix lookup and prevents spelling/case duplicates.
            $table->unique(
                ['company_id', 'name_search'],
                'logistics_freight_agents_company_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_freight_agents');
    }
};
