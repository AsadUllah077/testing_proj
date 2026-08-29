<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'category',
        'priority',
        'content',
        'send_to_type',
        'send_to_department_id',
        'send_to_employee_id',
    ];

    protected $casts = [
        'send_to_department_id' => 'array',
        'send_to_employee_id' => 'array',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'send_to_department_id');
    }

    /**
     * The manager employee whose direct reports receive this announcement.
     * send_to_employee_id stores the manager's employee ID.
     */
    public function reportingToEmployee()
    {
        return $this->belongsTo(Employee::class, 'send_to_employee_id');
    }
}
