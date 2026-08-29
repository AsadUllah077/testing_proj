<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 18, 4)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('credit_limit_exceeded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('credit_limit_exceeded'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['credit_limit', 'payment_terms_days']));
    }
};
