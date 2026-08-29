<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsOceanRateCard extends Model
{
    use BelongsToCompany,HasFactory,LogsActivity;

    protected $table = 'logistics_ocean_rate_cards';

    protected $fillable = [
        'ref_code',
        'carrier_id',
        'rate_type',
        'nac_quote_reference',
        'source_document',
        'origin_port',
        'destination_port',
        'equipment',
        'transit_time_days',
        'ocean_freight',
        'baf',
        'caf',
        'eis_isps_other',
        'thc_origin',
        'thc_destination',
        'all_in_total',
        'free_days_demurrage',
        'demurrage_rate_after_free',
        'free_days_detention',
        'detention_rate_after_free',
        'valid_from',
        'valid_to',
        'agreed_volume_teu_month',
        'remarks_gri_pss_notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'ocean_freight' => 'decimal:2',
        'baf' => 'decimal:2',
        'caf' => 'decimal:2',
        'eis_isps_other' => 'decimal:2',
        'thc_origin' => 'decimal:2',
        'thc_destination' => 'decimal:2',
        'all_in_total' => 'decimal:2',
        'demurrage_rate_after_free' => 'decimal:2',
        'detention_rate_after_free' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    // ─── Constants ────────────────────────────────────────────────

    const CARRIERS = [
        'maersk' => 'Maersk',
        'msc' => 'MSC',
        'cma_cgm' => 'CMA CGM',
        'hapag' => 'Hapag-Lloyd',
        'evergreen' => 'Evergreen',
        'cosco' => 'COSCO',
        'one' => 'ONE (Ocean Network Express)',
        'yang_ming' => 'Yang Ming',
    ];

    const RATE_TYPES = [
        'nac' => 'NAC — Named Account Contract',
        'spot' => 'Spot Rate',
        'gri' => 'GRI Rate',
        'pss' => 'PSS Rate',
    ];

    const SOURCE_DOCUMENTS = [
        'booking_confirmation_email' => 'Booking Confirmation Email',
        'nac_agreement_pdf' => 'NAC Agreement PDF',
        'freight_forwarder_quote' => 'Freight Forwarder Quote',
        'carrier_tariff' => 'Carrier Tariff',
    ];

    public function carrier()
    {
        return $this->belongsTo(LogisticsV2Carriers::class, 'carrier_id');
    }

    const ORIGIN_PORTS = [
        'PKQCT' => 'PKQCT — Port Qasim, KHI',
        'PKKHI' => 'PKKHI — Karachi Port',
        'PKLHE' => 'PKLHE — Lahore (ICD)',
        'PKISB' => 'PKISB — Islamabad (ICD)',
    ];

    const DESTINATION_PORTS = [
        'GBLGP' => 'GBLGP — London Gateway',
        'GBFXT' => 'GBFXT — Felixstowe',
        'NLRTM' => 'NLRTM — Rotterdam',
        'DEHAM' => 'DEHAM — Hamburg',
        'USNYC' => 'USNYC — New York',
        'AEJEA' => 'AEJEA — Jebel Ali, Dubai',
        'CNSHA' => 'CNSHA — Shanghai',
        'SGSIN' => 'SGSIN — Singapore',
    ];

    const EQUIPMENT = [
        '20ft_standard' => '20ft Standard (TEU)',
        '40ft_standard' => '40ft Standard (FEU)',
        '40ft_hc' => '40ft High Cube (HC)',
        '45ft_hc' => '45ft High Cube',
        'reefer_20' => '20ft Reefer',
        'reefer_40' => '40ft Reefer',
        'lcl_cbm' => 'LCL / CBM',
    ];

    const STATUSES = [
        'live' => 'Live',
        'expired' => 'Expired',
        'draft' => 'Draft',
    ];

    // ─── Carrier dot colours for UI ──────────────────────────────

    const CARRIER_COLORS = [
        'maersk' => '#0091CD',
        'msc' => '#FF6B2B',
        'cma_cgm' => '#006D9C',
        'hapag' => '#E30613',
        'evergreen' => '#008751',
        'cosco' => '#005C9F',
        'one' => '#EB1C23',
        'yang_ming' => '#00538E',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getCarrierNameLabelAttribute(): string
    {
        return $this->carrier->carrier_company_name;
    }

    public function getRateTypeLabelAttribute(): string
    {
        return self::RATE_TYPES[$this->rate_type] ?? $this->rate_type;
    }

    public function getEquipmentLabelAttribute(): string
    {
        return self::EQUIPMENT[$this->equipment] ?? $this->equipment;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getCarrierColorAttribute(): string
    {
        return self::CARRIER_COLORS[$this->carrier->carrier_company_name] ?? '#6B7280';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_to->isPast();
    }

    // ─── Auto-generate ref code ───────────────────────────────────

    public static function generateRefCode(): string
    {
        $last = self::orderByDesc('id')->value('ref_code');
        if ($last && preg_match('/RC-(\d+)/', $last, $m)) {
            return 'RC-'.str_pad((int) $m[1] + 1, 4, '0', STR_PAD_LEFT);
        }

        return 'RC-0001';
    }
}
