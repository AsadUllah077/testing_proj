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
         Schema::create('gate_pass_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gate_pass_id');
            $table->unsignedBigInteger('product_id');
            $table->string('unique_id');
            $table->string('product_name');
            $table->string('category_name');
            $table->integer('quantity')->default(1);
            $table->json('product_details')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_pass_items');
    }
};
