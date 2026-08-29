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
             $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('extracted_by')->nullable()->constrained('users');
        $table->timestamp('extracted_at')->nullable();
                $table->string('name')->nullable();
        $table->string('value')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrap_materials', function (Blueprint $table) {
            //
        });
    }
};
