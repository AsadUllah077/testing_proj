<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    /**
     * Show the form for assigning permissions to a role.
     */
    public function create($roleId)
    {
        try {
            $companyId = Auth::user()->company_id;

            $role = Role::with(['permissions'])
                ->where('id', $roleId)
                ->where(function ($query) {})
                ->firstOrFail();
            $usersCount = User::where('role_id', $roleId)->count();

            // Get all permissions for this company and global permissions
            $permissions = Permission::where(function ($query) {})
                ->orderBy('name')
                ->get();

            // Get already assigned permission IDs
            $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

            return view('pages.users.role.permission', compact('role', 'permissions', 'assignedPermissionIds', 'usersCount'));

        } catch (\Exception $e) {
            Log::error('Error loading role permissions: '.$e->getMessage());

            return redirect()->route('users.role.index')
                ->with('error', 'Role not found or cannot assign permissions');
        }
    }

    /**
     * Store/Update role permissions.
     */
    public function store(Request $request, $roleId)
    {
        try {
            $companyId = Auth::user()->company_id;

            $role = Role::where('id', $roleId)
                ->where(function ($query) {})
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permissionIds = $request->input('permissions', []);

            // Validate that all permissions belong to the same company or are global
            $validPermissionIds = Permission::whereIn('id', $permissionIds)
                ->where(function ($query) {})
                ->pluck('id')
                ->toArray();

            // Sync permissions
            $role->syncPermissions($validPermissionIds);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully!',

            ]);

        } catch (\Exception $e) {
            Log::error('Error assigning role permissions: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error assigning permissions: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get role permissions for API.
     */
    public function getRolePermissions($roleId)
    {
        try {
            $companyId = Auth::user()->company_id;

            $role = Role::with(['permissions'])
                ->where('id', $roleId)
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                })
                ->firstOrFail();

            $permissions = $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'company' => $permission->company->name ?? 'System',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions_count' => $role->permissions_count,
                    ],
                    'permissions' => $permissions,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching role permissions',
            ], 500);
        }
    }

    public function getUsersByRole(Role $role)
    {
        try {
            $search = request('search');
            $perPage = request('per_page', 20);

            $users = User::where('role_id', $role->id)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->paginate($perPage);

            // Add full_name and initials for each user
            $users->getCollection()->transform(function ($user) {
                $user->full_name = $user->first_name.' '.$user->last_name;
                $user->initials = strtoupper(
                    substr($user->first_name, 0, 1).
                    (isset($user->last_name) ? substr($user->last_name, 0, 1) : '')
                );

                return $user;
            });

            return response()->json([
                'success' => true,
                'users' => $users,
                'total' => $users->total(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching users for role: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading users',
            ], 500);
        }
    }
}
