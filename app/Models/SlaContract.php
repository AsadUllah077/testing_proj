<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaContract extends Model
{
    use BelongsToCompany, HasFactory,LogsActivity;

    protected $table = 'sla_contracts';

    protected $fillable = [
        'contract_id',
        'carrier_id',
        'transport_mode',
        'contract_term',
        'min_otd_guarantee',
        'max_claim_rate',
        'volume_commitment',
        'contract_start_date',
        'contract_expiry_date',
        'rate_lock_period',
        'penalty_clause',
        'penalty_rate_amount',
        'review_frequency',
        'special_conditions',
        'status',
        'performance_status',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_expiry_date' => 'date',
        'min_otd_guarantee' => 'decimal:2',
        'max_claim_rate' => 'decimal:2',
    ];

    // ──────────────────────────────────────────────
    //  Auto-generate contract_id
    // ──────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (SlaContract $contract) {
            if (empty($contract->contract_id)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $contract->contract_id = 'SLA-'.$year.'-'.str_pad($count, 2, '0', STR_PAD_LEFT);
            }
        });
    }

    // ──────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(LogisticsV2Carriers::class, 'carrier_id');
    }

    // ──────────────────────────────────────────────
    //  Scopes
    // ──────────────────────────────────────────────
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('contract_id', 'like', "%{$term}%")
                ->orWhere('transport_mode', 'like', "%{$term}%")
                ->orWhereHas('carrier', fn ($cq) => $cq->where('name', 'like', "%{$term}%"));
        });
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->contract_expiry_date && $this->contract_expiry_date->isPast();
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return $this->contract_expiry_date
            && $this->contract_expiry_date->isFuture()
            && now()->diffInDays($this->contract_expiry_date) <= $withinDays;
    }

    // ──────────────────────────────────────────────
    //  Static option maps  (used in views/controller)
    // ──────────────────────────────────────────────
    public static function transportModes(): array
    {
        return [
            'ocean_fcl' => 'Ocean FCL',
            'ocean_lcl' => 'Ocean LCL',
            'air_freight' => 'Air Freight',
            'inland_trucking' => 'Inland Trucking',
            'rail_freight' => 'Rail Freight',
            'multimodal' => 'Multimodal',
        ];
    }

    public static function contractTerms(): array
    {
        return [
            'annual_12' => 'Annual (12 months)',
            'biannual_6' => 'Bi-Annual (6 months)',
            'quarterly_3' => 'Quarterly (3 months)',
            'monthly' => 'Monthly',
            'spot' => 'Spot / One-time',
        ];
    }

    public static function rateLockPeriods(): array
    {
        return [
            'no_lock' => 'No rate lock',
            '30_days' => '30 days',
            '60_days' => '60 days',
            '90_days' => '90 days',
            'full_term' => 'Full contract term',
        ];
    }

    public static function penaltyClauses(): array
    {
        return [
            'rebate_per_breach' => '% Rebate per breach',
            'fixed_amount' => 'Fixed amount per breach',
            'credit_note' => 'Credit note issued',
            'none' => 'None',
        ];
    }

    public static function reviewFrequencies(): array
    {
        return [
            'monthly' => 'Monthly review',
            'quarterly' => 'Quarterly review',
            'biannual' => 'Bi-annual review',
            'annual' => 'Annual review',
            'on_breach' => 'On breach only',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'breach' => 'Breach',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
        ];
    }

    public static function performanceStatuses(): array
    {
        return [
            'met' => 'Met',
            'marginal' => 'Marginal',
            'breach' => 'Breach',
            'pending' => 'Pending',
        ];
    }

    // ──────────────────────────────────────────────
    //  Convenience label getters
    // ──────────────────────────────────────────────
    public function getTransportModeLabelAttribute(): string
    {
        return static::transportModes()[$this->transport_mode] ?? ucfirst($this->transport_mode);
    }

    public function getContractTermLabelAttribute(): string
    {
        return static::contractTerms()[$this->contract_term] ?? ucfirst($this->contract_term);
    }

    public function getRateLockLabelAttribute(): string
    {
        return static::rateLockPeriods()[$this->rate_lock_period] ?? ucfirst($this->rate_lock_period);
    }

    public function getPenaltyClauseLabelAttribute(): string
    {
        return static::penaltyClauses()[$this->penalty_clause] ?? ucfirst($this->penalty_clause);
    }

    public function getReviewFrequencyLabelAttribute(): string
    {
        return static::reviewFrequencies()[$this->review_frequency] ?? ucfirst($this->review_frequency);
    }
}
