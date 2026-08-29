<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollVarianceComment extends Model
{
    use BelongsToCompany;
    protected $guarded = [];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function payRun() { return $this->belongsTo(PayRun::class); }
}
