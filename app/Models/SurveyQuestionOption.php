<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestionOption extends Model
{
    protected $guarded = [];

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
}
