<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_term_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title')->default('Terms and Conditions');
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'is_default']);
        });

        DB::table('invoice_term_conditions')->insert([
            'company_id' => null,
            'title' => 'Terms and Conditions',
            'content' => implode(PHP_EOL, [
                'Payment is due by the invoice due date unless otherwise agreed in writing.',
                'All bank charges, transfer fees, and applicable taxes are the responsibility of the paying party unless stated otherwise.',
                'Goods or services remain subject to the agreed commercial terms, purchase order, contract, or service agreement.',
                'Any discrepancy must be reported in writing within seven business days of receiving this invoice.',
                'Late payments may be subject to additional charges where permitted by the governing agreement or applicable law.',
            ]),
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_term_conditions');
    }
};
