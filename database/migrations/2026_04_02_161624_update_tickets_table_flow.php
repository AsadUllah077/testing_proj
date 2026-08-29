<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            $table->timestamp('started_at')->nullable()->after('assigned_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->text('feedback')->nullable()->after('attachment');
        });

        try {
            DB::statement("ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_status_check");
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status::text IN ('open', 'assigned', 'in-progress', 'review', 'resolved', 'closed', 'completed'))");
        } catch (\Exception $e) {
            // Fallback: just change to string if constraint name is different
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('status')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'started_at', 'completed_at', 'feedback']);
        });

        try {
            DB::statement("ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_status_check");
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status::text IN ('open', 'in-progress', 'review', 'resolved', 'closed'))");
        } catch (\Exception $e) {
            // Fallback
        }
    }
};
