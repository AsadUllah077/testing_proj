<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsAirRateCard extends Model
{
    use BelongsToCompany,HasFactory, LogsActivity;

    protected $table = 'logistics_air_rate_cards';

    protected $fillable = [
        'ref_code',
        'carrier_id',
        'carrier',
        'origin_port',
        'destination_port',
        'via_hub',
        'weight_breakpoint',
        'charge_rate_usd_kg',
        'fuel_surcharge_fsc',
        'security_xray',
        'origin_handling',
        'all_in_per_kg',
        'minimum_charge_usd',
        'source_document',
        'notes',
        'rate_valid_from',
        'rate_valid_to',
        'status',
        'created_by',
    ];

    protected $casts = [
        'charge_rate_usd_kg' => 'decimal:4',
        'fuel_surcharge_fsc' => 'decimal:4',
        'security_xray' => 'decimal:4',
        'origin_handling' => 'decimal:4',
        'all_in_per_kg' => 'decimal:4',
        'minimum_charge_usd' => 'decimal:2',
        'rate_valid_from' => 'date',
        'rate_valid_to' => 'date',
    ];

    // ─── Constants ────────────────────────────────────────────────

    const CARRIERS = [
        'turkish_airlines_cargo' => 'Turkish Airlines Cargo',
        'emirates_skycargo' => 'Emirates SkyCargo',
        'qatar_airways_cargo' => 'Qatar Airways Cargo',
        'pia_cargo' => 'PIA Cargo',
        'lufthansa_cargo' => 'Lufthansa Cargo',
        'etihad_cargo' => 'Etihad Cargo',
        'air_arabia_cargo' => 'Air Arabia Cargo',
        'fedex' => 'FedEx',
        'dhl_express' => 'DHL Express',
    ];

    const ORIGIN_PORTS = [
        'KHI' => 'KHI — Karachi Jinnah Int\'l',
        'LHE' => 'LHE — Lahore Allama Iqbal',
        'ISB' => 'ISB — Islamabad Int\'l',
        'PEW' => 'PEW — Peshawar Bacha Khan',
        'SKT' => 'SKT — Sialkot Int\'l',
    ];

    const DESTINATION_PORTS = [
        'IST' => 'IST — Istanbul Atatürk',
        'DXB' => 'DXB — Dubai Int\'l',
        'DOH' => 'DOH — Doha Hamad',
        'LHR' => 'LHR — London Heathrow',
        'ORD' => 'ORD — Chicago O\'Hare',
        'FRA' => 'FRA — Frankfurt',
        'AMS' => 'AMS — Amsterdam Schiphol',
        'JFK' => 'JFK — New York JFK',
        'PVG' => 'PVG — Shanghai Pudong',
        'SIN' => 'SIN — Singapore Changi',
    ];

    const WEIGHT_BREAKPOINTS = [
        'min_charge' => 'Min Charge (M)',
        'normal' => 'Normal (N)',
        '+45kg' => '+45 kg',
        '+100kg' => '+100 kg',
        '+300kg' => '+300 kg',
        '+500kg' => '+500 kg',
        '+1000kg' => '+1000 kg',
    ];

    const SOURCE_DOCUMENTS = [
        'carrier_tariff_sheet' => 'Carrier Tariff Sheet',
        'freight_forwarder_confirmation' => 'Freight Forwarder Confirmation',
        'email_quote' => 'Email Quote',
        'contract_rate' => 'Contract Rate',
    ];

    const STATUSES = [
        'live' => 'Live',
        'expired' => 'Expired',
        'draft' => 'Draft',
    ];

    const CARRIER_COLORS = [
        'turkish_airlines_cargo' => '#C60C30',
        'emirates_skycargo' => '#007DC6',
        'qatar_airways_cargo' => '#5C0632',
        'pia_cargo' => '#4B286D',
        'lufthansa_cargo' => '#05164D',
        'etihad_cargo' => '#BD8B13',
        'air_arabia_cargo' => '#E31837',
        'fedex' => '#4D148C',
        'dhl_express' => '#FFCC00',
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

    public function carrierInfo()
    {
        return $this->belongsTo(LogisticsV2Carriers::class, 'carrier_id');
    }

    // ─── Accessors ────────────────────────────────────────────────

    // public function getCarrierLabelAttribute(): string
    // {
    //     return self::CARRIERS[$this->carrier->carrier_company_name] ?? $this->carrier->carrier_company_name ;
    // }

    public function getCarrierColorAttribute(): string
    {
        return self::CARRIER_COLORS[$this->carrier] ?? '#6B7280';
    }

    public function getWeightBreakpointLabelAttribute(): string
    {
        return self::WEIGHT_BREAKPOINTS[$this->weight_breakpoint] ?? $this->weight_breakpoint;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRouteLabelAttribute(): string
    {
        return $this->origin_port.' → '.$this->destination_port
            .($this->via_hub ? ' via '.$this->via_hub : '');
    }

    // ─── Auto ref code ────────────────────────────────────────────

    public static function generateRefCode(string $carrier): string
    {
        $prefix = 'ARC-'.strtoupper(substr($carrier, 0, 2));
        $last = self::where('ref_code', 'like', $prefix.'%')->orderByDesc('id')->value('ref_code');

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            return $prefix.'-'.str_pad((int) $m[1] + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix.'-0001';
    }
}
