<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use SoftDeletes;

    protected $table = 'project_tasks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'assignee_id',
        'due_date',
        'priority',
        'status',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
            'todo'        => 'bg-gray-100 text-gray-700',
            'in-progress' => 'bg-blue-100 text-blue-700',
            'review'      => 'bg-yellow-100 text-yellow-700',
            'completed'   => 'bg-green-100 text-green-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
