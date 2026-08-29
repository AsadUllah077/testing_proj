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
        Schema::create('invoice_templates', function (Blueprint $table) {
           $table->id();
            $table->string('name')->default('Invoice Template');
            $table->string('page_size')->default('A4');
            $table->string('measures_in')->default('cm');
            $table->decimal('top_margin', 8, 2)->default(0.5);
            $table->decimal('bottom_margin', 8, 2)->default(0.4);
            $table->decimal('address_padding', 8, 2)->default(0.4);
            $table->string('font')->nullable();
            $table->string('font_size')->nullable();

            // Invoice Titles
            $table->string('draft_invoice_title')->nullable();
            $table->string('approved_invoice_title')->nullable();
            $table->string('overdue_invoice_title')->nullable();
            $table->string('credit_note_title')->nullable();
            $table->string('statement_title')->nullable();
            $table->string('draft_purchase_order_title')->nullable();
            $table->string('purchase_order_title')->nullable();
            $table->string('draft_quote_title')->nullable();
            $table->string('quote_title')->nullable();
            $table->string('remittance_advice_title')->nullable();
            $table->string('receipt_title')->nullable();

            // Logo options
            $table->string('logo_alignment')->default('right');
            $table->string('show_head_as')->default('logo');

            // Display options
            $table->boolean('show_tax_numbers')->default(true);
            $table->boolean('show_column_heading')->default(true);
            $table->boolean('show_item_code')->default(true);
            $table->boolean('show_invoice_qty')->default(true);
            $table->boolean('show_payment_advance_cutaway')->default(false);
            $table->boolean('show_tax_column')->default(true);
            $table->boolean('show_registered_columns')->default(true);
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_hs')->default(false);
            $table->boolean('hide_discount')->default(false);
            $table->boolean('show_contact_account_number')->default(false);

            // Text areas
            $table->text('terms_payment_advice')->nullable();
            $table->text('terms_quotes')->nullable();
            $table->text('contact_details')->nullable();

            // Payment services
            $table->string('payment_credit_card')->nullable();
            $table->string('payment_paypal')->nullable();
            $table->string('payment_bank')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
