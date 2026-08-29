<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Searchable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    protected $searchable = ['first_name', 'last_name', 'username', 'email', 'phone_number'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->full_name,
            'category' => 'User',
            'url' => route('users.user.edit', $this->id),
            'description' => $this->email,
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['company.currency'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function scopeSearch($query, $keyword)
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('first_name', 'like', "%{$keyword}%")
                ->orWhere('last_name', 'like', "%{$keyword}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"])
                ->orWhere('username', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone_number', 'like', "%{$keyword}%")
                ->orWhere('designation', 'like', "%{$keyword}%");
        });
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public function lastLogin()
    {
        return $this->hasOne(UserLoginLogs::class, 'user_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'linked_erp_user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Permissions granted directly to this user in addition to their role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps();
    }

    /**
     * Check if user is Super Admin (role_id = 1 or has Super Admin/Admin role)
     */
    public function isSuperAdmin()
    {
        // Check by role_id (role_id 1 is Super Admin)
        if ($this->role_id == 1) {
            return true;
        }

        // Check by role name as fallback
        if ($this->role) {
            $roleName = strtolower($this->role->name);
            $adminRoles = ['super admin', 'admin', 'administrator', 'superadmin'];

            return in_array($roleName, $adminRoles);
        }

        return false;
    }

    /**
     * Mapping of permission prefixes to module keywords.
     * Used to derive which module a permission belongs to.
     */
    protected function permissionModuleMap(): array
    {
        return [
            'home' => 'home',
            'users' => 'users',
            'account' => 'account',
            'hrm' => 'hrm',
            'payroll' => 'payroll',
            'qms' => 'qms',
            'product' => 'product',
            'warehouse' => ['warehouse', 'product'],
            'project' => 'project',
            'ticket' => 'ticket',
            'tasks' => 'tasks',
            'mailer' => 'mailer',
            'chat' => 'chat',
            'activity' => 'activity',
            'logistics' => 'logistics',
            'dashboard' => 'dashboard',
        ];
    }

    /**
     * Check if the user's role is a module-level admin for a given module keyword.
     * A role is a module admin if its name contains both the module keyword and "admin".
     * Example: "product admin", "hrm admin", "account admin"
     */
    public function isModuleAdmin(string $moduleKeyword): bool
    {
        if (! $this->role) {
            return false;
        }

        $roleName = strtolower($this->role->name);

        return str_contains($roleName, strtolower($moduleKeyword))
            && str_contains($roleName, 'admin');
    }

    /**
     * Resolve the module keyword from a permission string.
     * E.g. 'account.sale.view' → 'account'
     */
    protected function resolveModulesFromPermission(string $permission): array
    {
        $prefix = explode('.', $permission)[0];
        $modules = $this->permissionModuleMap()[$prefix] ?? [];

        return array_values((array) $modules);
    }

    protected function isModuleAdminForPermission(string $permission): bool
    {
        foreach ($this->resolveModulesFromPermission($permission) as $module) {
            if ($this->isModuleAdmin($module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission)
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Module admin check — if the user's role is an admin for this permission's module, grant access
        if ($this->isModuleAdminForPermission($permission)) {
            return true;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->role && $this->role->permissions()
            ->where('name', $permission)
            ->exists();
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Module admin check — if the user is a module admin for ANY permission's module, grant access
        foreach ($permissions as $permission) {
            if ($this->isModuleAdminForPermission($permission)) {
                return true;
            }
        }

        if ($this->permissions()->whereIn('name', $permissions)->exists()) {
            return true;
        }

        return $this->role && $this->role->permissions()
            ->whereIn('name', $permissions)
            ->exists();
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        $explicitPermissions = $this->permissions()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();

        if ($this->role) {
            $explicitPermissions = array_unique(array_merge(
                $explicitPermissions,
                $this->role->permissions()
                    ->whereIn('name', $permissions)
                    ->pluck('name')
                    ->toArray()
            ));
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $explicitPermissions, true) || $this->isModuleAdminForPermission($permission)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Check if user can view a specific module
     */
    public function canViewModule($moduleName)
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        $modulePermissions = [
            'home' => ['home.dashboard.view'],
            'users' => ['users.dashboard.view', 'users.user.view', 'users.role.view', 'users.permission.view'],
            'accounting' => [
                'account.dashboard.view',
                'account.sale.view',
                'account.bill-invoice.view',
                'account.purchase-order.view',
                'account.journal-entries.view',
                'account.reports.view',
            ],
            'hrm' => [
                'hrm.dashboard.view',
                'hrm.employee.view',
                'hrm.leave.view',
                'hrm.attendance.view',
            ],
            'qms' => ['qms.dashboard.view'],
            'members' => ['members.view'],
            'third-party' => ['third-party.view'],
            'product' => [
                'product.dashboard.view',
                'warehouse.dashboard.view',
                'warehouse.products.view',
                'warehouse.master-data.view',
                'warehouse.barcode.view',
                'warehouse.technicians.view',
                'warehouse.parts.view',
                'warehouse.gate-pass.view',
                'warehouse.scrap.view',
                'warehouse.progress-sheet.view',
                'warehouse.scan.view',
                'warehouse.product-issue.view',
            ],
            'project' => ['project.view'],
            'mailer' => ['mailer.view'],
            'fileshare' => ['fileshare.view'],
            'tech-support' => ['tech-support.view'],
            'chat' => ['chat.view'],
            'activity-logs' => ['activity.logs.view'],
            'dashboard' => ['dashboard.view'],
        ];

        if (! isset($modulePermissions[$moduleName])) {
            return false;
        }

        return $this->hasAnyPermission($modulePermissions[$moduleName]);
    }

    /**
     * Check if user can access a route/menu
     */
    public function canAccess($permissionOrRoute)
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if it's a permission string
        if (str_contains($permissionOrRoute, '.')) {
            return $this->hasPermission($permissionOrRoute);
        }

        // Handle route-based checking
        $routePermissions = [
            // Main dashboard
            'dashboard' => 'dashboard.view',

            // Users module
            'users.dashboard' => 'users.dashboard.view',
            'users.permission.index' => 'users.permission.view',
            'users.role.index' => 'users.role.view',
            'users.user.index' => 'users.user.view',

            // Home module
            'home.dashboard' => 'home.dashboard.view',
            'home.companies.index' => 'home.companies.view',

            // Accounting module
            'account.dashboard' => 'account.dashboard.view',
            'account.sale-invoice.index' => 'account.sale.view',
            'account.bill-invoice.index' => 'account.bill-invoice.view',
            'account.purchase-order.index' => 'account.purchase-order.view',
            'account.journal-entries.index' => 'account.journal-entries.view',
            'account.report.journal' => 'account.reports.view',
            'account.organization-settings.index' => 'account.organization-settings.view',
            'account.kpi' => 'account.kpi.view',

            // HRM module
            'hrm.index' => 'hrm.dashboard.view',
            'hrm.employee-type.index' => 'hrm.employee-type.view',
            'hrm.employee.index' => 'hrm.employee.view',
            'hrm.attendance.index' => 'hrm.attendance.view',
            'hrm.calendar' => 'hrm.leave.view',
            'hrm.leave.index' => 'hrm.leave.view',
            'hrm.leave-types.index' => 'hrm.leave-types.view',
            'hrm.department.index' => 'hrm.department.view',

            // QMS module
            'qms.index' => 'qms.dashboard.view',
            'qms.documentmanagment.index' => 'qms.document.view',

            // Other modules
            'activity.logs.index' => 'activity.logs.view',
        ];

        // Check if exact route exists
        if (isset($routePermissions[$permissionOrRoute])) {
            return $this->hasPermission($routePermissions[$permissionOrRoute]);
        }

        // Check for route patterns (e.g., account.*)
        foreach ($routePermissions as $routePattern => $permission) {
            if (str_contains($routePattern, '.*') && str_starts_with($permissionOrRoute, str_replace('.*', '', $routePattern))) {
                return $this->hasPermission($permission);
            }
        }

        return false;
    }

    /**
     * Check if user can access any route in a module
     */
    public function canAccessModule($modulePrefix)
    {
        // Super admin check - bypass permission checking
        if ($this->isSuperAdmin()) {
            return true;
        }

        $moduleRoutePermissions = [
            'users' => 'users.dashboard.view',
            'home' => 'home.dashboard.view',
            'account' => 'account.dashboard.view',
            'hrm' => 'hrm.dashboard.view',
            'qms' => 'qms.dashboard.view',
            'payroll' => 'payroll.dashboard.view',
            'activity.logs' => 'activity.logs.view',
            'product' => 'product.dashboard.view',
            'warehouse' => 'warehouse.dashboard.view',
        ];

        if (isset($moduleRoutePermissions[$modulePrefix])) {
            return $this->hasPermission($moduleRoutePermissions[$modulePrefix]);
        }

        return false;
    }

    /**
     * Get all permissions user has
     */
    public function getPermissions()
    {
        // Super admin gets all permissions
        if ($this->isSuperAdmin()) {
            return \App\Models\Permission::pluck('name')->toArray();
        }

        $permissions = $this->permissions->pluck('name')->toArray();
        if ($this->role) {
            $permissions = array_merge($permissions, $this->role->permissions->pluck('name')->toArray());
        }
        $adminPrefixes = [];

        foreach ($this->permissionModuleMap() as $permissionPrefix => $moduleKeywords) {
            foreach ((array) $moduleKeywords as $moduleKeyword) {
                if ($this->isModuleAdmin($moduleKeyword)) {
                    $adminPrefixes[] = $permissionPrefix;
                    break;
                }
            }
        }

        if ($adminPrefixes) {
            $modulePermissions = \App\Models\Permission::query()
                ->where(function ($query) use ($adminPrefixes) {
                    foreach (array_unique($adminPrefixes) as $prefix) {
                        $query->orWhere('name', 'like', $prefix.'.%');
                    }
                })
                ->pluck('name')
                ->toArray();

            $permissions = array_merge($permissions, $modulePermissions);
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Check if user can perform action (create, edit, delete, view)
     */
    public function canPerform($resource, $action = 'view')
    {
        $permission = strtolower($resource).'.'.$action;

        return $this->hasPermission($permission);
    }

    /**
     * Check if user is assigned to a task.
     */
    public function isAssignedToTask($task)
    {
        return $task->assignedEmployees->contains('id', $this->id);
    }

    /**
     * Get the product assignments for this user (as technician)
     */
    public function assignments()
    {
        return $this->hasMany(ProductAssignment::class, 'technician_id');
    }

    /**
     * Get active assignments for this user
     */
    public function activeAssignments()
    {
        return $this->hasMany(ProductAssignment::class, 'technician_id')
            ->whereNull('completed_at');
    }

    /**
     * Get completed assignments for this user
     */
    public function completedAssignments()
    {
        return $this->hasMany(ProductAssignment::class, 'technician_id')
            ->whereNotNull('completed_at');
    }

    /**
     * Get assignments assigned by this user
     */
    public function assignedByMe()
    {
        return $this->hasMany(ProductAssignment::class, 'assigned_by');
    }
}
