<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePayStructure extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;
    protected $fillable = [
        'company_id',
        'employee_id',
        'pay_item_id',
        'amount',
        'hourly_rate',
        'sort_order'
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function payItem()
    {
        return $this->belongsTo(PayItem::class);
    }
}
