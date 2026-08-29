<?php

namespace App\Exports\Logistics;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function array(): array
    {
        return [
            [
                'import', // booking_type
                'BKG-102930', // booking_id
                'fcl_sea', // freight_mode
                'CN PVG', // origin_port
                'US LAX', // dest_port
                'Shanghai', // place_of_receipt
                'Los Angeles', // final_delivery_place
                'Tech Supplier Inc.', // shipper
                'Mega Retail Corp.', // consignee
                'Notify Party LLC', // notify_party
                'Global Forwarders', // freight_forwarder
                'Maersk', // carrier
                '40HC', // container_type
                '2', // container_count
                'standard', // service_level
                '2024-06-15', // etd
                '2024-07-01', // eta
                '8517.12', // hs_code
                'Smartphones and Accessories', // cargo_description
                '15000', // gross_weight
                '45.5', // volume_cbm
                '500000', // cargo_value
                'no', // dangerous_goods
                'Keep dry', // special_instructions
                'FOB', // incoterms
                'Collect', // freight_terms
                '2500', // freight_rate
                'PO-12345', // lc_po_reference
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'booking_type',
            'booking_id',
            'freight_mode',
            'origin_port',
            'dest_port',
            'place_of_receipt',
            'final_delivery_place',
            'shipper',
            'consignee',
            'notify_party',
            'freight_forwarder',
            'carrier',
            'container_type',
            'container_count',
            'service_level',
            'etd',
            'eta',
            'hs_code',
            'cargo_description',
            'gross_weight',
            'volume_cbm',
            'cargo_value',
            'dangerous_goods',
            'special_instructions',
            'incoterms',
            'freight_terms',
            'freight_rate',
            'lc_po_reference',
        ];
    }

    public function title(): string
    {
        return 'Bookings Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}
