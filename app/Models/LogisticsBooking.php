<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsBooking extends Model
{
    protected $fillable = [
        'service_type',
        'origin_country',
        'origin_postal',
        'origin_type',
        'dest_country',
        'dest_postal',
        'dest_type',
        'loading_date',
        'eta',
        'services',
        'freight_class',
        'carrier_id',
        'pickup_time',
        'pickup_contact',
        'pickup_address',
        'delivery_location_name',
        'delivery_contact',
        'delivery_email',
        'delivery_phone',
        'delivery_address',
        'total_value',
        'is_draft',
        'status',
        'company_id',
        'origin_company',
        'origin_street',
        'origin_street2',
        'origin_city',
        'origin_state',
        'dest_company',
        'dest_street',
        'dest_street2',
        'dest_city',
        'dest_state',
        'reference_billing',
        'pickup_first_name',
        'pickup_last_name',
        'pickup_email',
        'pickup_phone',
        'delivery_first_name',
        'delivery_last_name',
        'carrier_rate',
        'pick_date',
        'second_party',
        'third_party',
    ];

    protected $casts = [
        'loading_date' => 'date',
        'pick_date'    => 'date',
        'eta'          => 'date',
        'is_draft'     => 'boolean'
    ];

    public function items()
    {
        return $this->hasMany(LogisticsBookingItem::class, 'logistics_booking_id');
    }

    public function carrier()
    {
        return $this->belongsTo(LogisticsCarrier::class, 'carrier_id');
    }

    public function events()
    {
        return $this->hasMany(LogisticsBookingEvent::class, 'logistics_booking_id')->latest();
    }
}
