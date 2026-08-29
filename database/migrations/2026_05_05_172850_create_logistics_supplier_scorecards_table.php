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
        Schema::create('logistics_supplier_scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('logistics_suppliers_v2')->onDelete('cascade');
            $table->string('evaluation_period');
            $table->integer('quality_score')->default(0);
            $table->integer('delivery_otd')->default(0);
            $table->integer('doc_compliance')->default(0);
            $table->integer('communication')->default(0);
            $table->integer('price_competitiveness')->default(0);
            $table->integer('overall_score')->default(0);
            $table->string('grade')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_supplier_scorecards');
    }
};
