<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LogisticsBookingStatusHistory extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $table = "logistics_booking_status_history";
    protected $guarded = [];

    protected $casts = [
        'original_date' => 'date',
        'new_date' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_id');
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'pickup_approved' => 'Pickup Approved',
            'pickup_rejected' => 'Pickup Rescheduled',
            'delivery_approved' => 'Delivery Confirmed',
            'delivery_rejected' => 'Delivery Rescheduled',
            default => ucfirst(str_replace('_', ' ', $this->event_type)),
        };
    }
}
