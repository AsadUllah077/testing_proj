<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Searchable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_anonymous' => 'boolean',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'title', 'start_date', 'end_date', 'status'];

    protected $searchable = ['title', 'description'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function assignments()
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->title,
            'category' => 'Survey',
            'url' => route('hrm.surveys.index', ['q' => $this->title]),
            'description' => $this->description ?? '',
        ];
    }
}
