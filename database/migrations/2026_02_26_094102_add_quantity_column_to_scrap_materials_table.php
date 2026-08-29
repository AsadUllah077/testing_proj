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
         Schema::table('scrap_materials', function (Blueprint $table) {
            // Add quantity column as integer
            $table->integer('quantity')
                  ->nullable() // Make it nullable if you want to allow null values
                  ->default(0)  // Set default value to 0
                  ->after('quantity_type'); // Place it after quantity_type column

            // If you want it to be required (not nullable)
            // $table->integer('quantity')->default(0)->after('quantity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('scrap_materials', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
