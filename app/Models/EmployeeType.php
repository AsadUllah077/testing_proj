<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeType extends Model
{
    use HasFactory, SoftDeletes,BelongsToCompany, LogsActivity;

    protected $fillable = [
        'employee_type',
        'company_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the employee type.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
