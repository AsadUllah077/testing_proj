<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qms_policies', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title'); // QA, Sales, Customer Service, Supplier
            $table->string('version')->default('1.0')->after('category');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null')->after('version');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null')->after('owner_id');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null')->after('reviewer_id');
            $table->json('visibility')->nullable()->after('approver_id'); // roles/departments
            $table->timestamp('reviewed_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('qms_policies', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropForeign(['approver_id']);
            $table->dropColumn(['category', 'version', 'owner_id', 'reviewer_id', 'approver_id', 'visibility', 'reviewed_at']);
        });
    }
};
