<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->string('documentary_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            $table->dropColumn('documentary_number');
        });
    }
};
