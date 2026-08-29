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
        Schema::table('products', function (Blueprint $table) {
            // Drop the existing location string column
            $table->dropColumn('location');

            // Add the new foreign key column
            $table->foreignId('warehouse_location_id')
                  ->nullable()
                  ->after('id') // You can change 'id' to any existing column name after which you want to add this column
                  ->constrained('warehouse_locations')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key constraint and column
            $table->dropForeign(['warehouse_location_id']);
            $table->dropColumn('warehouse_location_id');

            // Add back the original location column
            $table->string('location')->nullable();
        });
    }
};
