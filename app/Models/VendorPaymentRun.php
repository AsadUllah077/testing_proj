<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPaymentRun extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'payment_date'  => 'date',
        'total_amount'  => 'decimal:2',
        'confirmed_at'  => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(VendorPaymentRunItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public static function nextReference(int $companyId): string
    {
        $count = static::withTrashed()->where('company_id', $companyId)->count() + 1;
        $reference = 'VPR-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        while (static::withoutGlobalScope('company')->withTrashed()->where('run_reference', $reference)->exists()) {
            $count++;
            $reference = 'VPR-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $reference;
    }
}
