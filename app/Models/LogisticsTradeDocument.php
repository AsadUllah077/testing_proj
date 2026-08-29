<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsTradeDocument extends Model
{
    protected $table = 'logistics_trade_documents';

    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_id');
    }
}
