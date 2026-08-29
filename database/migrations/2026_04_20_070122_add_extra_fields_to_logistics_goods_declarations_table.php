<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->string('collectorate')->nullable()->after('declaration_type');
            $table->string('bl_awb_number')->nullable()->after('collectorate');
            $table->string('ntn_cnic')->nullable()->after('importer');
            $table->string('country_of_origin')->nullable()->after('exporter');
            $table->string('exam_status')->default('no_exam')->after('status'); // no_exam / doc_check / physical_exam
            $table->string('duty_payment_mode')->nullable()->after('total_duty');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_goods_declarations', function (Blueprint $table) {
            $table->dropColumn(['collectorate', 'bl_awb_number', 'ntn_cnic', 'country_of_origin', 'exam_status', 'duty_payment_mode']);
        });
    }
};
