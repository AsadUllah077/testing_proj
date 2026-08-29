<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'purchase_date'          => 'date',
        'in_service_date'        => 'date',
        'last_depreciation_date' => 'date',
        'disposal_date'          => 'date',
        'purchase_cost'          => 'decimal:2',
        'residual_value'         => 'decimal:2',
        'current_book_value'     => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'disposal_amount'        => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Business Logic ───────────────────────────────────────────────────────

    /**
     * Monthly straight-line depreciation amount.
     */
    public function monthlyDepreciation(): float
    {
        if ($this->useful_life_months <= 0) return 0;

        if ($this->depreciation_method === 'declining_balance') {
            // Annual rate = 1 / useful_life_years * 2 (double-declining)
            $annualRate = (2 / ($this->useful_life_months / 12));
            return round((float) $this->current_book_value * $annualRate / 12, 2);
        }

        // Straight-line
        return round(
            ((float) $this->purchase_cost - (float) $this->residual_value) / $this->useful_life_months,
            2
        );
    }

    public function isFullyDepreciated(): bool
    {
        return (float) $this->current_book_value <= (float) $this->residual_value;
    }

    /**
     * Months since in_service_date.
     */
    public function monthsInService(): int
    {
        return (int) $this->in_service_date->diffInMonths(Carbon::today());
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'             => 'emerald',
            'fully_depreciated'  => 'blue',
            'disposed'           => 'gray',
            default              => 'gray',
        };
    }

    public static function generateAssetNumber(): string
    {
        $prefix = 'FA-' . now()->format('Ym') . '-';
        $last   = static::where('asset_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('asset_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
