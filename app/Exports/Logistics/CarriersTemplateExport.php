<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CarriersTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                // Company Information
                'Maersk Line',                  // carrier_company_name    *required
                'Maersk',                       // trading_brand_name
                'ocean_liner',                  // transport_modes         *required (ocean_liner / air_freight / inland_trucking / rail_freight / multimodal)
                'Denmark',                      // country_of_incorporation *required
                'Karachi',                      // pakistan_liaison_office
                'MAEU',                         // ntn_scac_code
                'Ali Hassan',                   // contact_person          *required
                'ali.hassan@maersk.com',        // contact_email           *required
                '+92-21-35680001',              // phone_pakistan_office
                'https://www.maersk.com',       // website

                // Service Coverage
                'PK KHI, CN PVG, AE JEA',      // key_origin_ports_airports
                'US LAX, UK FEL, DE HAM',       // key_destination_markets
                'fcl_sea',                       // services_offered        (fcl_sea / lcl_sea / air_express / air_standard / road_ftl / road_ltl / rail)
                'yes_both',                      // tracking_portal_api     (yes_web_portal / yes_api / yes_both / no)

                // Compliance & Documents
                'fiata_member',                  // fiata_iata_membership   (fiata_member / iata_member / both / none)
                'yes_coi',                       // insurance_marine_liability (yes_coi / yes_no_coi / no)
                'ISO 9001, AEO',                 // aeo_ctpat_iso_certification
                'net_30',                        // payment_terms           *required (net_30 / net_45 / net_60 / advance / cod)
                '500000',                        // credit_limit_usd
                'Sarah Khan',                    // account_manager_internal
                'Preferred ocean liner partner.', // notes_onboarding_remarks

                // Status
                'pending_approval',              // status (draft / pending_approval / approved / rejected)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            // Company Information
            'carrier_company_name',       // *required
            'trading_brand_name',
            'transport_modes',            // *required
            'country_of_incorporation',   // *required
            'pakistan_liaison_office',
            'ntn_scac_code',
            'contact_person',             // *required
            'contact_email',              // *required
            'phone_pakistan_office',
            'website',

            // Service Coverage
            'key_origin_ports_airports',
            'key_destination_markets',
            'services_offered',
            'tracking_portal_api',

            // Compliance & Documents
            'fiata_iata_membership',
            'insurance_marine_liability',
            'aeo_ctpat_iso_certification',
            'payment_terms',              // *required
            'credit_limit_usd',
            'account_manager_internal',
            'notes_onboarding_remarks',

            // Status
            'status',                     // draft | pending_approval | approved | rejected
        ];
    }

    public function title(): string
    {
        return 'Carriers Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, 'B' => 20, 'C' => 18, 'D' => 24, 'E' => 22,
            'F' => 14, 'G' => 22, 'H' => 28, 'I' => 20, 'J' => 28,
            'K' => 28, 'L' => 28, 'M' => 16, 'N' => 18, 'O' => 20,
            'P' => 24, 'Q' => 26, 'R' => 16, 'S' => 16, 'T' => 24,
            'U' => 30, 'V' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1:1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0078C8'],
            ],
        ]);

        $sheet->freezePane('A2');

        return [];
    }
}
