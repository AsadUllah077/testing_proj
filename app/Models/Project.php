<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'client',
        'client_id',
        'manager_id',
        'start_date',
        'end_date',
        'priority',
        'budget',
        'description',
        'status',
        'progress',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'decimal:2',
        'progress'   => 'integer',
    ];

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        // pivot column is now employee_id
        return $this->belongsToMany(Employee::class, 'project_members', 'project_id', 'employee_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function clientRecord()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
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
            'planning'  => 'bg-blue-100 text-blue-700',
            'active'    => 'bg-green-100 text-green-700',
            'on-hold'   => 'bg-yellow-100 text-yellow-700',
            'completed' => 'bg-gray-100 text-gray-700',
            default     => 'bg-gray-100 text-gray-700',
        };
    }
}
