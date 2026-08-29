<?php
// app/Models/FunctionalTestingResult.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunctionalTestingResult extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'test_results' => 'array',
        'test_data' => 'array',
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
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
