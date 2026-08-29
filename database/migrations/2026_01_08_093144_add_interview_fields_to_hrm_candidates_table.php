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
        Schema::table('hrm_candidates', function (Blueprint $table) {
            // Add interview fields
            $table->date('interview_date')->nullable()->after('stage');
            $table->time('interview_time')->nullable()->after('interview_date');
            $table->string('interview_location')->nullable()->after('interview_time');
            $table->string('interviewer')->nullable()->after('interview_location');
            $table->text('interview_notes')->nullable()->after('interviewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hrm_candidates', function (Blueprint $table) {
             $table->dropColumn([
                'interview_date',
                'interview_time',
                'interview_location',
                'interviewer',
                'interview_notes'
            ]);
        });
    }
};
