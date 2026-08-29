<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_transit_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_search');
            $table->timestamps();

            $table->unique(
                ['company_id', 'name_search'],
                'logistics_transit_stops_company_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_transit_stops');
    }
};
