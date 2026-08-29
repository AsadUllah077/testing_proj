<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStage extends Model
{
    use  SoftDeletes, LogsActivity, BelongsToCompany;

    protected $table = 'workflow_stages';

    protected $fillable = [
        'name',
        'company_id',
    ];

    // Relationship with company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scope for searching
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%'.$search.'%');
    }

    // Scope for filtering by company
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
