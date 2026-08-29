<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ComplianceEvent extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'statutory_body_id',
        'event_type',
        'title',
        'description',
        'due_date',
        'period_start',
        'period_end',
        'amount_due',
        'status',
        'completed_at',
        'penalty_amount',
        'metadata',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount_due' => 'decimal:2',
        'completed_at' => 'date',
        'penalty_amount' => 'decimal:2',
        'metadata' => 'array',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the statutory body for this event.
     */
    public function statutoryBody()
    {
        return $this->belongsTo(StatutoryBody::class);
    }

    /**
     * Scope for pending events.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for overdue events.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for due soon events.
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '>=', now()->toDateString())
                    ->where('due_date', '<=', now()->addDays($days)->toDateString());
    }

    /**
     * Calculate penalty for overdue event.
     */
    public function calculatePenalty()
    {
        if ($this->status !== 'pending' || !$this->isOverdue()) {
            return 0;
        }

        $body = $this->statutoryBody;
        if (!$body || !$body->penalty_rate_per_day || !$this->amount_due) {
            return 0;
        }

        $daysOverdue = abs(now()->diffInDays($this->due_date));
        $penalty = ($this->amount_due * $body->penalty_rate_per_day / 100) * $daysOverdue;

        // Apply max penalty limit if set
        if ($body->max_penalty_percentage) {
            $maxPenalty = $this->amount_due * $body->max_penalty_percentage / 100;
            $penalty = min($penalty, $maxPenalty);
        }

        return round(abs($penalty), 2);
    }

    /**
     * Check if event is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date < now()->toDateString();
    }

    /**
     * Check if event is due soon.
     */
    public function isDueSoon($days = 7)
    {
        return $this->status === 'pending' && 
               $this->due_date >= now()->toDateString() && 
               $this->due_date <= now()->addDays($days)->toDateString();
    }

    /**
     * Mark event as completed.
     */
    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()->toDateString(),
        ]);
    }
}
