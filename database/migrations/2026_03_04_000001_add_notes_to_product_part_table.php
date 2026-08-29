<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_part', function (Blueprint $table) {
            if (!Schema::hasColumn('product_part', 'notes')) {
                $table->text('notes')->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_part', function (Blueprint $table) {
            if (Schema::hasColumn('product_part', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
