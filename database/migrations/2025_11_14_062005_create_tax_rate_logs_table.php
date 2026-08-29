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
        Schema::create('tax_rate_logs', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('tax_rate_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('event'); // created, updated, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->timestamps();

            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rate_logs');
    }
};
