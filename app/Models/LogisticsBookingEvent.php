<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsBookingEvent extends Model
{
    protected $fillable = [
        'logistics_booking_id',
        'event_type',
        'scheduled_date',
        'actual_date',
        'new_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date'    => 'date',
        'new_date'       => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBooking::class, 'logistics_booking_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
