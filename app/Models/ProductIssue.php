<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductIssue extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'datetime',
        'return_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
