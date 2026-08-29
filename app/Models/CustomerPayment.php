<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CustomerPaymentItem::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextReference(int $companyId): string
    {
        $count = static::withTrashed()->where('company_id', $companyId)->count() + 1;
        $reference = 'CP-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        while (static::withoutGlobalScope('company')->withTrashed()->where('reference_no', $reference)->exists()) {
            $count++;
            $reference = 'CP-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $reference;
    }
}
