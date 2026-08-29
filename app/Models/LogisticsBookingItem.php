<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LogisticsBookingItem extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'pallets' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_id');
    }
}
