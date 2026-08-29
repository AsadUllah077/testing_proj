<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsGoodsDeclaration extends Model
{
    use SoftDeletes;

    protected $table = 'logistics_goods_declarations';

    protected $fillable = [
        'gd_number',
        'booking_v2_id',
        'declaration_type',
        'collectorate',
        'bl_awb_number',
        'importer',
        'ntn_cnic',
        'exporter',
        'country_of_origin',
        'hs_code',
        'description',
        'quantity',
        'unit',
        'gross_weight',
        'net_weight',
        'customs_value',
        'delivery_term',
        'delivery_term_value',
        'currency',
        'exchange_rate',
        'customs_duty',
        'sales_tax',
        'income_tax',
        'additional_duty',
        'total_duty',
        'duty_payment_mode',
        'duty_payment_details',
        'landed_cost',
        'status',
        'exam_status',
        'filed_date',
        'approval_date',
        'remarks',
        'company_id',
    ];

    protected $casts = [
        'filed_date'    => 'date',
        'approval_date' => 'date',
        'duty_payment_details' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(LogisticsBookingV2::class, 'booking_v2_id');
    }

    public static function generateGdNumber(): string
    {
        $last = static::withTrashed()->latest('id')->value('gd_number');
        if ($last && preg_match('/GD-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }
        return 'GD-' . $next;
    }
}
