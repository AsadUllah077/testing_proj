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
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->string('second_party')->nullable()->after('service_type');
            $table->string('third_party')->nullable()->after('second_party');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->dropColumn(['second_party', 'third_party']);
        });
    }
};
