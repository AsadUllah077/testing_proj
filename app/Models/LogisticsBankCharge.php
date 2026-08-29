<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsBankCharge extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_bank_charges';

    protected $fillable = [
        'charge_number',
        'charge_type',
        'bank',
        'linked_lc',
        'lc_id',
        'charge_date',
        'amount_pkr',
        'amount_usd',
        'exchange_rate',
        'charge_currency',
        'payment_mode',
        'bank_reference',
        'remarks',
        'company_id',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'amount_pkr' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function lc()
    {
        return $this->belongsTo(LogisticsLetterOfCredit::class, 'lc_id');
    }

    public function payment()
    {
        return $this->hasOne(LogisticsLcPayment::class, 'bank_charge_id');
    }

    public function getBankNameAttribute(): string
    {
        return config('logistics.banks.'.$this->bank)
            ?? ucwords(str_replace('_', ' ', (string) $this->bank));
    }

    public function getChargeTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->charge_type));
    }

    public static function generateChargeNumber(): string
    {
        $last = static::withTrashed()->latest('id')->value('charge_number');
        if ($last && preg_match('/CHG-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }

        return 'CHG-'.$next;
    }
}
