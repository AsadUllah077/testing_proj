<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_bookings', 'bank_instrument_type')) {
                $table->string('bank_instrument_type')->nullable();
            }

            if (! Schema::hasColumn('logistics_bookings', 'po_reference_number')) {
                $table->string('po_reference_number')->nullable();
            }

            if (! Schema::hasColumn('logistics_bookings', 'po_invoice_number')) {
                $table->string('po_invoice_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['bank_instrument_type', 'po_reference_number', 'po_invoice_number'] as $column) {
            if (Schema::hasColumn('logistics_bookings', $column)) {
                Schema::table('logistics_bookings', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
