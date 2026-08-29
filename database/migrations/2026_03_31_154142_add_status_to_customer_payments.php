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
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('payment_date');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('created_by');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');

            $table->foreign('confirmed_by')->references('id')->on('users');
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['status', 'confirmed_by', 'confirmed_at']);
        });
    }
};
