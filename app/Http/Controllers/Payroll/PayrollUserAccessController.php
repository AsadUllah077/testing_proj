<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollUserAccessController extends Controller
{
    public function index(Request $request)
    {
        $actor = $this->authorizePayrollUserManagement();
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $search = trim((string) $request->input('q', ''));
        $from = $request->input('from_date');
        $to = $request->input('to_date');

        $users = User::with(['company', 'companies', 'role', 'permissions'])
            ->when($search, fn ($query) => $query->search($search))
            ->when($from || $to, fn ($query) => $query->dateRange($from, $to))
            ->where(fn ($query) => $this->payrollUsersOnly($query))
            ->where(function ($query) use ($actor) {
                $query->where('company_id', $actor->company_id)
                    ->orWhereHas('companies', fn ($company) => $company->where('companies.id', $actor->company_id));
            })
            ->latest()
            ->paginate($perPage);

        $data = compact('users', 'search', 'from', 'to', 'perPage');
        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.organization-settings.user.table', $data)->render(),
                'pagination' => $users->appends($request->query())->links()->toHtml(),
            ]);
        }

        return view('pages.payroll.organization-settings.user.index', $data);
    }

    public function create()
    {
        $this->authorizePayrollUserManagement();

        return view('pages.payroll.organization-settings.user.create', $this->userFormData());
    }

    public function store(Request $request)
    {
        $actor = $this->authorizePayrollUserManagement();
        $validated = $this->validateUser($request);
        $role = $this->allowedRoles($actor)->findOrFail($validated['role_id']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);

        $user = DB::transaction(function () use ($request, $validated, $role, $permissionIds, $actor) {
            unset($validated['permissions']);
            $validated['company_id'] = $actor->company_id;
            $validated['role_id'] = $role->id;
            $validated['status'] = $request->boolean('status');
            $validated['profile_pic'] = $request->hasFile('profile_pic')
                ? $this->storeProfilePicture($request)
                : 'assets/images/default.png';

            $user = User::create($validated);
            $user->companies()->syncWithoutDetaching([$actor->company_id]);
            $user->permissions()->sync($permissionIds);
            UserLog::create([
                'user_id' => $user->id,
                'performed_by' => $actor->id,
                'action' => 'create',
                'changed_fields' => null,
            ]);

            return $user;
        });

        return redirect()->route('payroll.organization-settings.users.index')
            ->with('success', "{$user->full_name} was created successfully.");
    }

    public function edit(User $user)
    {
        $actor = $this->authorizePayrollUserManagement();
        $this->authorizeTargetUser($actor, $user);

        return view('pages.payroll.organization-settings.user.edit', array_merge(
            $this->userFormData($user),
            ['user' => $user->load('permissions')]
        ));
    }

    public function update(Request $request, User $user)
    {
        $actor = $this->authorizePayrollUserManagement();
        $this->authorizeTargetUser($actor, $user);
        $validated = $this->validateUser($request, $user);
        $role = $this->allowedRoles($actor)->findOrFail($validated['role_id']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);

        $permissionIds = array_unique(array_merge(
            $permissionIds,
            $user->permissions()->where('name', 'not like', 'payroll.%')->pluck('permissions.id')->all()
        ));

        DB::transaction(function () use ($request, $validated, $role, $permissionIds, $user, $actor) {
            unset($validated['permissions']);
            $validated['company_id'] = $user->company_id;
            $validated['role_id'] = $role->id;
            $validated['status'] = $request->boolean('status');

            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            if ($request->hasFile('profile_pic')) {
                if ($user->profile_pic && $user->profile_pic !== 'assets/images/default.png' && File::exists(public_path($user->profile_pic))) {
                    File::delete(public_path($user->profile_pic));
                }
                $validated['profile_pic'] = $this->storeProfilePicture($request);
            }

            $original = $user->getOriginal();
            $user->update($validated);
            $user->companies()->syncWithoutDetaching([$actor->company_id]);
            $user->permissions()->sync($permissionIds);

            $loggedChanges = $validated;
            unset($loggedChanges['password']);
            UserLog::create([
                'user_id' => $user->id,
                'performed_by' => $actor->id,
                'action' => 'update',
                'changed_fields' => array_diff_assoc($loggedChanges, $original),
            ]);
        });

        return redirect()->route('payroll.organization-settings.users.index')
            ->with('success', "{$user->full_name} was updated successfully.");
    }

    public function destroy(User $user)
    {
        $actor = $this->authorizePayrollUserManagement();
        $this->authorizeTargetUser($actor, $user);
        abort_if($actor->is($user), 403, 'You cannot delete your own account.');
        $user->delete();

        return response()->json(['success' => ['User deleted successfully.']]);
    }

    public function export()
    {
        $actor = $this->authorizePayrollUserManagement();
        $users = User::with(['company', 'role'])
            ->where(fn ($query) => $this->payrollUsersOnly($query))
            ->where(function ($query) use ($actor) {
                $query->where('company_id', $actor->company_id)
                    ->orWhereHas('companies', fn ($company) => $company->where('companies.id', $actor->company_id));
            })
            ->latest()
            ->get();

        return Pdf::loadView('pdf.users', compact('users'))->download('payroll-users.pdf');
    }

    public function createRole()
    {
        $this->authorizePayrollUserManagement();

        return view('pages.account.organization-settings.role.create', [
            'pageTitle' => 'Create Payroll Role',
            'pageDescription' => 'Create a role using payroll permissions only.',
            'permissionPrefix' => 'payroll.',
            'backRoute' => 'payroll.organization-settings.users.create',
            'storeRoute' => 'payroll.organization-settings.roles.store',
            'permissions' => $this->allowedPermissions()->get()
                ->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
        ]);
    }

    public function editRole(Role $role)
    {
        $actor = $this->authorizePayrollUserManagement();
        $this->authorizePayrollRole($actor, $role);

        return view('pages.account.organization-settings.role.create', [
            'role' => $role,
            'pageTitle' => 'Edit Payroll Role',
            'pageDescription' => 'Update this role using payroll permissions only.',
            'permissionPrefix' => 'payroll.',
            'backRoute' => 'payroll.organization-settings.users.create',
            'formAction' => route('payroll.organization-settings.roles.update', $role),
            'formMethod' => 'PUT',
            'submitLabel' => 'Save role',
            'permissions' => $this->allowedPermissions()->get()
                ->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
            'assignedPermissionIds' => $role->permissions()
                ->where('name', 'like', 'payroll.%')
                ->pluck('permissions.id')
                ->all(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $actor = $this->authorizePayrollUserManagement();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->whereNull('deleted_at')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $this->guardPayrollRoleName($actor, $validated['name']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);
        $role = DB::transaction(function () use ($validated, $permissionIds) {
            $role = Role::create(['name' => trim($validated['name'])]);
            $role->permissions()->sync($permissionIds);

            return $role;
        });

        return redirect()->route('payroll.organization-settings.users.create')
            ->with('success', "{$role->name} was created with payroll permissions.");
    }

    public function updateRole(Request $request, Role $role)
    {
        $actor = $this->authorizePayrollUserManagement();
        $this->authorizePayrollRole($actor, $role);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)->whereNull('deleted_at')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $this->guardPayrollRoleName($actor, $validated['name']);
        $permissionIds = array_unique(array_merge(
            $this->validatedPermissionIds($validated['permissions'] ?? []),
            $role->permissions()->where('name', 'not like', 'payroll.%')->pluck('permissions.id')->all()
        ));

        DB::transaction(function () use ($role, $validated, $permissionIds) {
            $role->update(['name' => trim($validated['name'])]);
            $role->permissions()->sync($permissionIds);
        });

        return redirect()->route('payroll.organization-settings.users.create')
            ->with('success', "{$role->name} was updated successfully.");
    }

    private function authorizePayrollUserManagement(): User
    {
        $user = Auth::user();
        $isHrAdmin = strcasecmp((string) $user?->role?->name, 'HR Admin') === 0;
        abort_unless($user && ($user->isSuperAdmin() || $isHrAdmin), 403);

        return $user;
    }

    private function authorizeTargetUser(User $actor, User $target): void
    {
        $belongsToCompany = (int) $target->company_id === (int) $actor->company_id
            || $target->companies()->where('companies.id', $actor->company_id)->exists();
        abort_unless($belongsToCompany, 404);
        abort_unless($this->isPayrollUser($target), 404);
        abort_if(! $actor->isSuperAdmin() && $target->isSuperAdmin(), 403, 'HR Admins cannot manage administrators.');
    }

    private function allowedRoles(User $actor)
    {
        return Role::query()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereHas('permissions', fn ($permission) => $permission->where('name', 'like', 'payroll.%'))
                    ->orWhereIn('name', ['HR Admin', 'Super Admin']);
            })
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->whereNotIn('name', ['Super Admin', 'Admin', 'Administrator', 'Superadmin']));
    }

    private function authorizePayrollRole(User $actor, Role $role): void
    {
        $isPayrollRole = in_array($role->name, ['HR Admin', 'Super Admin'], true)
            || $role->permissions()->where('name', 'like', 'payroll.%')->exists();
        abort_unless($isPayrollRole, 404);

        if (! $actor->isSuperAdmin()) {
            abort_if(in_array(strtolower($role->name), ['super admin', 'admin', 'administrator', 'superadmin', 'hr admin'], true), 403, 'This protected role cannot be edited.');
        }
    }

    private function guardPayrollRoleName(User $actor, string $name): void
    {
        $normalizedName = strtolower(trim($name));
        $privilegedNames = ['super admin', 'admin', 'administrator', 'superadmin'];
        $createsModuleAdmin = str_contains($normalizedName, 'payroll') && str_contains($normalizedName, 'admin');
        if (! $actor->isSuperAdmin() && (in_array($normalizedName, $privilegedNames, true) || $createsModuleAdmin)) {
            throw ValidationException::withMessages([
                'name' => 'HR Admins cannot create or rename an administrator role.',
            ]);
        }
    }

    private function payrollUsersOnly($query): void
    {
        $query->whereHas('permissions', fn ($permission) => $permission->where('name', 'like', 'payroll.%'))
            ->orWhereHas('role.permissions', fn ($permission) => $permission->where('name', 'like', 'payroll.%'))
            ->orWhereHas('role', fn ($role) => $role->whereIn('name', ['HR Admin', 'Super Admin']));
    }

    private function isPayrollUser(User $user): bool
    {
        return in_array($user->role?->name, ['HR Admin', 'Super Admin'], true)
            || $user->permissions()->where('name', 'like', 'payroll.%')->exists()
            || $user->role?->permissions()->where('name', 'like', 'payroll.%')->exists();
    }

    private function allowedPermissions()
    {
        return Permission::query()
            ->whereNull('deleted_at')
            ->where('name', 'like', 'payroll.%')
            ->orderBy('name');
    }

    private function userFormData(?User $user = null): array
    {
        $actor = Auth::user();
        $roles = $this->allowedRoles($actor)
            ->with(['permissions' => fn ($query) => $query->where('name', 'like', 'payroll.%')])
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
            'rolePermissionIds' => $roles->mapWithKeys(
                fn ($role) => [(string) $role->id => $role->permissions->pluck('id')->values()->all()]
            )->all(),
            'editableRoleIds' => $roles
                ->filter(fn ($role) => $actor->isSuperAdmin() || ! in_array(strtolower($role->name), ['super admin', 'admin', 'administrator', 'superadmin', 'hr admin'], true))
                ->pluck('id')
                ->values()
                ->all(),
            'permissions' => $this->allowedPermissions()->get()
                ->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
            'assignedPermissionIds' => $user?->permissions()->pluck('permissions.id')->all() ?? [],
        ];
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:191', Rule::unique('users')->ignore($user?->id)],
            'phone_number' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user?->id)],
            'designation' => ['required', 'string', 'max:150'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'profile_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'status' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);
    }

    private function validatedPermissionIds(array $permissionIds): array
    {
        $allowed = $this->allowedPermissions()->whereIn('id', $permissionIds)->pluck('id')->all();
        abort_unless(count($allowed) === count(array_unique($permissionIds)), 403, 'One or more permissions cannot be assigned.');

        return $allowed;
    }

    private function storeProfilePicture(Request $request): string
    {
        $file = $request->file('profile_pic');
        $imageName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('uploads/users'), $imageName);

        return 'uploads/users/'.$imageName;
    }
}
