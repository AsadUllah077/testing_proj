<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Searchable;

class Product extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Searchable;

    protected $guarded = [];

    protected $searchable = ['title', 'unique_id', 'dpn_sn_number', 'product_id_value'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->title,
            'category' => 'Product',
            'url' => route('warehouse.products.edit', $this->id),
            'description' => $this->title,
        ];
    }

    protected $casts = [
        'specifications' => 'array',
        'status' => 'string',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function assignments()
    // {
    //     return $this->hasMany(ProductAssignment::class)->latest('assigned_at');
    // }
    public function parts()
    {
        return $this->belongsToMany(Part::class, 'product_part')
            ->withPivot('quantity', 'notes', 'added_by')
            ->withTimestamps();
    }

    public function currentAssignment()
    {
        return $this->hasOne(ProductAssignment::class)
            ->whereNull('completed_at')
            ->latest('assigned_at');
    }

    public function assignmentHistory()
    {
        return $this->hasMany(ProductAssignment::class)
            ->orderBy('assigned_at', 'desc');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function disposition()
    {
        return $this->belongsTo(Disposition::class, 'disposition_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function workflowStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'work_flow_stage_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function visualInspections()
    {
        return $this->hasMany(VisualInspection::class);
    }

    public function functionalTestings()
    {
        return $this->hasMany(FunctionalTesting::class);
    }

    // public function parts()
    // {
    //     return $this->hasMany(ProductPart::class);
    // }

    public function assignments()
    {
        return $this->hasMany(ProductAssignment::class);
    }

    /**
     * Check if product has a specific part
     */
    public function hasPart($partId): bool
    {
        return $this->parts()->where('part_id', $partId)->exists();
    }

    /**
     * Get part quantity for a specific part
     */
    public function getPartQuantity($partId): int
    {
        $part = $this->parts()->where('part_id', $partId)->first();

        return $part ? $part->pivot->quantity : 0;
    }

    /**
     * Update part quantity
     */
    public function updatePartQuantity($partId, $quantity, $notes = null)
    {
        if ($quantity <= 0) {
            return $this->parts()->detach($partId);
        }

        return $this->parts()->updateExistingPivot($partId, [
            'quantity' => $quantity,
            'notes' => $notes,
            'updated_at' => now(),
        ]);
    }

    // public function specifications()
    // {
    //     return $this->hasMany(ProductSpecification::class);
    // }

    // Accessors
    // public function getLatestVisualInspectionAttribute()
    // {
    //     return $this->visualInspections()->latest()->first();
    // }

    // public function getLatestFunctionalTestingAttribute()
    // {
    //     return $this->functionalTestings()->latest()->first();
    // }

    public function getCurrentAssigneeAttribute()
    {
        return $this->assignments()->latest()->first()?->technician ?? $this->assignee;
    }

    // Convenience accessors for backward compatibility
    // public function getDispositionAttribute()
    // {
    //     return $this->disposition()->first()?->name;
    // }

    // public function getWorkFlowStageAttribute()
    // {
    //     return $this->workflowStage()->first()?->name;
    // }

    // Scopes
    public function scopeWarehouseIssued($query, bool $availableOnly = true)
    {
        $query->where(function ($q) {
            $q->whereRaw("LOWER(status) = 'issued'")
                ->orWhere(function ($manualAssigned) {
                    $manualAssigned->where('manual_status', 1)
                        ->whereNotNull('assigned_to')
                        ->where(function ($status) {
                            $status->whereNull('status')
                                ->orWhereRaw("LOWER(status) != 'hold'");
                        });
                });
        });

        if ($availableOnly) {
            $query->where('sold_status', 0)
                ->where('scrap_status', 0);
        }

        return $query;
    }

    public function scopeWarehouseNotIssued($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($status) {
                $status->whereNull('status')
                    ->orWhereRaw("LOWER(status) != 'issued'");
            })->where(function ($manualAssigned) {
                $manualAssigned->whereNull('manual_status')
                    ->orWhere('manual_status', '!=', 1)
                    ->orWhereNull('assigned_to')
                    ->orWhereRaw("LOWER(status) = 'hold'");
            });
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['category'] ?? null, function ($query, $category) {
            $query->where('category', $category);
        })
            ->when($filters['location'] ?? null, function ($query, $location) {
                $query->where('location', $location);
            })
            ->when($filters['disposition'] ?? null, function ($query, $dispositionName) {
                $query->whereHas('disposition', function ($q) use ($dispositionName) {
                    $q->where('name', $dispositionName);
                });
            })
            ->when($filters['work_flow_stage'] ?? null, function ($query, $stageName) {
                $query->whereHas('workflowStage', function ($q) use ($stageName) {
                    $q->where('name', $stageName);
                });
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('unique_id', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('dpn_sn_number', 'like', "%{$search}%")
                        ->orWhere('product_id_value', 'like', "%{$search}%");
                });
            })
            ->when($filters['assigned_to'] ?? null, function ($query, $assigned) {
                if ($assigned === 'Assigned') {
                    $query->whereNotNull('assigned_to');
                } elseif ($assigned === 'Unassigned') {
                    $query->whereNull('assigned_to');
                }
            })
            ->when($filters['company_id'] ?? null, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            });
    }

    // Business Logic Methods
    public function changeDisposition($dispositionId, $userId)
    {
        $this->update([
            'disposition_id' => $dispositionId,
            'last_action' => 'Disposition changed',
        ]);

        // Log the change if needed
        ProductHistory::create([
            'product_id' => $this->id,
            'action' => 'disposition_change',
            'old_value' => $this->getOriginal('disposition_id'),
            'new_value' => $dispositionId,
            'changed_by' => $userId,
        ]);

        return $this;
    }

    // Add this relationship for gate pass items
    public function gatePassItems()
    {
        return $this->hasMany(GatePassItem::class, 'product_id');
    }

    public function activeGatePassItems()
    {
        return $this->hasMany(GatePassItem::class, 'product_id')
            ->whereHas('gatePass', function ($q) {
                $q->whereNotIn('status', ['QC_AND_SECURITY_APPROVED', 'REJECTED']);
            });
    }

    public function changeWorkflowStage($workflowStageId, $userId)
    {
        $this->update([
            'work_flow_stage_id' => $workflowStageId,
            'last_action' => 'Workflow stage changed',
        ]);

        // Log the change
        ProductHistory::create([
            'product_id' => $this->id,
            'action' => 'workflow_stage_change',
            'old_value' => $this->getOriginal('work_flow_stage_id'),
            'new_value' => $workflowStageId,
            'changed_by' => $userId,
        ]);

        return $this;
    }

    public function histories()
    {
        return $this->hasMany(ProductHistory::class);
    }
}
