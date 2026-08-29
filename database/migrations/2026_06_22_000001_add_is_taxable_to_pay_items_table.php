<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_items', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('base_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('pay_items', function (Blueprint $table) {
            $table->dropColumn('is_taxable');
        });
    }
};
