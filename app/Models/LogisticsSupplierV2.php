<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsSupplierV2 extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;

    protected $table = 'logistics_suppliers_v2';

    protected $guarded = [];

    protected $casts = [
        'year_established' => 'integer',
        'annual_value' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'lead_time' => 'integer',
    ];

    /**
     * Generate next supplier ID: SUP-2026-XXXX
     */
    public static function generateSupplierId(): string
    {
        $year = date('Y');
        $last = static::withTrashed()->latest('id')->first();
        
        if ($last && $last->supplier_code && preg_match('/SUP-'.$year.'-(\d+)/', $last->supplier_code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }

        return 'SUP-'.$year.'-'.$next;
    }
}
