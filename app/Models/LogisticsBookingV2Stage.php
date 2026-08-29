<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsBookingV2Stage extends Model
{
    protected $table = 'logistics_booking_v2_stages';

    protected $fillable = [
        'booking_v2_id',
        'name',
        'document_type',
        'description',
        'required_for',
        'status',
        'file_path',
        'original_filename',
        'sort_order',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_v2_id');
    }
}
