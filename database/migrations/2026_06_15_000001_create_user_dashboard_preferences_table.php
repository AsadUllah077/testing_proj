<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('dashboard_key');
            $table->json('preferences');
            $table->timestamps();

            $table->unique(['user_id', 'company_id', 'dashboard_key'], 'user_dashboard_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');
    }
};
