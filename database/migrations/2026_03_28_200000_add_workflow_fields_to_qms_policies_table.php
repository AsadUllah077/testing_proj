<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qms_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('qms_policies', 'rejection_notes')) {
                $table->text('rejection_notes')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qms_policies', function (Blueprint $table) {
            $table->dropColumn(['rejection_notes']);
        });
    }
};
