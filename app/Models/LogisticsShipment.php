<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsShipment extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_shipments';

    protected $fillable = [
        'shipment_number',
        'booking_v2_id',
        'shipment_type',
        'freight_mode',
        'carrier',
        'vessel_flight',
        'voyage_number',
        'origin_port',
        'dest_port',
        'etd',
        'eta',
        'atd',
        'ata',
        'container_number',
        'seal_number',
        'hs_code',
        'cargo_description',
        'shipper',
        'consignee',
        'cargo_value',
        'currency',
        'delay_days',
        'delay_reason',
        'status',
        'company_id',
    ];

    protected $casts = [
        'etd' => 'date',
        'eta' => 'date',
        'atd' => 'date',
        'ata' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_v2_id');
    }

    public static function generateShipmentNumber(): string
    {
        $last = static::withTrashed()->latest('id')->value('shipment_number');
        if ($last && preg_match('/SH-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }
        return 'SH-' . $next;
    }
}
