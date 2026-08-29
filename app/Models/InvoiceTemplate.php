<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'name',
        'page_size',
        'measures_in',
        'top_margin',
        'bottom_margin',
        'address_padding',
        'font',
        'font_size',
        'draft_invoice_title',
        'approved_invoice_title',
        'overdue_invoice_title',
        'credit_note_title',
        'statement_title',
        'draft_purchase_order_title',
        'purchase_order_title',
        'draft_quote_title',
        'quote_title',
        'remittance_advice_title',
        'receipt_title',
        'logo_alignment',
        'show_head_as',
        'show_tax_numbers',
        'show_column_heading',
        'show_item_code',
        'show_invoice_qty',
        'show_payment_advance_cutaway',
        'show_tax_column',
        'show_registered_columns',
        'show_logo',
        'show_hs',
        'hide_discount',
        'show_contact_account_number',
        'terms_payment_advice',
        'terms_quotes',
        'contact_details',
        'payment_credit_card',
        'payment_paypal',
        'payment_bank',
        'is_default',
        'logo',
    ];
}
