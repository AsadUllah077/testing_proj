<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsInlandRateCard extends Model
{
    use BelongsToCompany,HasFactory, LogsActivity;

    protected $table = 'logistics_inland_rate_cards';

    protected $fillable = [
        'ref_code',
        'carrier_id',
        'carrier_mou_party',
        'zone_route_description',
        'example_routes',
        'transit_time',
        'rate_up_to_500kg',
        'rate_501_1000kg',
        'rate_1001_5000kg',
        'rate_20ft_container',
        'rate_40ft_container',
        'mou_effective_date',
        'mou_expiry_date',
        'fuel_levy_clause',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rate_up_to_500kg' => 'decimal:2',
        'rate_501_1000kg' => 'decimal:2',
        'rate_1001_5000kg' => 'decimal:2',
        'rate_20ft_container' => 'decimal:2',
        'rate_40ft_container' => 'decimal:2',
        'mou_effective_date' => 'date',
        'mou_expiry_date' => 'date',
    ];

    // ─── Constants ────────────────────────────────────────────────

    const CARRIERS = [
        'nlc_pakistan' => 'NLC Pakistan',
        'tcs_logistics' => 'TCS Logistics',
        'leopard_courier' => 'Leopard Courier',
        'mp_logistics' => 'M&P Logistics',
        'lcs' => 'LCS',
        'fml_express' => 'FML Express',
        'tranzum' => 'Tranzum',
        'blueex' => 'BlueEx Courier',
    ];

    const FUEL_LEVY_CLAUSES = [
        'included_in_above_rates' => 'Included in above rates',
        'charged_separately' => 'Charged separately',
        'not_applicable' => 'Not applicable',
        'as_per_mou' => 'As per MOU terms',
    ];

    const STATUSES = [
        'active' => 'Active',
        'expired' => 'Expired',
        'draft' => 'Draft',
    ];

    const CARRIER_COLORS = [
        'nlc_pakistan' => '#0B8043',
        'tcs_logistics' => '#E53E3E',
        'leopard_courier' => '#DD6B20',
        'mp_logistics' => '#2B6CB0',
        'lcs' => '#6B46C1',
        'fml_express' => '#0078C8',
        'tranzum' => '#975A16',
        'blueex' => '#2C7A7B',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function carrier()
    {
        return $this->belongsTo(LogisticsV2Carriers::class, 'carrier_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getCarrierLabelAttribute(): string
    {
        return self::CARRIERS[$this->carrier_mou_party] ?? $this->carrier_mou_party;
    }

    public function getCarrierColorAttribute(): string
    {
        return self::CARRIER_COLORS[$this->carrier_mou_party] ?? '#6B7280';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getFuelLevyLabelAttribute(): string
    {
        return self::FUEL_LEVY_CLAUSES[$this->fuel_levy_clause] ?? ($this->fuel_levy_clause ?? '—');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->mou_expiry_date && $this->mou_expiry_date->isPast();
    }

    // ─── Auto ref code ────────────────────────────────────────────

    public static function generateRefCode(string $carrier): string
    {
        $prefix = 'IRC-'.strtoupper(substr(str_replace('_', '', $carrier), 0, 3));
        $last = self::where('ref_code', 'like', $prefix.'%')->orderByDesc('id')->value('ref_code');

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            return $prefix.'-'.str_pad((int) $m[1] + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix.'-0001';
    }
}
