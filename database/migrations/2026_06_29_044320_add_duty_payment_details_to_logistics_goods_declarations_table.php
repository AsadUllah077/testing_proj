<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->json('duty_payment_details')->nullable()->after('duty_payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->dropColumn('duty_payment_details');
        });
    }
};
