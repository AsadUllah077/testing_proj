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
        // Add start_date column if it does not exist.
        Schema::table('surveys', function (Blueprint $table) {
            if (!Schema::hasColumn('surveys', 'start_date')) {
                $table->date('start_date')->nullable();
            }
        });
    }

    /**
 * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            if (Schema::hasColumn('surveys', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};
