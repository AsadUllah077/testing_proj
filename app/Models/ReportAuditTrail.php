<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ReportAuditTrail extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'report_name',
        'action',
        'filters',
        'export_format',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'filters'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
