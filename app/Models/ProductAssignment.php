<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ProductAssignment extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'test_data' => 'array',
        'due_date' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function workflowStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    public function disposition()
    {
        return $this->belongsTo(Disposition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Accessors for backward compatibility
    public function getDispositionAttribute()
    {
        return $this->disposition()->first()?->name;
    }

    public function getWorkflowStageAttribute()
    {
        return $this->workflowStage()->first()?->name;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeForWorkflowStage($query, $stageId)
    {
        return $query->where('workflow_stage_id', $stageId);
    }

    public function scopeForDisposition($query, $dispositionId)
    {
        return $query->where('disposition_id', $dispositionId);
    }
    // Task result relationships
    public function visualInspectionResult()
    {
        return $this->hasOne(VisualInspectionResult::class, 'assignment_id');
    }

    public function functionalTestingResult()
    {
        return $this->hasOne(FunctionalTestingResult::class, 'assignment_id');
    }

    public function generalTaskResult()
    {
        return $this->hasOne(generalTaskResult::class, 'assignment_id');
    }

    // Methods
    public function complete()
    {
        $this->update(['completed_at' => now()]);
    }

    public function assign($technicianId, $assignerId, $data = [])
    {
        $this->update(array_merge([
            'technician_id' => $technicianId,
            'assigned_by' => $assignerId,
            'assigned_at' => now(),
        ], $data));
    }
}
