<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class LogisticsSupplierScorecard extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'evaluation_period',
        'quality_score',
        'delivery_otd',
        'doc_compliance',
        'communication',
        'price_competitiveness',
        'overall_score',
        'grade',
        'remarks',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function supplier()
    {
        return $this->belongsTo(LogisticsSupplierV2::class, 'supplier_id');
    }

    /**
     * Boot function to calculate overall score and grade before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->overall_score = round(
                ($model->quality_score * 0.3) +
                ($model->delivery_otd * 0.3) +
                ($model->doc_compliance * 0.2) +
                ($model->communication * 10 * 0.1) +
                ($model->price_competitiveness * 10 * 0.1)
            );

            if ($model->overall_score >= 90) {
                $model->grade = 'A';
            } elseif ($model->overall_score >= 75) {
                $model->grade = 'B';
            } elseif ($model->overall_score >= 60) {
                $model->grade = 'C';
            } else {
                $model->grade = 'D';
            }
        });
    }
}
