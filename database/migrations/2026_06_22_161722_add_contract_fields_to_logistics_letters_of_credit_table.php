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
        Schema::table('logistics_letters_of_credit', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_letters_of_credit', 'contract_number')) {
                $table->string('contract_number')->nullable()->after('lc_number');
            }
            if (! Schema::hasColumn('logistics_letters_of_credit', 'po_pi_indented_no')) {
                $table->string('po_pi_indented_no')->nullable()->after('latest_shipment_date');
            }
            if (! Schema::hasColumn('logistics_letters_of_credit', 'date_in_document')) {
                $table->date('date_in_document')->nullable()->after('po_pi_indented_no');
            }
            if (! Schema::hasColumn('logistics_letters_of_credit', 'booking_number')) {
                $table->string('booking_number')->nullable()->after('date_in_document');
            }
            if (! Schema::hasColumn('logistics_letters_of_credit', 'booking_date')) {
                $table->date('booking_date')->nullable()->after('booking_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_letters_of_credit', function (Blueprint $table) {
            $table->dropColumn([
                'contract_number',
                'po_pi_indented_no',
                'date_in_document',
                'booking_number',
                'booking_date'
            ]);
        });
    }
};
