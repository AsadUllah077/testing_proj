<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LogisticsBookingV2 extends Model
{
    use BelongsToCompany,LogsActivity;

    protected $table = 'logistics_bookings';

    protected $guarded = [];

    protected $casts = [
        'inland_details' => 'array',
    ];

    public function statusHistories()
    {
        return $this->hasMany(LogisticsBookingStatusHistory::class, 'booking_id')->latest();
    }

    // ── Status helpers ──────────────────────────────────────────
    public function resolveStatusFromPickupDate(): string
    {
        if (! $this->pickup_date) {
            return $this->status; // no change
        }

        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        if ($this->pickup_date->toDateString() === $today) {
            return 'Pickup';
        }

        if ($this->pickup_date->toDateString() >= $tomorrow) {
            return 'In-Transit';
        }

        return $this->status;
    }

    /**
     * Get specific data from freight_details JSON
     */
    public function getExtra($key, $default = null)
    {
        return data_get($this->freight_details, $key, $default);
    }

    /**
     * Container Lifecycle Helpers
     */
    public function getContainerInfo()
    {
        return [
            'id' => $this->getExtra('container.id'),
            'seal' => $this->getExtra('container.seal'),
            'type' => $this->container_type,
            'return_deadline' => $this->getExtra('container.return_deadline'),
            'returned_at' => $this->getExtra('container.returned_at'),
            'gate_out_port' => $this->getExtra('container.gate_out_port'),
            'gate_in_factory' => $this->getExtra('container.gate_in_factory'),
        ];
    }

    /**
     * Customs Workflow Helpers
     */
    public function getCustomsInfo()
    {
        return [
            'gd_number' => $this->getExtra('customs.gd_number'),
            'gd_date' => $this->getExtra('customs.gd_date'),
            'status' => $this->getExtra('customs.status', 'Pending'),
            'examination' => $this->getExtra('customs.examination', 'Pending'),
            'clearance_date' => $this->getExtra('customs.clearance_date'),
        ];
    }

    /**
     * Inland Child Workflow Helpers
     */
    public function getInlandInfo()
    {
        $details = $this->getExtra('inland_details', []);

        return [
            'gd_number' => $this->gd_number ?? $details['gd_number'] ?? null,
            'do_number' => $this->do_number ?? $details['do_number'] ?? null,
            'customs_channel' => $this->customs_channel ?? $details['customs_channel'] ?? null,
            'carrier_contact' => $this->carrier_contact ?? $details['carrier_contact'] ?? null,
            'carrier_contract' => $this->carrier_contract ?? $details['carrier_contract'] ?? null,
            'return_deadline' => ($this->return_deadline ? $this->return_deadline->toDateString() : null) ?? $details['return_deadline'] ?? null,
            'freight_rate_pkr' => $this->freight_rate_pkr ?? $details['freight_rate_pkr'] ?? null,
            'transit_stop' => $this->transit_stop ?? $details['transit_stop'] ?? null,
            'oversize_permit' => $this->oversize_permit ?? $details['oversize_permit'] ?? null,
            'vehicle_reg' => $this->vehicle_reg ?? $details['vehicle_reg'] ?? null,
            'vehicle_model' => $this->vehicle_model ?? $details['vehicle_model'] ?? null,
            'truck_capacity' => $this->truck_capacity ?? $details['truck_capacity'] ?? null,
            'driver_name' => $this->driver_name ?? $details['driver_name'] ?? null,
            'driver_cnic' => $this->driver_cnic ?? $details['driver_cnic'] ?? null,
            'driver_mobile' => $this->driver_mobile ?? $details['driver_mobile'] ?? null,
            'co_driver' => $this->co_driver ?? $details['co_driver'] ?? null,
            'gps_unit_id' => $this->gps_unit_id ?? $details['gps_unit_id'] ?? null,
            'gps_portal' => $this->gps_portal ?? $details['gps_portal'] ?? null,
            'vehicle_inspection' => $this->vehicle_inspection ?? $details['vehicle_inspection'] ?? null,
            'container_return_type' => $this->container_return_type ?? $details['container_return_type'] ?? null,
            'route_option' => $this->route_option ?? $details['route_option'] ?? null,
            'total_distance' => $this->total_distance ?? $details['total_distance'] ?? null,
            'est_transit_time' => $this->est_transit_time ?? $details['est_transit_time'] ?? null,
            'delivery_address' => $this->final_delivery_place ?? $this->delivery_address ?? $details['delivery_address'] ?? null,
            'receiving_contact' => $this->receiving_contact ?? $details['receiving_contact'] ?? null,
            'delivery_window' => $this->delivery_window ?? $details['delivery_window'] ?? null,
            'unloading_method' => $this->unloading_method ?? $details['unloading_method'] ?? null,
            'delivery_receipt_type' => $this->delivery_receipt_type ?? $details['delivery_receipt_type'] ?? null,
            'post_delivery_inspection' => $this->post_delivery_inspection ?? $details['post_delivery_inspection'] ?? null,
            'sub_mode' => $this->freight_mode ?? $details['child_sub_mode'] ?? null,
        ];
    }

    /**
     * Check if container return is overdue
     */
    public function isContainerOverdue()
    {
        $deadline = $this->getExtra('container.return_deadline');
        if (! $deadline || $this->getExtra('container.returned_at')) {
            return false;
        }

        return now()->gt(\Carbon\Carbon::parse($deadline));
    }

    public function stages()
    {
        return $this->hasMany(LogisticsBookingV2Stage::class, 'booking_v2_id')->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(LogisticsTradeDocument::class, 'booking_id');
    }

    public function lcPayments()
    {
        return $this->hasMany(LogisticsLcPayment::class, 'booking_id');
    }

    public function shipments()
    {
        return $this->hasMany(LogisticsShipment::class, 'booking_v2_id');
    }

    public function goodsDeclarations()
    {
        return $this->hasMany(LogisticsGoodsDeclaration::class, 'booking_v2_id');
    }

    /**
     * Generate next booking number: BK-XXXX
     */
    public static function generateBookingNumber(): string
    {
        $last = static::withTrashed()->latest('id')->value('booking_number');
        if ($last && preg_match('/BK-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }

        return 'BK-'.$next;
    }

    public function carrier()
    {
        return $this->belongsTo(LogisticsV2Carriers::class, 'carrier_id');
    }

    public function hsCode()
    {
        return $this->belongsTo(HsCode::class, 'hs_code_id');
    }

    public function bookingStatusHistory()
    {
        return $this->hasOne(LogisticsBookingStatusHistory::class, 'booking_id')->latest();
    }

    public function items()
    {
        return $this->hasMany(LogisticsBookingItem::class, 'booking_id');
    }
}
