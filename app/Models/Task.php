<?php

// app/Models/Task.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_OVERDUE = 'overdue';

    const STATUS_REVISION_REQUESTED = 'revision_requested';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    // Category constants
    const CATEGORY_DAILY = 'daily';

    const CATEGORY_WEEKLY = 'weekly';

    const CATEGORY_MONTHLY = 'monthly';

    /**
     * Get the company associated with the task.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this task.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the department associated with the task.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all assigned employees for this task.
     */
    public function assignedEmployees()
    {
        return $this->belongsToMany(Employee::class, 'task_assignments')
            ->withTimestamps();
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue()
    {
        return $this->deadline < now() && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_OVERDUE]);
    }

    /**
     * Scope for overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_OVERDUE]);
    }

    /**
     * Scope for tasks by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tasks by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for tasks by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for tasks in a department.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get status color for display.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_OVERDUE => 'bg-red-100 text-red-800',
            self::STATUS_REVISION_REQUESTED => 'bg-yellow-100 text-yellow-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get priority color for display.
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            self::PRIORITY_LOW => 'bg-green-100 text-green-800',
            self::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-800',
            self::PRIORITY_HIGH => 'bg-red-100 text-red-800',
        ];

        return $colors[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get formatted deadline.
     */
    public function getFormattedDeadlineAttribute()
    {
        return $this->deadline ? $this->deadline->format('M d, Y h:i A') : null;
    }

    /**
     * Get all status options.
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_REVISION_REQUESTED => 'Revision Requested',
        ];
    }

    /**
     * Get all priority options.
     */
    public static function getPriorityOptions()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
        ];
    }

    /**
     * Get all category options.
     */
    public static function getCategoryOptions()
    {
        return [
            self::CATEGORY_DAILY => 'Daily Task',
            self::CATEGORY_WEEKLY => 'Weekly Task',
            self::CATEGORY_MONTHLY => 'Monthly Task',
        ];
    }

    /**
     * Get all deliverables for this task.
     */
    public function deliverables()
    {
        return $this->hasMany(TaskDeliverable::class);
    }

    /**
     * Check if user is assigned to this task.
     */
    public function isAssignedTo($user)
    {
        if (! is_object($user)) {
            return false;
        }

        return $this->assignedEmployees->contains('id', $user->id);
    }

    // Add this to your Task model
    const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'on_hold' => 'On Hold',
        'cancelled' => 'Cancelled',
    ];
}
