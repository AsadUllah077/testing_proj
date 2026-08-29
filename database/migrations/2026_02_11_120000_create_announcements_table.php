<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('priority')->default('medium');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
