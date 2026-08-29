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
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['monthly', 'quarterly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'settled', 'paid'])->default('open');
            $table->decimal('total_payable', 18, 4)->default(0);
            $table->decimal('total_receivable', 18, 4)->default(0);
            $table->decimal('net_amount', 18, 4)->default(0);
            $table->string('unique_no')->nullable(); // For settlement journal
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_periods');
    }
};
