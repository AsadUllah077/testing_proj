<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLocation extends Model
{
     use  SoftDeletes, BelongsToCompany, LogsActivity;

    protected $table = 'warehouse_locations';

    protected $guarded = [];

    // Relationship with company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scope for searching
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    // Scope for filtering by company
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'warehouse_location_id');
    }
}
