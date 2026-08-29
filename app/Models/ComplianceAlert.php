<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ComplianceAlert extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'compliance_event_id',
        'alert_type',
        'title',
        'message',
        'severity',
        'status',
        'acknowledged_at',
        'acknowledged_by',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the compliance event for this alert.
     */
    public function complianceEvent()
    {
        return $this->belongsTo(ComplianceEvent::class);
    }

    /**
     * Get the user who acknowledged this alert.
     */
    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope for active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for alerts by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for alerts by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Mark alert as acknowledged.
     */
    public function acknowledge($userId = null)
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId ?: auth('web')->id(),
        ]);
    }

    /**
     * Check if alert is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
