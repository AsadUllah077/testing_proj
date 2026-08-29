<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapMaterial extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $table = 'scrap_materials';

    protected $guarded = [];

    protected $casts = [
        'weight'     => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // New relationships for scrap types and categories
    public function scrapCategory()
    {
        return $this->belongsTo(ScrapCategory::class, 'scrap_category_id');
    }

    public function scrapType()
    {
        return $this->belongsTo(ScrapType::class, 'scrap_type_id');
    }

    // -------------------------------------------------------
    // Accessors for backward compatibility (optional)
    // -------------------------------------------------------

    public function getMaterialCategoryAttribute()
    {
        return $this->scrapCategory?->name;
    }

    public function getScrapTypeNameAttribute()
    {
        return $this->scrapType?->name;
    }

    // -------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------

    public function isDisposed(): bool
    {
        return $this->status === 'disposed';
    }

    public function canDelete(): bool
    {
        return $this->status !== 'disposed';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'in_storage'        => 'In Storage',
            'pending_disposal'  => 'Pending Disposal',
            'pending_recycling' => 'Pending Recycling',
            'transferred'       => 'Transferred',
            'disposed'          => 'Disposed',
            default             => $this->status,
        };
    }
    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }
}
