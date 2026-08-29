<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxBracket extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'min_income',
        'max_income',
        'base_tax',
        'rate',
        'sort_order',
    ];

    protected $casts = [
        'min_income' => 'decimal:2',
        'max_income' => 'decimal:2',
        'base_tax'   => 'decimal:2',
        'rate'       => 'decimal:2',
    ];

    public function scopeForYear($query, string $year)
    {
        return $query->where('fiscal_year', $year)->orderBy('sort_order');
    }
}
