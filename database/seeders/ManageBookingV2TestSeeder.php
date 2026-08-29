<?php

namespace Database\Seeders;

use App\Models\LogisticsBookingItem;
use App\Models\LogisticsBookingStatusHistory;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsTradeDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ManageBookingV2TestSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        DB::transaction(function () use ($companyId) {
            foreach ($this->bookingDefinitions($companyId) as $definition) {
                $this->seedBooking($definition, $companyId);
            }
        });

        $this->command?->info('Seeded 10 manage-booking v2 test bookings with child bookings, documents, items, and history.');
    }

    private function seedBooking(array $definition, int $companyId): void
    {
        $bookingData = Arr::except($definition, ['item', 'documents', 'status_history', 'children']);

        $booking = LogisticsBookingV2::withoutEvents(function () use ($definition, $bookingData) {
            return LogisticsBookingV2::updateOrCreate(
                ['booking_number' => $definition['booking_number']],
                $bookingData
            );
        });

        LogisticsBookingItem::where('booking_id', $booking->id)->delete();
        LogisticsTradeDocument::where('booking_id', $booking->id)->delete();
        LogisticsBookingStatusHistory::where('booking_id', $booking->id)->delete();

        LogisticsBookingV2::withoutEvents(function () use ($booking, $definition) {
            $booking->update([
                'inland_details' => $this->buildChildBookings($booking, $definition['children'] ?? []),
            ]);
        });

        LogisticsBookingItem::create(array_merge(
            [
                'booking_id' => $booking->id,
                'carrier_id' => $booking->carrier_id,
                'freight_mode' => $booking->freight_mode,
                'freight_sub_mode' => $booking->freight_sub_mode,
            ],
            $definition['item']
        ));

        foreach ($definition['documents'] as $document) {
            LogisticsTradeDocument::create(array_merge($document, [
                'booking_id' => $booking->id,
                'company_id' => $companyId,
            ]));
        }

        foreach ($definition['status_history'] as $history) {
            LogisticsBookingStatusHistory::create(array_merge($history, [
                'booking_id' => $booking->id,
                'company_id' => $companyId,
            ]));
        }
    }

    private function buildChildBookings(LogisticsBookingV2 $booking, array $children): array
    {
        $inlandDetails = [];

        foreach ($children as $child) {
            $childBookingNumber = $child['booking_number'];

            $inlandDetails[$childBookingNumber] = [
                'booking_identity' => [
                    'parent_id' => $booking->id,
                    'parent_booking_number' => $booking->booking_number,
                    'booking_number' => $childBookingNumber,
                    'freight_mode' => $child['freight_mode'],
                    'container_no' => $child['container_no'],
                    'do_number' => $child['do_number'],
                    'customs_channel' => $child['customs_channel'],
                    'status' => $child['status'],
                    'arrival_confirmed_at' => $child['arrival_confirmed_at'] ?? null,
                    'delivery_confirmed_at' => $child['delivery_confirmed_at'] ?? null,
                ],
                'inland_carrier' => [
                    'carrier' => $child['carrier'],
                    'carrier_contact' => $child['carrier_contact'],
                    'carrier_contract' => $child['carrier_contract'],
                    'pickup_point' => $child['pickup_point'],
                    'final_destination' => $child['final_destination'],
                    'etd' => $child['etd'],
                    'eta' => $child['eta'],
                    'delivery_date' => $child['delivery_date'],
                    'return_deadline' => $child['return_deadline'],
                    'freight_rate_pkr' => $child['freight_rate_pkr'],
                    'transit_stop' => $child['transit_stop'],
                    'route_option' => $child['route_option'],
                ],
                'vehicle_driver_assignment' => [
                    'vehicle_reg' => $child['vehicle_reg'],
                    'vehicle_model' => $child['vehicle_model'],
                    'truck_capacity' => $child['truck_capacity'],
                    'driver_name' => $child['driver_name'],
                    'driver_cnic' => $child['driver_cnic'],
                    'driver_mobile' => $child['driver_mobile'],
                    'co_driver' => $child['co_driver'],
                    'gps_unit_id' => $child['gps_unit_id'],
                    'gps_portal' => $child['gps_portal'],
                    'vehicle_inspection' => $child['vehicle_inspection'],
                ],
                'final_delivery_details' => [
                    'delivery_address' => $child['delivery_address'],
                    'receiving_contact' => $child['receiving_contact'],
                    'delivery_window' => $child['delivery_window'],
                    'unloading_method' => $child['unloading_method'],
                    'delivery_receipt_type' => $child['delivery_receipt_type'],
                    'post_delivery_inspection' => $child['post_delivery_inspection'],
                ],
            ];
        }

        return $inlandDetails;
    }

    private function bookingDefinitions(int $companyId): array
    {
        $fallbackCarrierIds = DB::table('logistics_v2_carriers')
            ->orderBy('id')
            ->limit(10)
            ->pluck('id')
            ->values();

        return [
            [
                'booking_number' => 'TEST-BKG-2026-001',
                'booking_type' => 'import',
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => 'direct',
                'origin_port' => 'Shanghai Yangshan, CN',
                'destination_port' => 'Port Qasim, Karachi',
                'place_of_receipt' => 'Shanghai CFS Warehouse',
                'final_delivery_place' => 'Safe Solutions Main Warehouse, Karachi',
                'exporter' => 'Eastern Textile Mills Ltd.',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions Customs Desk',
                'agent' => 'BlueWave Logistics Pakistan',
                'estimated_time_departure' => '2026-06-04 09:00:00',
                'estimated_time_delivery' => '2026-06-18 17:30:00',
                'service_level' => 'Standard FCL',
                'hs_code_id' => $this->resolveHsCodeId('5407.61'),
                'carrier_id' => $this->resolveCarrierId(['maersk', 'msc'], $fallbackCarrierIds->get(0)),
                'gross_weight' => 18250,
                'volume_cbm' => 61.8,
                'declared_cargo_value' => 132000,
                'dangerous_good' => false,
                'remarks' => 'Booked for June production replenishment. Pickup approval should trigger from today.',
                'incoterms' => 'CIF',
                'freight_terms' => 'Prepaid',
                'quoted_freight_rate' => 2950,
                'status' => 'Booked',
                'item' => [
                    'container_type' => '40HC',
                    'number_of_pallets' => 20,
                    'details_of_commodities' => 'Polyester woven fabric rolls for June production lot.',
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Primary invoice for parent shipment.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Packing breakdown by pallet and roll.', 'Parent Shipment', 'Received'),
                    $this->document('Bill of Lading', 'Original BL pending release from origin carrier.', 'Parent Shipment', 'Pending'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-29 10:15:00', 'Booked', 'Booking entered for planned June import movement.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-002',
                'booking_type' => 'import',
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => 'port_to_warehouse',
                'origin_port' => 'Jebel Ali, AE',
                'destination_port' => 'Port Qasim, Karachi',
                'place_of_receipt' => 'Dubai Cargo Village',
                'final_delivery_place' => 'Safe Solutions SITE Plant, Karachi',
                'exporter' => 'Al Noor Industrial Supplies LLC',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions Import Cell',
                'agent' => 'Anchor Freight Services',
                'estimated_time_departure' => '2026-06-02 13:00:00',
                'estimated_time_delivery' => '2026-06-09 11:00:00',
                'service_level' => 'FCL Priority',
                'hs_code_id' => $this->resolveHsCodeId('7304.19'),
                'carrier_id' => $this->resolveCarrierId(['msc', 'hapag'], $fallbackCarrierIds->get(1)),
                'gross_weight' => 62400,
                'volume_cbm' => 47.5,
                'declared_cargo_value' => 75500,
                'dangerous_good' => false,
                'remarks' => 'Parent shipment is at port. Child leg arrival confirmation should be testable today.',
                'incoterms' => 'FOB',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 2050,
                'status' => 'Pickup',
                'item' => [
                    'container_type' => '20GP',
                    'number_of_pallets' => 12,
                    'details_of_commodities' => 'Steel pipes and fittings for maintenance shutdown.',
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Cleared commercial invoice.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Verified packing list.', 'Parent Shipment', 'Received'),
                    $this->document('Delivery Order', 'Issued delivery order for inland release.', 'Parent Shipment', 'Received'),
                    $this->document('Gate Pass', 'Gate pass still awaited from terminal operator.', 'Parent Shipment', 'Pending'),
                    $this->document('Truck Assignment Sheet', 'Truck assignment for child leg.', 'Child Shipment', 'Received', 'TEST-CH-2026-002-A'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-30 09:00:00', 'Booked', 'Booking created and customs file opened.'),
                    $this->history('pickup_approved', '2026-06-02 13:00:00', '2026-06-03 07:30:00', 'Pickup', 'Pickup confirmed from port stack yard.'),
                ],
                'children' => [
                    $this->child(
                        'TEST-CH-2026-002-A',
                        'FTL',
                        'MSCU4821563',
                        'DO-2026-002-A',
                        'Green',
                        'Booked',
                        'TransPak Inland Movers',
                        'Saad Khan',
                        'TPK-FCL-2201',
                        'Port Qasim Container Yard',
                        'Safe Solutions SITE Plant, Karachi',
                        '2026-06-04 08:00:00',
                        '2026-06-04 18:30:00',
                        '2026-06-05 10:30:00',
                        '2026-06-08 00:00:00',
                        98000,
                        'National Highway Interchange',
                        'M-9 Corridor',
                        'TMA-231',
                        'Hino 500',
                        '28 MT',
                        'Akram Ali',
                        '42101-1234567-8',
                        '+92-300-1112233',
                        'Sajid Mehmood',
                        'GPS-TPK-231',
                        'TrackPro',
                        'Passed',
                        'Plot 19, SITE Area, Karachi',
                        'Muhammad Usman',
                        '09:00 - 18:00',
                        'Forklift',
                        'POD',
                        'Required'
                    ),
                ],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-003',
                'booking_type' => 'import',
                'freight_mode' => 'lcl_sea',
                'freight_sub_mode' => 'consolidated',
                'origin_port' => 'Singapore Port, SG',
                'destination_port' => 'Karachi Port',
                'place_of_receipt' => 'Jurong Freight Center',
                'final_delivery_place' => 'Safe Solutions Electronics Store, Karachi',
                'exporter' => 'Pacific Components Pte Ltd',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions SCM Team',
                'agent' => 'Kuehne + Nagel Pakistan',
                'estimated_time_departure' => '2026-05-28 06:30:00',
                'estimated_time_delivery' => '2026-06-04 15:00:00',
                'service_level' => 'Express LCL',
                'hs_code_id' => $this->resolveHsCodeId('8542.31'),
                'carrier_id' => $this->resolveCarrierId(['cosco', 'maersk'], $fallbackCarrierIds->get(2)),
                'gross_weight' => 3150,
                'volume_cbm' => 8.2,
                'declared_cargo_value' => 218750,
                'dangerous_good' => false,
                'remarks' => 'Delivery confirmation should be available today for parent booking.',
                'incoterms' => 'CIP',
                'freight_terms' => 'Prepaid',
                'quoted_freight_rate' => 12400,
                'status' => 'In-Transit',
                'item' => [
                    'item_description' => 'Electronic components, control boards, and spare capacitor kits.',
                    'package_type' => 'Carton',
                    'package' => 48,
                    'quantity' => 1280,
                    'length' => 1.2,
                    'width' => 0.8,
                    'height' => 0.9,
                    'dimension_unit' => 'm',
                    'weight' => 3150,
                    'image_path' => null,
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Validated supplier invoice.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'LCL packing list received.', 'Parent Shipment', 'Received'),
                    $this->document('Arrival Notice', 'Arrival notice shared by forwarder.', 'Parent Shipment', 'Received'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-22 11:00:00', 'Booked', 'Booking created for urgent spare parts.'),
                    $this->history('pickup_approved', '2026-05-28 06:30:00', '2026-05-28 06:30:00', 'Pickup', 'Cargo handed to origin consolidator.'),
                    $this->history('delivery_due', '2026-06-04 15:00:00', '2026-06-04 15:00:00', 'In-Transit', 'Parent delivery is due for confirmation today.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-004',
                'booking_type' => 'export',
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => 'multi_stop',
                'origin_port' => 'Karachi Port Qasim',
                'destination_port' => 'Rotterdam, NL',
                'place_of_receipt' => 'Safe Solutions Export Yard, Karachi',
                'final_delivery_place' => 'Rotterdam Distribution Hub',
                'exporter' => 'Safe Solutions Pvt Ltd',
                'importer' => 'Euro Textile BV',
                'notify_party' => 'Euro Textile BV Logistics Desk',
                'agent' => 'Maersk Pakistan',
                'estimated_time_departure' => '2026-05-25 14:00:00',
                'estimated_time_delivery' => '2026-06-14 18:00:00',
                'service_level' => 'FCL Export Premium',
                'hs_code_id' => $this->resolveHsCodeId('5208.21'),
                'carrier_id' => $this->resolveCarrierId(['maersk', 'cma'], $fallbackCarrierIds->get(3)),
                'gross_weight' => 15840,
                'volume_cbm' => 54.7,
                'declared_cargo_value' => 97800,
                'dangerous_good' => false,
                'remarks' => 'Second child leg is due for delivery confirmation today.',
                'incoterms' => 'FOB',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 3380,
                'status' => 'In-Transit',
                'item' => [
                    'container_type' => '40GP',
                    'number_of_pallets' => 18,
                    'details_of_commodities' => 'Export cotton fabric and stitched sample kits.',
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Export invoice cleared.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Export packing list received.', 'Parent Shipment', 'Received'),
                    $this->document('Shipper Letter of Instruction', 'SLI approved by export team.', 'Parent Shipment', 'Received'),
                    $this->document('Delivery Order', 'Child leg delivery order received.', 'Child Shipment', 'Received', 'TEST-CH-2026-004-A'),
                    $this->document('Proof of Delivery', 'Final customer POD awaited.', 'Child Shipment', 'Pending', 'TEST-CH-2026-004-B'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-18 08:45:00', 'Booked', 'Export file opened against customer PO ETB/2026/31.'),
                    $this->history('pickup_approved', '2026-05-25 14:00:00', '2026-05-25 14:00:00', 'Pickup', 'Factory stuffing completed and cargo dispatched.'),
                    $this->history('child_arrival_approved', '2026-06-01 12:00:00', '2026-06-01 12:15:00', 'In-Transit', 'First inland child stop confirmed.'),
                ],
                'children' => [
                    $this->child(
                        'TEST-CH-2026-004-A',
                        'FTL',
                        'MAEU7756421',
                        'DO-2026-004-A',
                        'Green',
                        'In-Transit',
                        'PortLink Transport',
                        'Ahsan Iqbal',
                        'PLT-EXP-4401',
                        'Rotterdam Terminal 1',
                        'Breda Cross-Dock, NL',
                        '2026-06-01 09:30:00',
                        '2026-06-01 12:00:00',
                        '2026-06-02 08:30:00',
                        '2026-06-05 00:00:00',
                        132000,
                        'A16 Logistics Spur',
                        'A16 / N3',
                        'RTM-512',
                        'DAF XF',
                        '24 MT',
                        'Jeroen Vink',
                        'NL-DR-00983',
                        '+31-6-22114455',
                        'Piet Sander',
                        'GPS-PLT-512',
                        'FleetWatch',
                        'Passed',
                        'Breda Textile Park, Hall 4',
                        'Mila de Jong',
                        '08:00 - 14:00',
                        'Dock Leveler',
                        'ePOD',
                        'Not Required',
                        '2026-06-01 12:10:00',
                        null
                    ),
                    $this->child(
                        'TEST-CH-2026-004-B',
                        'LTL',
                        'MAEU7756421',
                        'DO-2026-004-B',
                        'Green',
                        'In-Transit',
                        'PortLink Transport',
                        'Ahsan Iqbal',
                        'PLT-EXP-4402',
                        'Breda Cross-Dock, NL',
                        'Euro Textile BV Final Warehouse',
                        '2026-06-03 07:15:00',
                        '2026-06-03 12:30:00',
                        '2026-06-04 16:00:00',
                        '2026-06-06 00:00:00',
                        48000,
                        'Tilburg Sorting Point',
                        'N261',
                        'RTM-734',
                        'Volvo FH',
                        '12 MT',
                        'Sven Kramer',
                        'NL-DR-11452',
                        '+31-6-99881122',
                        'Lars van Dijk',
                        'GPS-PLT-734',
                        'FleetWatch',
                        'Passed',
                        'Industrieweg 18, Rotterdam',
                        'Ilse Vermeer',
                        '13:00 - 18:00',
                        'Manual',
                        'Stamped POD',
                        'Required'
                    ),
                ],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-005',
                'booking_type' => 'export',
                'freight_mode' => 'air',
                'freight_sub_mode' => 'express',
                'origin_port' => 'Karachi Jinnah, PK',
                'destination_port' => 'London Heathrow, UK',
                'place_of_receipt' => 'Safe Solutions Airport Cargo Desk',
                'final_delivery_place' => 'Northpoint Fashion DC, London',
                'exporter' => 'Safe Solutions Pvt Ltd',
                'importer' => 'Northpoint Fashion Ltd',
                'notify_party' => 'Northpoint Fashion Ltd',
                'agent' => 'SkyBridge Forwarding',
                'estimated_time_departure' => '2026-05-20 02:20:00',
                'estimated_time_delivery' => '2026-05-22 17:00:00',
                'service_level' => 'Air Express',
                'hs_code_id' => $this->resolveHsCodeId('6205.20'),
                'carrier_id' => $this->resolveCarrierId(['emirates', 'qatar', 'turkish'], $fallbackCarrierIds->get(4)),
                'gross_weight' => 2460,
                'volume_cbm' => 9.7,
                'declared_cargo_value' => 69500,
                'dangerous_good' => false,
                'remarks' => 'Fully delivered air export for completed-flow testing.',
                'incoterms' => 'FCA',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 8650,
                'status' => 'Delivered',
                'item' => [
                    'item_description' => 'Finished cotton shirts packed for retail replenishment.',
                    'package_type' => 'Carton',
                    'package' => 120,
                    'quantity' => 8500,
                    'length' => 0.6,
                    'width' => 0.4,
                    'height' => 0.4,
                    'dimension_unit' => 'm',
                    'weight' => 2460,
                    'image_path' => null,
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Air export invoice received.', 'Parent Shipment', 'Received'),
                    $this->document('Air Waybill', 'Master AWB issued and received.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Air packing list received.', 'Parent Shipment', 'Received'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-16 15:20:00', 'Booked', 'Air export booking created.'),
                    $this->history('pickup_approved', '2026-05-20 02:20:00', '2026-05-20 02:20:00', 'Pickup', 'Cargo accepted at airline terminal.'),
                    $this->history('delivery_approved', '2026-05-22 17:00:00', '2026-05-22 17:30:00', 'Delivered', 'Consignee delivery confirmed in London.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-006',
                'booking_type' => 'import',
                'freight_mode' => 'air',
                'freight_sub_mode' => 'priority',
                'origin_port' => 'Frankfurt, DE',
                'destination_port' => 'Lahore, PK',
                'place_of_receipt' => 'Frankfurt Cargo City',
                'final_delivery_place' => 'Safe Solutions Lahore Branch',
                'exporter' => 'EuroTech Process Systems GmbH',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions Plant Engineering',
                'agent' => 'DHL Global Forwarding',
                'estimated_time_departure' => '2026-06-07 05:10:00',
                'estimated_time_delivery' => '2026-06-09 20:00:00',
                'service_level' => 'Air Priority',
                'hs_code_id' => $this->resolveHsCodeId('8458.11'),
                'carrier_id' => $this->resolveCarrierId(['lufthansa', 'qatar', 'safe solution'], $fallbackCarrierIds->get(5)),
                'gross_weight' => 1780,
                'volume_cbm' => 6.4,
                'declared_cargo_value' => 91200,
                'dangerous_good' => false,
                'remarks' => 'Future-dated air import to test non-due bookings.',
                'incoterms' => 'DAP',
                'freight_terms' => 'Prepaid',
                'quoted_freight_rate' => 10950,
                'status' => 'Booked',
                'item' => [
                    'item_description' => 'CNC spindle kits and calibration tools.',
                    'package_type' => 'Crate',
                    'package' => 8,
                    'quantity' => 24,
                    'length' => 1.1,
                    'width' => 0.9,
                    'height' => 0.8,
                    'dimension_unit' => 'm',
                    'weight' => 1780,
                    'image_path' => null,
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Supplier invoice received.', 'Parent Shipment', 'Received'),
                    $this->document('Air Waybill', 'AWB copy pending from carrier.', 'Parent Shipment', 'Pending'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-06-01 13:15:00', 'Booked', 'Booked for upcoming engineering import.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-007',
                'booking_type' => 'import',
                'freight_mode' => 'truck',
                'freight_sub_mode' => 'ftl',
                'origin_port' => 'Wagah Border, PK',
                'destination_port' => 'Lahore Dry Port',
                'place_of_receipt' => 'Border Receiving Yard',
                'final_delivery_place' => 'Safe Solutions Chemical Store, Lahore',
                'exporter' => 'Punjab Color Chem India',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions Lahore SCM',
                'agent' => 'RoadLink Cargo',
                'estimated_time_departure' => '2026-06-03 07:00:00',
                'estimated_time_delivery' => '2026-06-05 14:00:00',
                'service_level' => 'Dedicated Truck',
                'hs_code_id' => $this->resolveHsCodeId('3204.16'),
                'carrier_id' => $this->resolveCarrierId(['safe solution', 'delaney', 'maersk'], $fallbackCarrierIds->get(6)),
                'gross_weight' => 14600,
                'volume_cbm' => 21.4,
                'declared_cargo_value' => 43800,
                'dangerous_good' => true,
                'remarks' => 'Road import already in pickup stage for Lahore delivery testing.',
                'incoterms' => 'CFR',
                'freight_terms' => 'Prepaid',
                'quoted_freight_rate' => 1650,
                'status' => 'Pickup',
                'item' => [
                    'item_description' => 'Reactive dyes packed in sealed drums.',
                    'package_type' => 'Drum',
                    'package' => 96,
                    'quantity' => 96,
                    'length' => 0.5,
                    'width' => 0.5,
                    'height' => 0.8,
                    'dimension_unit' => 'm',
                    'weight' => 14600,
                    'image_path' => null,
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Received at border desk.', 'Parent Shipment', 'Received'),
                    $this->document('Transit Permit', 'Road transit permit received.', 'Parent Shipment', 'Received'),
                    $this->document('Inspection Note', 'Hazmat inspection note pending at Lahore.', 'Parent Shipment', 'Pending'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-06-01 17:10:00', 'Booked', 'Truck movement created from border handover.'),
                    $this->history('pickup_approved', '2026-06-03 07:00:00', '2026-06-03 07:20:00', 'Pickup', 'Vehicle released after border formalities.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-008',
                'booking_type' => 'export',
                'freight_mode' => 'lcl_sea',
                'freight_sub_mode' => 'buyer_consolidation',
                'origin_port' => 'Karachi Port',
                'destination_port' => 'Hamburg, DE',
                'place_of_receipt' => 'Korangi Export Shed',
                'final_delivery_place' => 'Buyer Nominated CFS, Hamburg',
                'exporter' => 'Safe Solutions Pvt Ltd',
                'importer' => 'Textil AG Hamburg',
                'notify_party' => 'Textil AG Hamburg',
                'agent' => 'GreenPort Forwarders',
                'estimated_time_departure' => '2026-05-18 10:00:00',
                'estimated_time_delivery' => '2026-06-01 12:00:00',
                'service_level' => 'LCL Economy',
                'hs_code_id' => $this->resolveHsCodeId('6205.20'),
                'carrier_id' => $this->resolveCarrierId(['msc', 'maersk'], $fallbackCarrierIds->get(7)),
                'gross_weight' => 5200,
                'volume_cbm' => 19.3,
                'declared_cargo_value' => 41200,
                'dangerous_good' => false,
                'remarks' => 'Cancelled export retained for status filter testing.',
                'incoterms' => 'FOB',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 1890,
                'status' => 'Cancelled',
                'item' => [
                    'item_description' => 'Mixed apparel cartons for buyer consolidation.',
                    'package_type' => 'Carton',
                    'package' => 180,
                    'quantity' => 4200,
                    'length' => 0.6,
                    'width' => 0.4,
                    'height' => 0.4,
                    'dimension_unit' => 'm',
                    'weight' => 5200,
                    'image_path' => null,
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Invoice archived after cancellation.', 'Parent Shipment', 'Received'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-10 12:30:00', 'Booked', 'Export booking opened prior to buyer schedule.'),
                    $this->history('booking_cancelled', '2026-05-18 10:00:00', '2026-05-16 09:00:00', 'Cancelled', 'Buyer postponed the shipment window and cargo was cancelled.'),
                ],
                'children' => [],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-009',
                'booking_type' => 'import',
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => 'deconsolidation',
                'origin_port' => 'Ningbo, CN',
                'destination_port' => 'Karachi Port',
                'place_of_receipt' => 'Ningbo Supplier Hub',
                'final_delivery_place' => 'Safe Solutions North Warehouse, Karachi',
                'exporter' => 'Zhejiang Process Materials Co.',
                'importer' => 'Safe Solutions Pvt Ltd',
                'notify_party' => 'Safe Solutions Materials Planning',
                'agent' => 'CEVA Logistics Pakistan',
                'estimated_time_departure' => '2026-05-24 20:00:00',
                'estimated_time_delivery' => '2026-06-11 09:30:00',
                'service_level' => 'FCL Split Delivery',
                'hs_code_id' => $this->resolveHsCodeId('5205.11'),
                'carrier_id' => $this->resolveCarrierId(['evergreen', 'yang', 'hapag'], $fallbackCarrierIds->get(8)),
                'gross_weight' => 24750,
                'volume_cbm' => 39.6,
                'declared_cargo_value' => 58250,
                'dangerous_good' => false,
                'remarks' => 'Parent booking with one completed child and one active child for multi-leg tracking.',
                'incoterms' => 'FOB',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 2260,
                'status' => 'In-Transit',
                'item' => [
                    'container_type' => '40HC',
                    'number_of_pallets' => 16,
                    'details_of_commodities' => 'Cotton yarn lots split across two warehouse destinations.',
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Supplier invoice received.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Master packing list received.', 'Parent Shipment', 'Received'),
                    $this->document('Delivery Order', 'Child A delivery order received.', 'Child Shipment', 'Received', 'TEST-CH-2026-009-A'),
                    $this->document('Delivery Order', 'Child B delivery order received.', 'Child Shipment', 'Received', 'TEST-CH-2026-009-B'),
                    $this->document('Proof of Delivery', 'Child A POD received.', 'Child Shipment', 'Received', 'TEST-CH-2026-009-A'),
                    $this->document('Gate Pass', 'Child B final gate pass still pending.', 'Child Shipment', 'Pending', 'TEST-CH-2026-009-B'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-05-16 10:40:00', 'Booked', 'Booking created for split-yarn delivery.'),
                    $this->history('pickup_approved', '2026-05-24 20:00:00', '2026-05-24 20:00:00', 'Pickup', 'Carrier lifted container from supplier site.'),
                    $this->history('child_delivery_approved', '2026-06-02 11:30:00', '2026-06-02 11:45:00', 'In-Transit', 'First child destination delivered successfully.'),
                ],
                'children' => [
                    $this->child(
                        'TEST-CH-2026-009-A',
                        'FTL',
                        'YMLU2211432',
                        'DO-2026-009-A',
                        'Green',
                        'Delivered',
                        'NorthStar Inland',
                        'Qasim Jafri',
                        'NSI-SPLIT-91A',
                        'Karachi Port Off-Dock Terminal',
                        'Safe Solutions North Warehouse, Karachi',
                        '2026-06-01 08:00:00',
                        '2026-06-01 13:00:00',
                        '2026-06-02 11:30:00',
                        '2026-06-05 00:00:00',
                        94500,
                        'Sohrab Goth Junction',
                        'Super Highway',
                        'KHI-8124',
                        'Isuzu F-Series',
                        '18 MT',
                        'Waseem Ahmed',
                        '42101-6543210-9',
                        '+92-321-7788990',
                        'Adnan Ghafoor',
                        'GPS-NSI-8124',
                        'TrackFleet',
                        'Passed',
                        'Warehouse Plot 4, North Karachi',
                        'Haris Salman',
                        '08:00 - 17:00',
                        'Forklift',
                        'POD',
                        'Not Required',
                        '2026-06-01 13:05:00',
                        '2026-06-02 11:45:00'
                    ),
                    $this->child(
                        'TEST-CH-2026-009-B',
                        'LTL',
                        'YMLU2211432',
                        'DO-2026-009-B',
                        'Yellow',
                        'In-Transit',
                        'NorthStar Inland',
                        'Qasim Jafri',
                        'NSI-SPLIT-91B',
                        'Karachi Port Off-Dock Terminal',
                        'Safe Solutions South Warehouse, Karachi',
                        '2026-06-03 09:15:00',
                        '2026-06-04 14:00:00',
                        '2026-06-06 16:00:00',
                        '2026-06-08 00:00:00',
                        52000,
                        'Landhi Link Road',
                        'National Highway',
                        'KHI-9022',
                        'Mitsubishi Fuso',
                        '10 MT',
                        'Zubair Raza',
                        '42101-9988776-5',
                        '+92-333-5544332',
                        'Irfan Shah',
                        'GPS-NSI-9022',
                        'TrackFleet',
                        'Passed',
                        'Korangi Creek Industrial Park',
                        'Shahzaib Khan',
                        '10:00 - 18:00',
                        'Manual + Forklift',
                        'Signed POD',
                        'Required',
                        '2026-06-04 14:10:00',
                        null
                    ),
                ],
            ],
            [
                'booking_number' => 'TEST-BKG-2026-010',
                'booking_type' => 'export',
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => 'multi_modal',
                'origin_port' => 'Karachi Port',
                'destination_port' => 'Mersin, TR',
                'place_of_receipt' => 'Safe Solutions Export Warehouse, Karachi',
                'final_delivery_place' => 'Mersin Free Zone, Turkey',
                'exporter' => 'Safe Solutions Pvt Ltd',
                'importer' => 'Anatolia Retail Group',
                'notify_party' => 'Anatolia Retail Group',
                'agent' => 'BlueSea Container Services',
                'estimated_time_departure' => '2026-06-08 15:00:00',
                'estimated_time_delivery' => '2026-06-21 09:00:00',
                'service_level' => 'FCL Export Standard',
                'hs_code_id' => $this->resolveHsCodeId('6205.20'),
                'carrier_id' => $this->resolveCarrierId(['maersk', 'cma', 'msc'], $fallbackCarrierIds->get(9)),
                'gross_weight' => 9680,
                'volume_cbm' => 31.5,
                'declared_cargo_value' => 73400,
                'dangerous_good' => false,
                'remarks' => 'Future export with one future child leg to test upcoming path steps.',
                'incoterms' => 'FOB',
                'freight_terms' => 'Collect',
                'quoted_freight_rate' => 3125,
                'status' => 'Booked',
                'item' => [
                    'container_type' => '40HC',
                    'number_of_pallets' => 14,
                    'details_of_commodities' => 'Finished garment cartons for Turkey retail launch.',
                ],
                'documents' => [
                    $this->document('Commercial Invoice', 'Draft invoice received.', 'Parent Shipment', 'Received'),
                    $this->document('Packing List', 'Packing list pending final QC signoff.', 'Parent Shipment', 'Pending'),
                    $this->document('Truck Assignment Sheet', 'Child truck assignment created.', 'Child Shipment', 'Pending', 'TEST-CH-2026-010-A'),
                ],
                'status_history' => [
                    $this->history('booking_created', null, '2026-06-03 12:00:00', 'Booked', 'Future export booking created for customer launch stock.'),
                ],
                'children' => [
                    $this->child(
                        'TEST-CH-2026-010-A',
                        'FTL',
                        'TCLU5544332',
                        'DO-2026-010-A',
                        'Green',
                        'Booked',
                        'Anatolia Inland',
                        'Mehmet Yilmaz',
                        'ANI-TR-1180',
                        'Mersin Port',
                        'Anatolia Retail Group DC',
                        '2026-06-22 08:00:00',
                        '2026-06-22 14:00:00',
                        '2026-06-23 10:30:00',
                        '2026-06-25 00:00:00',
                        116000,
                        'Tarsus Junction',
                        'D400',
                        'TR-33-AR-512',
                        'Mercedes Actros',
                        '22 MT',
                        'Yusuf Demir',
                        'TR-DRV-22101',
                        '+90-532-1102233',
                        'Kerem Altin',
                        'GPS-ANI-512',
                        'RouteTrack',
                        'Scheduled',
                        'Mersin Free Zone Block C-11',
                        'Elif Kaya',
                        '09:00 - 17:00',
                        'Dock Leveler',
                        'ePOD',
                        'Required'
                    ),
                ],
            ],
        ];
    }

    private function resolveCarrierId(array $needles, ?int $fallbackId = null): int
    {
        foreach ($needles as $needle) {
            $carrierId = DB::table('logistics_v2_carriers')
                ->where(function ($query) use ($needle) {
                    $query->where('trading_brand_name', 'ilike', '%'.$needle.'%')
                        ->orWhere('carrier_company_name', 'ilike', '%'.$needle.'%');
                })
                ->orderBy('id')
                ->value('id');

            if ($carrierId) {
                return (int) $carrierId;
            }
        }

        return (int) ($fallbackId ?: DB::table('logistics_v2_carriers')->orderBy('id')->value('id'));
    }

    private function resolveHsCodeId(string $prefix): int
    {
        $hsCodeId = DB::table('hs_codes')
            ->where('hs_code', 'like', $prefix.'%')
            ->orderBy('id')
            ->value('id');

        if ($hsCodeId) {
            return (int) $hsCodeId;
        }

        return (int) DB::table('hs_codes')->orderBy('id')->value('id');
    }

    private function document(
        string $type,
        string $description,
        string $requiredFor,
        string $status,
        ?string $childBookingNumber = null
    ): array {
        return [
            'document_type' => $type,
            'description' => $description,
            'required_for' => $requiredFor,
            'status' => $status,
            'file_path' => null,
            'original_filename' => null,
            'child_booking_number' => $childBookingNumber,
        ];
    }

    private function history(
        string $eventType,
        ?string $originalDate,
        ?string $newDate,
        ?string $statusChangedTo,
        string $reason
    ): array {
        return [
            'event_type' => $eventType,
            'original_date' => $originalDate,
            'new_date' => $newDate,
            'status_changed_to' => $statusChangedTo,
            'reason' => $reason,
            'action_by' => 1,
        ];
    }

    private function child(
        string $bookingNumber,
        string $freightMode,
        string $containerNo,
        string $doNumber,
        string $customsChannel,
        string $status,
        string $carrier,
        string $carrierContact,
        string $carrierContract,
        string $pickupPoint,
        string $finalDestination,
        string $etd,
        string $eta,
        string $deliveryDate,
        string $returnDeadline,
        int $freightRatePkr,
        string $transitStop,
        string $routeOption,
        string $vehicleReg,
        string $vehicleModel,
        string $truckCapacity,
        string $driverName,
        string $driverCnic,
        string $driverMobile,
        string $coDriver,
        string $gpsUnitId,
        string $gpsPortal,
        string $vehicleInspection,
        string $deliveryAddress,
        string $receivingContact,
        string $deliveryWindow,
        string $unloadingMethod,
        string $deliveryReceiptType,
        string $postDeliveryInspection,
        ?string $arrivalConfirmedAt = null,
        ?string $deliveryConfirmedAt = null
    ): array {
        return [
            'booking_number' => $bookingNumber,
            'freight_mode' => $freightMode,
            'container_no' => $containerNo,
            'do_number' => $doNumber,
            'customs_channel' => $customsChannel,
            'status' => $status,
            'carrier' => $carrier,
            'carrier_contact' => $carrierContact,
            'carrier_contract' => $carrierContract,
            'pickup_point' => $pickupPoint,
            'final_destination' => $finalDestination,
            'etd' => $etd,
            'eta' => $eta,
            'delivery_date' => $deliveryDate,
            'return_deadline' => $returnDeadline,
            'freight_rate_pkr' => $freightRatePkr,
            'transit_stop' => $transitStop,
            'route_option' => $routeOption,
            'vehicle_reg' => $vehicleReg,
            'vehicle_model' => $vehicleModel,
            'truck_capacity' => $truckCapacity,
            'driver_name' => $driverName,
            'driver_cnic' => $driverCnic,
            'driver_mobile' => $driverMobile,
            'co_driver' => $coDriver,
            'gps_unit_id' => $gpsUnitId,
            'gps_portal' => $gpsPortal,
            'vehicle_inspection' => $vehicleInspection,
            'delivery_address' => $deliveryAddress,
            'receiving_contact' => $receivingContact,
            'delivery_window' => $deliveryWindow,
            'unloading_method' => $unloadingMethod,
            'delivery_receipt_type' => $deliveryReceiptType,
            'post_delivery_inspection' => $postDeliveryInspection,
            'arrival_confirmed_at' => $arrivalConfirmedAt,
            'delivery_confirmed_at' => $deliveryConfirmedAt,
        ];
    }
}
