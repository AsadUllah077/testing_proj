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
        Schema::create('product_issues', function (Blueprint $box) {
            $box->id();
            $box->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $box->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $box->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $box->integer('quantity')->default(1);
            $box->dateTime('issue_date');
            $box->dateTime('return_date')->nullable();
            $box->string('status')->default('Issued'); // Issued, Returned
            $box->text('notes')->nullable();
            $box->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $box->timestamps();
            $box->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_issues');
    }
};
