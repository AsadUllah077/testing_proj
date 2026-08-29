<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    protected $guarded = [];

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
}
