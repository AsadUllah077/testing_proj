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
            // First, check if the current_location column exists and drop it
            if (Schema::hasColumn('scrap_materials', 'current_location')) {
                $table->dropColumn('current_location');
            }

            // Add the new foreign key column
            $table->foreignId('warehouse_location_id')
                  ->nullable()
                  ->after('id') // You can change this to after any existing column
                  ->constrained('warehouse_locations')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrap_materials', function (Blueprint $table) {
            // Drop the foreign key constraint and column
            $table->dropForeign(['warehouse_location_id']);
            $table->dropColumn('warehouse_location_id');

            // Add back the original current_location column
            $table->string('current_location')->nullable();
        });
    }
};
