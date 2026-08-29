<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hs_codes')) {
        Schema::create('hs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('hs_code')->nullable();
            $table->text('description')->nullable();
            $table->string('cd')->nullable();
            $table->timestamps();
        });
    }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hs_codes');
    }
};
