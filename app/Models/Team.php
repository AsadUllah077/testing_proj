<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'company_id', 'project_id', 'created_by'];

    public function members()
    {
        return $this->belongsToMany(Employee::class, 'team_members', 'team_id', 'employee_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
