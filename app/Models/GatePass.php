<?php
// app/Models/GatePass.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePass extends Model
{
    use  BelongsToCompany,LogsActivity;

    protected $guarded=[];

    protected $casts = [
        'departure_date' => 'datetime',
        'qc_approved_at' => 'datetime',
        'security_approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(GatePassItem::class);
    }
    /**
     * Get the warehouse location for this gate pass.
     */
    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getItemsCountAttribute()
    {
        return $this->items()->count();
    }

    public function scopePendingQC($query)
    {
        return $query->where('status', 'QC_APPROVAL_PENDING');
    }

    public function scopePendingSecurity($query)
    {
        return $query->where('status', 'SECURITY_APPROVAL_PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'QC_AND_SECURITY_APPROVED');
    }

    public function canApproveQC()
    {
        return $this->status === 'QC_APPROVAL_PENDING';
    }

    public function canApproveSecurity()
    {
        return $this->status === 'SECURITY_APPROVAL_PENDING' ||
               ($this->status === 'QC_APPROVAL_PENDING' && $this->qc_approved_at);
    }

    public function isFullyApproved()
    {
        return $this->status === 'QC_AND_SECURITY_APPROVED';
    }
}
