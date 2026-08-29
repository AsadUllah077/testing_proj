<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->nullable();
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('category')->nullable();
            $table->enum('status', ['open', 'in-progress', 'review', 'resolved', 'closed'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('due_date')->nullable();
            $table->string('region')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assigned_to')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            $table->index(['company_id', 'status']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
