<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompensationReview extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'current_salary' => 'decimal:4', 'proposed_salary' => 'decimal:4', 'approved_salary' => 'decimal:4',
        'market_benchmark' => 'decimal:4', 'annual_budget_impact' => 'decimal:4',
        'effective_date' => 'date', 'approved_at' => 'datetime', 'applied_at' => 'datetime',
    ];
    public function employee() { return $this->belongsTo(Employee::class); }
}
