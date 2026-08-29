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
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->decimal('carried_forward_amount', 15, 2)->default(0)->after('net_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_periods', function (Blueprint $table) {
            $table->dropColumn('carried_forward_amount');
        });
    }
};
