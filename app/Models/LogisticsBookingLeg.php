<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsBookingLeg extends Model
{
    protected $table = 'logistics_booking_legs';

    protected $fillable = [
        'booking_v2_id',
        'origin',
        'destination',
        'etd',
        'eta',
        'estimated_delivery_date',
        'original_etd',
        'original_eta',
        'timing_status',
        'timing_reason',
        'mode',
        'carrier',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'etd' => 'date',
        'eta' => 'date',
        'estimated_delivery_date' => 'date',
        'original_etd' => 'date',
        'original_eta' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_v2_id');
    }
}
