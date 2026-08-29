<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Searchable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'title', 'date', 'from_employee_id', 'against_employee_id'];

    protected $searchable = ['title', 'description'];

    public function fromEmployee()
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    public function againstEmployee()
    {
        return $this->belongsTo(Employee::class, 'against_employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->title,
            'category' => 'Complaint',
            'url' => route('hrm.complaints.index', ['q' => $this->title]),
            'description' => $this->description ?? '',
        ];
    }
}
