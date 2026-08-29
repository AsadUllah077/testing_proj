<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsBookingV2;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BookingsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Skip empty rows
        if (!isset($row['booking_type']) || empty($row['booking_type'])) {
            return null;
        }

        // Parse dates safely
        $etd = $this->transformDate($row['etd'] ?? null);
        $eta = $this->transformDate($row['eta'] ?? null);

        $booking = new LogisticsBookingV2([
            'booking_number' => 'BK-' . rand(99999, 1000000), // Or use from sheet if provided
            'booking_id' => $row['booking_id'] ?? null,
            'booking_type' => $row['booking_type'],
            'freight_mode' => $row['freight_mode'],
            'origin_port' => $row['origin_port'],
            'dest_port' => $row['dest_port'],
            'place_of_receipt' => $row['place_of_receipt'] ?? null,
            'final_delivery_place' => $row['final_delivery_place'] ?? null,
            'shipper' => $row['shipper'],
            'consignee' => $row['consignee'],
            'notify_party' => $row['notify_party'] ?? null,
            'freight_forwarder' => $row['freight_forwarder'] ?? null,
            'carrier' => $row['carrier'],
            'container_type' => $row['container_type'] ?? null,
            'container_count' => isset($row['container_count']) && is_numeric($row['container_count']) ? (int)$row['container_count'] : null,
            'service_level' => $row['service_level'] ?? 'standard',
            'etd' => $etd,
            'eta' => $eta,
            'hs_code' => $row['hs_code'],
            'cargo_description' => $row['cargo_description'],
            'gross_weight' => isset($row['gross_weight']) && is_numeric($row['gross_weight']) ? (float)$row['gross_weight'] : null,
            'volume_cbm' => isset($row['volume_cbm']) && is_numeric($row['volume_cbm']) ? (float)$row['volume_cbm'] : null,
            'cargo_value' => $row['cargo_value'] ?? null,
            'dangerous_goods' => $row['dangerous_goods'] ?? 'no',
            'special_instructions' => $row['special_instructions'] ?? null,
            'incoterms' => $row['incoterms'],
            'freight_terms' => $row['freight_terms'] ?? null,
            'freight_rate' => $row['freight_rate'] ?? null,
            'lc_po_reference' => $row['lc_po_reference'] ?? null,
            'status' => 'Booked',
            'pickup_date' => $etd,
            'is_draft' => false,
            'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);

        // Create corresponding folder in central Document Manager
        $folderName = $booking->booking_id ?? $booking->booking_number;
        \App\Models\LogisticFolder::firstOrCreate([
            'name' => $folderName,
            'parent_id' => null,
        ]);

        return $booking;
    }

    public function rules(): array
    {
        return [
            'booking_type' => 'required',
            'freight_mode' => 'required',
            'origin_port' => 'required',
            'dest_port' => 'required',
            'shipper' => 'required',
            'consignee' => 'required',
            'carrier' => 'required',
            'hs_code' => 'required',
            'cargo_description' => 'required',
            'incoterms' => 'required',
        ];
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            if (empty($value)) return null;
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
