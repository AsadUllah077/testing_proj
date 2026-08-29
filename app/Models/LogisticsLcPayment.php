<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsLcPayment extends Model
{
    protected $table = 'logistics_lc_payments';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'currency_value' => 'decimal:6',
    ];

    public function amountInPkr(): ?float
    {
        if ($this->amount === null) {
            return null;
        }

        if (strtoupper((string) ($this->currency ?: 'PKR')) === 'PKR') {
            return (float) $this->amount;
        }

        return $this->hasCurrencyValue()
            ? (float) $this->amount * (float) $this->currency_value
            : null;
    }

    public function amountInUsd(): ?float
    {
        if ($this->amount === null) {
            return null;
        }

        if (strtoupper((string) ($this->currency ?: 'PKR')) === 'USD') {
            return (float) $this->amount;
        }

        return $this->hasCurrencyValue()
            ? (float) $this->amount / (float) $this->currency_value
            : null;
    }

    public function hasCurrencyValue(): bool
    {
        return is_numeric($this->currency_value) && (float) $this->currency_value > 0;
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        if ($this->entry_source === 'bank_charge') {
            return 'Bank Charge';
        }

        return $this->payment_type === 'freight_payment'
            ? 'Freight Payment'
            : 'Product Payment';
    }

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_id');
    }

    public function bankCharge()
    {
        return $this->belongsTo(LogisticsBankCharge::class, 'bank_charge_id');
    }
}
