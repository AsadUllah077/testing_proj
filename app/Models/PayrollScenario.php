<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollScenario extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $guarded = [];
    protected $casts = ['assumptions' => 'array', 'results' => 'array'];
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
