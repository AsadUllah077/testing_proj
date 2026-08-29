<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_policy_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('qms_policies')->cascadeOnDelete();
            $table->string('version', 10);
            $table->foreignId('approved_by')->constrained('users');
            $table->timestamp('approved_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_policy_approvals');
    }
};
