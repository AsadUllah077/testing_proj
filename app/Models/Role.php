<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use LogsActivity, SoftDeletes;

    protected $guarded = [];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function syncPermissions(array $permissionIds): array
    {
        return $this->permissions()->sync($permissionIds);
    }

    // In App\Models\Role.php

    // ... existing code ...

    public function hasPermission($permission): bool
    {
        return $this->permissions()
            ->where('name', $permission)
            ->exists();
    }

    public function givePermissionsTo(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function removePermissions(array $permissionIds): void
    {
        $this->permissions()->detach($permissionIds);
    }
}
