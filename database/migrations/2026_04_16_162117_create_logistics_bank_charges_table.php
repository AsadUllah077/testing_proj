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
        Schema::create('logistics_bank_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_number')->unique();
            $table->string('charge_type')->nullable();    // lc_opening/lc_amendment/lc_negotiation/acceptance/swift/others
            $table->string('bank')->nullable();
            $table->string('linked_lc')->nullable();      // LC reference string
            $table->date('charge_date')->nullable();
            $table->decimal('amount_pkr', 15, 2)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->string('payment_mode')->nullable();   // cash / bank_transfer / cheque
            $table->string('bank_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_bank_charges');
    }
};
