<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollCostAllocation extends Model
{
    use BelongsToCompany;
    protected $guarded = [];
    protected $casts = ['percentage' => 'decimal:4', 'effective_from' => 'date', 'effective_to' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
