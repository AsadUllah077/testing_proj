<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisualInspection extends Model
{
    use  BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'checklist_items' => 'array',
        'custom_checklist' => 'array',
        'inspected_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inspector()
    {
        return $this->belongsTo(Employee::class, 'inspected_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Methods for checklist management
    public function addChecklistItem($item)
    {
        $checklist = $this->checklist_items ?? [];
        $checklist[] = $item;
        $this->checklist_items = $checklist;
        return $this;
    }

    public function addCustomChecklistItem($item)
    {
        $customChecklist = $this->custom_checklist ?? [];
        $customChecklist[] = $item;
        $this->custom_checklist = $customChecklist;
        return $this;
    }
}
