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
        Schema::table('gate_passes', function (Blueprint $table) {
            if (! Schema::hasColumn('gate_passes', 'customer_info')) {
                $table->text('customer_info')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            if (Schema::hasColumn('gate_passes', 'customer_info')) {
                $table->dropColumn('customer_info');
            }
        });
    }
};
