<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayItem extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable;
    protected $fillable = [
        'company_id',
        'statutory_body_id',
        'name',
        'type',
        'account_id',
        'calculation_type',
        'percentage_value',
        'base_item_id',
        'is_taxable',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'name', 'type', 'account_id', 'calculation_type', 'percentage_value', 'base_item_id', 'is_taxable'];

    public function baseItem()
    {
        return $this->belongsTo(PayItem::class, 'base_item_id');
    }
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
    public function employeeStructures()
    {
        return $this->hasMany(EmployeePayStructure::class);
    }
    public function statutoryBody()
    {
        return $this->belongsTo(StatutoryBody::class);
    }
}
