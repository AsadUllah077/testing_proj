<?php
// app/Models/GeneralTaskResult.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralTaskResult extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'additional_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(ProductAssignment::class, 'assignment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(Employee::class, 'submitted_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
