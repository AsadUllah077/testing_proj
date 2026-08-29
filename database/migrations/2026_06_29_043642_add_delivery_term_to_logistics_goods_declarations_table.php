<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->string('delivery_term')->nullable()->after('customs_value');
            $table->decimal('delivery_term_value', 15, 2)->nullable()->after('delivery_term');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->dropColumn(['delivery_term', 'delivery_term_value']);
        });
    }
};
