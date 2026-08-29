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
        Schema::create('progress_sheets', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');                  // Employee / technician name
            $table->text('working');                 // Description of work done
            $table->string('quantity')->nullable();  // Free-form, e.g. "2/1/1" or "//"
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');

            $table->index('company_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_sheets');
    }
};
