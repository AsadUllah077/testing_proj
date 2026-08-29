<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE qms_policies DROP CONSTRAINT IF EXISTS qms_policies_status_check');
        DB::statement("ALTER TABLE qms_policies ADD CONSTRAINT qms_policies_status_check CHECK (status IN ('draft', 'active', 'under_review', 'pending_approval', 'archived'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE qms_policies DROP CONSTRAINT IF EXISTS qms_policies_status_check');
        DB::statement("ALTER TABLE qms_policies ADD CONSTRAINT qms_policies_status_check CHECK (status IN ('draft', 'active', 'under_review', 'archived'))");
    }
};
