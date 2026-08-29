<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'response_id');
    }
}
