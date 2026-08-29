<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SuppliersTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                // Company Details
                'Alpha Textiles Ltd.',      // company_name         *required
                'Alpha Tex',               // trade_name
                'Manufacturer',            // business_type        *required  (Manufacturer / Trader / Agent / Service Provider)
                'Textiles',                // category             *required
                'Pakistan',               // country              *required
                'Karachi',                // city
                'NTN-1234567',            // registration_no_ntn
                'STRN-9876543',           // strn_no
                '2005',                   // year_established
                '5000000',                // annual_value_usd

                // Contacts
                'Ahmed Khan',             // primary_contact      *required
                'CEO',                    // designation
                'ahmed@alphatex.com',     // email                *required
                '+92-300-1234567',        // phone                *required
                'finance@alphatex.com',   // finance_contact
                'logistics@alphatex.com', // logistics_contact

                // Commercial
                'FOB',                    // incoterms            *required  (FOB / CIF / EXW / DDP / DAP / CFR / FCA)
                'Net 60',                 // payment_terms
                'USD',                    // currency
                '250000',                 // credit_limit_usd
                '500 units',              // moq
                '21',                     // lead_time_days

                // Compliance
                'Karachi Chamber',        // chamber_membership
                'EXP-2024-001',           // export_license
                'ISO 9001, OEKO-TEX',     // quality_certs
                'HBL-ACC-123456',         // bank_account
                'HBL Trade Finance',      // bank_reference
                'Cleared',                // aml_status           (Cleared / Pending Screening / Flagged)
                'No AML concerns noted.', // internal_notes

                // Status — controls record approval state
                'Pending',                // status               (Approved / Pending / Suspended / Under Review)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            // Company Details
            'company_name',           // *required
            'trade_name',
            'business_type',          // *required
            'category',               // *required
            'country',                // *required
            'city',
            'registration_no_ntn',
            'strn_no',
            'year_established',
            'annual_value_usd',

            // Contacts
            'primary_contact',        // *required
            'designation',
            'email',                  // *required
            'phone',                  // *required
            'finance_contact',
            'logistics_contact',

            // Commercial
            'incoterms',              // *required
            'payment_terms',
            'currency',
            'credit_limit_usd',
            'moq',
            'lead_time_days',

            // Compliance
            'chamber_membership',
            'export_license',
            'quality_certs',
            'bank_account',
            'bank_reference',
            'aml_status',
            'internal_notes',

            // Status
            'status',                 // Approved | Pending | Suspended | Under Review
        ];
    }

    public function title(): string
    {
        return 'Suppliers Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28, 'B' => 18, 'C' => 18, 'D' => 16, 'E' => 14,
            'F' => 14, 'G' => 18, 'H' => 18, 'I' => 16, 'J' => 18,
            'K' => 22, 'L' => 16, 'M' => 26, 'N' => 18, 'O' => 22,
            'P' => 22, 'Q' => 12, 'R' => 16, 'S' => 10, 'T' => 18,
            'U' => 14, 'V' => 16, 'W' => 22, 'X' => 18, 'Y' => 24,
            'Z' => 20, 'AA' => 22, 'AB' => 18, 'AC' => 30, 'AD' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold + blue header row
        $sheet->getStyle('1:1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0078C8'],
            ],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }
}
