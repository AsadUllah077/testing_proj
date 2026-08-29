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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('reference_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Note: This might fail if there are non-integer values in the column when rolling back.
            // But for rollback purposes we attempt to revert.
            // In a real scenario, we might want to clean up data or accept that down isn't perfectly symmetric if data is dirty.
             $table->integer('reference_id')->nullable()->change();
        });
    }
};
