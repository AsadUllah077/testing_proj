<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Searchable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warning extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $casts = [
        'warning_date' => 'date',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'employee_id', 'warning_date', 'title'];

    protected $searchable = ['title', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->title,
            'category' => 'Warning',
            'url' => route('hrm.warnings.index', ['q' => $this->title]),
            'description' => $this->employee->full_name ?? '',
        ];
    }
}
