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
        Schema::create('statutory_bodies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // EPF, SOCSO, LHDN, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country_code', 3)->default('MYS'); // ISO country code
            $table->string('website')->nullable();
            $table->json('contact_info')->nullable(); // phone, email, address
            $table->integer('default_grace_days')->default(15);
            $table->integer('default_warning_days')->default(5);
            $table->decimal('penalty_rate_per_day', 5, 2)->default(0); // % per day for late payments
            $table->decimal('max_penalty_percentage', 5, 2)->nullable(); // Max penalty as % of amount
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['country_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_bodies');
    }
};
