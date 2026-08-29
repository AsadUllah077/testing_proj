<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SurveyAnswer;
use App\Models\Survey;
use App\Models\SurveyQuestionOption;

class SurveyQuestion extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    // Relationship for answers used in analytics
    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id');
    }

    public function options()
    {
        return $this->hasMany(SurveyQuestionOption::class, 'question_id')
            ->orderBy('sort_order');
    }
}
