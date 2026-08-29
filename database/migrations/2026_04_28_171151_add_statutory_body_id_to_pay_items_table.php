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
        Schema::table('pay_items', function (Blueprint $table) {
            $table->foreignId('statutory_body_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->index(['statutory_body_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pay_items', function (Blueprint $table) {
            $table->dropForeign(['statutory_body_id']);
            $table->dropColumn('statutory_body_id');
        });
    }
};
