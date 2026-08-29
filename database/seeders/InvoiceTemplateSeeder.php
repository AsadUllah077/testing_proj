<?php

namespace Database\Seeders;

use App\Models\InvoiceTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InvoiceTemplate::create([
            'company_id' => '1',
            'name' => 'Default Invoice Template',
            'page_size' => 'A4',
            'measures_in' => 'cm',
            'top_margin' => 0.50,
            'bottom_margin' => 0.40,
            'address_padding' => 0.40,
            'font' => 'Arial',
            'font_size' => '9',

            // Titles
            'draft_invoice_title' => 'DRAFT INVOICE TITLE',
            'approved_invoice_title' => 'INVOICE',
            'overdue_invoice_title' => 'INVOICE',
            'credit_note_title' => 'CREDIT NOTE',
            'statement_title' => 'STATEMENT',
            'draft_purchase_order_title' => 'DRAFT PURCHASE ORDER',
            'purchase_order_title' => 'PURCHASE ORDER',
            'draft_quote_title' => 'DRAFT QUOTE',
            'quote_title' => 'QUOTE',
            'remittance_advice_title' => 'REMITTANCE ADVICE',
            'receipt_title' => 'RECEIPT',

            // Logo and display
            'logo_alignment' => 'right',
            'show_head_as' => 'logo',

            // Boolean display options
            'show_tax_numbers' => true,
            'show_column_heading' => true,
            'show_item_code' => true,
            'show_invoice_qty' => true,
            'show_payment_advance_cutaway' => false,
            'show_tax_column' => true,
            'show_registered_columns' => true,
            'show_logo' => true,
            'show_hs' => false,
            'hide_discount' => false,
            'show_contact_account_number' => false,

            // Payment service placeholders
            'payment_credit_card' => 'Select Payment Service',
            'payment_paypal' => 'Select Payment Service',
            'payment_bank' => 'Select Payment Service',

            // Text areas
            'terms_payment_advice' => 'Enter terms and payment advice...',
            'terms_quotes' => 'Enter quote terms...',
            'contact_details' => "Lumedon Trading LLC\n21 Charlestown Ave Ste 18F\nNEWARK, 19300\nUNITED STATES",
        ]);
    }
}
