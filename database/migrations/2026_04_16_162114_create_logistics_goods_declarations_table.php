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
        Schema::create('logistics_goods_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('gd_number')->unique();
            $table->unsignedBigInteger('booking_v2_id')->nullable();
            $table->string('declaration_type')->nullable();  // import / export
            $table->string('importer')->nullable();
            $table->string('exporter')->nullable();
            $table->string('hs_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->decimal('net_weight', 12, 2)->nullable();
            $table->decimal('customs_value', 15, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            // Calculated duties
            $table->decimal('customs_duty', 15, 2)->nullable();
            $table->decimal('sales_tax', 15, 2)->nullable();
            $table->decimal('income_tax', 15, 2)->nullable();
            $table->decimal('additional_duty', 15, 2)->nullable();
            $table->decimal('total_duty', 15, 2)->nullable();
            $table->decimal('landed_cost', 15, 2)->nullable();
            $table->string('status')->default('Draft');      // Draft/Filed/Approved/Rejected
            $table->date('filed_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('booking_v2_id')
                  ->references('id')->on('logistics_bookings')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_goods_declarations');
    }
};
