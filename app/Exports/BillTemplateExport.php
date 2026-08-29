<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BillTemplateExport implements FromArray, WithHeadings
{
    /**
     * Return the headings for the CSV template
     */
    public function headings(): array
    {
        return [
            'vendor_name',
            'currency_code',
            'date',
            'invoice_no',
            'ref_no',
            'item',
            'description',
            'hs_code',
            'quantity',
            'kg',
            'price',
            'discount_percentage',
            'account_name',
            'tax_rate',
            'paid_amount',
            'payment_account_name',
            'note',
        ];
    }

    /**
     * Return sample data for the template
     */
    public function array(): array
    {
        return [
            [
                'vendor_name' => 'ABC Suppliers',
                'currency_code' => 'PKR',
                'date' => '15/12/2025',
                'invoice_no' => 'BILL-001',
                'ref_no' => 'REF-001',
                'item' => 'Office Supplies',
                'description' => 'Description of Office Supplies',
                'hs_code' => '123456',
                'quantity' => 10,
                'kg' => '',
                'price' => 100.00,
                'discount_percentage' => '',
                'account_name' => 'Office Expenses',
                'tax_rate' => '',
                'paid_amount' => 0.00,
                'payment_account_name' => '',
                'note' => 'Sample bill note',
            ],
        ];
    }
}