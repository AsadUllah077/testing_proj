<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryBenchmark extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'median_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
    ];

    /**
     * Scope for specific designation.
     */
    public function scopeForDesignation($query, $designation)
    {
        return $query->where('designation', $designation);
    }

    /**
     * Get comp-ratio for a given salary.
     * Comp-ratio = Salary / Median Salary * 100
     */
    public function calculateCompRatio($salary)
    {
        if ($this->median_salary <= 0) return 0;
        return ($salary / $this->median_salary) * 100;
    }
}
