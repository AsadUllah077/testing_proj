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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('loggable_type');      // Model name (TaxRate)
            $table->unsignedBigInteger('loggable_id'); // Model ID
            $table->unsignedBigInteger('user_id')->nullable(); // User who performed action
            $table->string('action');             // created / updated / deleted
            $table->json('changes')->nullable();  // Old values / new values
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
