<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use LogsActivity, SoftDeletes, Sortable, BelongsToCompany;
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'tax_rate',
        'tax_type',
    ];

    public $sortable = ['name', 'code', 'tax_rate', 'tax_type'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function logs()
    {
        return $this->hasMany(TaxRateLog::class);
    }
    public function logChange($event, $old = null, $new = null)
    {
        TaxRateLog::create([
            'tax_rate_id' => $this->id,
            'company_id'  => $this->company_id,
            'user_id'     => auth('web')->id(),
            'event'       => $event,
            'old_values'  => $old,
            'new_values'  => $new,
        ]);
    }
}
