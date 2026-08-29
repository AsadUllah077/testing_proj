<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_letters_of_credit', function (Blueprint $table) {
            $table->string('record_type')->default('lc')->after('lc_number'); // lc / bank_contract
            $table->string('reimbursing_bank')->nullable()->after('advising_bank');
            $table->string('payment_terms')->nullable()->after('tolerance_pct');
            $table->string('place_of_expiry')->nullable()->after('expiry_date');
            $table->text('special_conditions')->nullable()->after('documents_required');
        });

        Schema::table('logistics_bank_charges', function (Blueprint $table) {
            $table->unsignedBigInteger('lc_id')->nullable()->after('linked_lc');
            $table->foreign('lc_id')->references('id')->on('logistics_letters_of_credit')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_letters_of_credit', function (Blueprint $table) {
            $table->dropColumn(['record_type', 'reimbursing_bank', 'payment_terms', 'place_of_expiry', 'special_conditions']);
        });
        Schema::table('logistics_bank_charges', function (Blueprint $table) {
            $table->dropForeign(['lc_id']);
            $table->dropColumn('lc_id');
        });
    }
};
