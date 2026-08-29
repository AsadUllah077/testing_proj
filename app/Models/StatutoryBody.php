<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StatutoryBody extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'country_code',
        'website',
        'contact_info',
        'default_grace_days',
        'default_warning_days',
        'penalty_rate_per_day',
        'max_penalty_percentage',
        'is_active',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'penalty_rate_per_day' => 'decimal:2',
        'max_penalty_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get pay items associated with this statutory body.
     */
    public function payItems()
    {
        return $this->hasMany(PayItem::class);
    }

    /**
     * Get compliance events for this statutory body.
     */
    public function complianceEvents()
    {
        return $this->hasMany(ComplianceEvent::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active statutory bodies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific country.
     */
    public function scopeForCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }
}
