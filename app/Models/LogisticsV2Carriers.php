<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsV2Carriers extends Model
{
    use BelongsToCompany,HasFactory,LogsActivity;

    protected $table = 'logistics_v2_carriers';

    protected $fillable = [
        // Company Information
        'carrier_company_name',
        'trading_brand_name',
        'transport_modes',
        'country_of_incorporation',
        'pakistan_liaison_office',
        'ntn_scac_code',
        'contact_person',
        'contact_email',
        'phone_pakistan_office',
        'website',

        // Service Coverage
        'key_origin_ports_airports',
        'key_destination_markets',
        'services_offered',
        'tracking_portal_api',

        // Compliance & Documents
        'fiata_iata_membership',
        'insurance_marine_liability',
        'aeo_ctpat_iso_certification',
        'payment_terms',
        'credit_limit_usd',
        'account_manager_internal',
        'notes_onboarding_remarks',
        'liability_insurance_path',

        // Status
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'credit_limit_usd' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // ─── Constants ───────────────────────────────────────────────

    const TRANSPORT_MODES = [
        'ocean_liner' => 'Ocean Liner',
        'air_freight' => 'Air Freight',
        'inland_trucking' => 'Inland (Trucking)',
        'rail_freight' => 'Rail Freight',
        'multimodal' => 'Multimodal',
    ];

    const SERVICES_OFFERED = [
        'fcl_sea' => 'FCL Sea',
        'lcl_sea' => 'LCL Sea',
        'air_express' => 'Air Express',
        'air_standard' => 'Air Standard',
        'road_ftl' => 'Road FTL',
        'road_ltl' => 'Road LTL',
        'rail' => 'Rail',
    ];

    const TRACKING_PORTAL_OPTIONS = [
        'yes_web_portal' => 'Yes — Web Portal',
        'yes_api' => 'Yes — API',
        'yes_both' => 'Yes — Both',
        'no' => 'No',
    ];

    const FIATA_IATA_OPTIONS = [
        'fiata_member' => 'FIATA Member',
        'iata_member' => 'IATA Member',
        'both' => 'Both',
        'none' => 'None',
    ];

    const INSURANCE_OPTIONS = [
        'yes_coi' => 'Yes — Provided COI',
        'yes_no_coi' => 'Yes — No COI Yet',
        'no' => 'No',
    ];

    const PAYMENT_TERMS = [
        'net_30' => 'Net 30',
        'net_45' => 'Net 45',
        'net_60' => 'Net 60',
        'advance' => 'Advance',
        'cod' => 'COD',
    ];

    const STATUSES = [
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getTransportModeLabelAttribute(): string
    {
        return self::TRANSPORT_MODES[$this->transport_modes] ?? $this->transport_modes;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getLiabilityInsuranceUrlAttribute(): ?string
    {
        return $this->liability_insurance_path
            ? asset('storage/'.$this->liability_insurance_path)
            : null;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public static function forSelect(): array
    {
        return static::where('status', 'approved')
            ->orderBy('carrier_company_name')
            ->pluck('carrier_company_name', 'id')
            ->toArray();
    }
}
