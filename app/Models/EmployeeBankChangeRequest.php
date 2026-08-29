<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeBankChangeRequest extends Model
{
    use BelongsToCompany;
    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];
    public function employee() { return $this->belongsTo(Employee::class); }
}
