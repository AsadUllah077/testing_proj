<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccountLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'chart_of_account_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
