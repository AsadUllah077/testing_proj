<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use App\Traits\Searchable;

class Employee extends Authenticatable
{
    use BelongsToCompany, HasApiTokens, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $searchable = ['full_name', 'email', 'phone_number', 'employee_id'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->full_name,
            'category' => 'Employee',
            'url' => route('hrm.employee.edit', $this->id),
            'description' => $this->phone_number ?? $this->email,
        ];
    }

    public $defaultSort = 'id';

    public $sortable = ['id', 'employee_id', 'full_name', 'email', 'status', 'reporting_to', 'designation', 'department_id', 'resign_date', 'date_of_joining'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmployeeType::class, 'employment_type_id');
    }

    public function linkedErpUser()
    {
        return $this->belongsTo(User::class, 'linked_erp_user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role) {
            return false;
        }

        return $this->role->permissions()->where('name', $permission)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if (! $this->role) {
            return false;
        }

        return $this->role->permissions()->whereIn('name', $permissions)->exists();
    }

    public function getPermissions(): array
    {
        if (! $this->role) {
            return [];
        }

        return $this->role->permissions->pluck('name')->toArray();
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function payStructures()
    {
        return $this->hasMany(EmployeePayStructure::class, 'employee_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    /**
     * Check if OTP is valid
     */
    public function isValidOtp($otp)
    {
        return $this->otp === $otp && ! $this->isOtpExpired();
    }

    public function getDisplayNameAttribute()
    {
        return $this->full_name;
    }

    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class, 'employee_id');
    }

    public function leaves()
    {
        return $this->hasMany(LeaveRecord::class, 'employee_id');
    }

    public function assignments()
    {
        return $this->hasMany(ProductAssignment::class, 'technician_id');
    }

    public function issuedProducts()
    {
        return $this->hasMany(Product::class, 'assigned_to');
    }

    public function productIssues()
    {
        return $this->hasMany(ProductIssue::class);
    }

    public function salaryAdvances()
    {
        return $this->hasMany(EmployeeSalaryAdvance::class);
    }
}
