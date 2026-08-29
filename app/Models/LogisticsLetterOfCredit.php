<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsLetterOfCredit extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_letters_of_credit';

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'latest_shipment_date' => 'date',
        'amount' => 'decimal:2',
        'tolerance_pct' => 'decimal:2',
    ];

    public function bankCharges()
    {
        return $this->hasMany(LogisticsBankCharge::class, 'lc_id');
    }

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_id');
    }

    public static function generateLcNumber(): string
    {
        $last = static::withTrashed()->where('record_type', 'lc')->latest('id')->value('lc_number');
        if ($last && preg_match('/LC-(\d{4})-(\d+)/', $last, $m)) {
            $next = (int) $m[2] + 1;
            $year = $m[1];
        } else {
            $next = 1;
            $year = now()->year;
        }

        return 'LC-'.$year.'-'.str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public static function generateBcNumber(): string
    {
        $last = static::withTrashed()->where('record_type', 'bank_contract')->latest('id')->value('lc_number');
        if ($last && preg_match('/BC-(\d{4})-(\d+)/', $last, $m)) {
            $next = (int) $m[2] + 1;
            $year = $m[1];
        } else {
            $next = 1;
            $year = now()->year;
        }

        return 'BC-'.$year.'-'.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
