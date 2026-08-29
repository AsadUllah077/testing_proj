<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'severity',
        'category',
        'status',
        'assigned_to',
        'reported_by',
        'company_id',
        'due_date',
        'region',
        'department_id',
        'attachment',
        'assigned_at',
        'started_at',
        'completed_at',
        'feedback',
    ];

    protected $casts = [
        'due_date'     => 'datetime',
        'assigned_at'  => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low'      => 'bg-green-100 text-green-700',
            'medium'   => 'bg-yellow-100 text-yellow-700',
            'high'     => 'bg-orange-100 text-orange-700',
            'critical' => 'bg-red-100 text-red-700',
            default    => 'bg-gray-100 text-gray-700',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open'        => 'bg-blue-100 text-blue-700',
            'assigned'    => 'bg-indigo-100 text-indigo-700',
            'in-progress' => 'bg-yellow-100 text-yellow-700',
            'review'      => 'bg-purple-100 text-purple-700',
            'resolved'    => 'bg-green-100 text-green-700',
            'completed'   => 'bg-emerald-100 text-emerald-700',
            'closed'      => 'bg-gray-100 text-gray-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
