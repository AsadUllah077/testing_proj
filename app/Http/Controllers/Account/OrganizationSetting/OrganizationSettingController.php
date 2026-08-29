<?php

namespace App\Http\Controllers\Account\OrganizationSetting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
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

class OrganizationSettingController extends Controller
{
    /**
     * Display the organization settings index page.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $company = $user->company;

            // Include users whose primary company is this company and users who
            // have access through the company_user pivot table.
            $accountingUsers = User::where(function ($query) use ($company) {
                $query->where('company_id', $company->id)
                    ->orWhereHas('companies', fn ($q) => $q->where('companies.id', $company->id));
            })
                ->where(fn ($query) => $this->accountingUsersOnly($query))
                ->get();

            return view('pages.account.organization-settings.index', compact('company', 'accountingUsers'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function accountantManagers(Request $request)
    {
        $this->authorizeAccountingUserManagement();

        $perPage = (int) $request->input('per_page', 20);
        $search = trim((string) $request->input('q', ''));
        $from = $request->input('from_date');
        $to = $request->input('to_date');

        $query = User::with(['company', 'companies', 'lastLogin', 'role', 'permissions'])
            ->when($search, function ($q) use ($search) {
                $q->search($search); // assuming scopeSearch exists
            })
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->dateRange($from, $to); // assuming scopeDateRange exists
            })
            ->where(fn ($q) => $this->accountingUsersOnly($q))
            ->where(function ($q) {
                $companyId = Auth::user()->company_id;
                $q->where('company_id', $companyId)
                    ->orWhereHas('companies', fn ($company) => $company->where('companies.id', $companyId));
            });

        $users = $query->latest()->paginate($perPage);

        $data = [
            'users' => $users,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'perPage' => $perPage,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.organization-settings.user.table', $data)->render(),
                'pagination' => $users->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.organization-settings.user.index', $data);
    }

    public function createUser()
    {
        $this->authorizeAccountingUserManagement();

        return view('pages.account.organization-settings.user.create', $this->userFormData());
    }

    public function createRole()
    {
        $this->authorizeAccountingUserManagement();

        return view('pages.account.organization-settings.role.create', [
            'permissions' => $this->allowedPermissions()->get()
                ->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
        ]);
    }

    public function editRole(Role $role)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $this->authorizeAccountingRole($actor, $role);

        return view('pages.account.organization-settings.role.create', [
            'role' => $role,
            'pageTitle' => 'Edit Accounting Role',
            'pageDescription' => 'Update this role using accounting permissions only.',
            'formAction' => route('account.organization-settings.roles.update', $role),
            'formMethod' => 'PUT',
            'submitLabel' => 'Save role',
            'permissions' => $this->allowedPermissions()->get()
                ->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
            'assignedPermissionIds' => $role->permissions()
                ->where('name', 'like', 'account.%')
                ->pluck('permissions.id')
                ->all(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->whereNull('deleted_at')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $this->guardAccountingRoleName($actor, $validated['name']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);
        $role = DB::transaction(function () use ($validated, $permissionIds) {
            $role = Role::create(['name' => trim($validated['name'])]);
            $role->permissions()->sync($permissionIds);

            return $role;
        });

        return redirect()->route('account.organization-settings.users.create')
            ->with('success', "{$role->name} was created with accounting permissions.");
    }

    public function updateRole(Request $request, Role $role)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $this->authorizeAccountingRole($actor, $role);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)->whereNull('deleted_at')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $this->guardAccountingRoleName($actor, $validated['name']);
        $permissionIds = array_unique(array_merge(
            $this->validatedPermissionIds($validated['permissions'] ?? []),
            $role->permissions()->where('name', 'not like', 'account.%')->pluck('permissions.id')->all()
        ));

        DB::transaction(function () use ($role, $validated, $permissionIds) {
            $role->update(['name' => trim($validated['name'])]);
            $role->permissions()->sync($permissionIds);
        });

        return redirect()->route('account.organization-settings.users.create')
            ->with('success', "{$role->name} was updated successfully.");
    }

    public function exportUsers()
    {
        $actor = $this->authorizeAccountingUserManagement();
        $users = User::with(['company', 'role'])
            ->where(fn ($query) => $this->accountingUsersOnly($query))
            ->where(function ($query) use ($actor) {
                $query->where('company_id', $actor->company_id)
                    ->orWhereHas('companies', fn ($company) => $company->where('companies.id', $actor->company_id));
            })
            ->latest()
            ->get();

        return Pdf::loadView('pdf.users', compact('users'))
            ->download('accounting-users.pdf');
    }

    public function storeUser(Request $request)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $validated = $this->validateUser($request);
        $role = $this->allowedRoles($actor)->findOrFail($validated['role_id']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);

        $user = DB::transaction(function () use ($request, $validated, $role, $permissionIds, $actor) {
            unset($validated['permissions']);
            $validated['company_id'] = $actor->company_id;
            $validated['role_id'] = $role->id;
            $validated['status'] = $request->boolean('status');

            if ($request->hasFile('profile_pic')) {
                $validated['profile_pic'] = $this->storeProfilePicture($request);
            } else {
                $validated['profile_pic'] = 'assets/images/default.png';
            }

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

        return redirect()->route('account.organization-settings.users')
            ->with('success', "{$user->full_name} was created successfully.");
    }

    public function editUser(User $user)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $this->authorizeTargetUser($actor, $user);

        return view('pages.account.organization-settings.user.edit', array_merge(
            $this->userFormData($user),
            ['user' => $user->load('permissions')]
        ));
    }

    public function updateUser(Request $request, User $user)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $this->authorizeTargetUser($actor, $user);
        $validated = $this->validateUser($request, $user);
        $role = $this->allowedRoles($actor)->findOrFail($validated['role_id']);
        $permissionIds = $this->validatedPermissionIds($validated['permissions'] ?? []);
        $permissionIds = array_unique(array_merge(
            $permissionIds,
            $user->permissions()->where('name', 'not like', 'account.%')->pluck('permissions.id')->all()
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
                if ($user->profile_pic && File::exists(public_path($user->profile_pic))) {
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

        return redirect()->route('account.organization-settings.users')
            ->with('success', "{$user->full_name} was updated successfully.");
    }

    public function destroyUser(User $user)
    {
        $actor = $this->authorizeAccountingUserManagement();
        $this->authorizeTargetUser($actor, $user);
        abort_if($actor->is($user), 403, 'You cannot delete your own account.');

        $user->delete();

        return response()->json(['success' => ['User deleted successfully.']]);
    }

    private function authorizeAccountingUserManagement(): User
    {
        $user = Auth::user();
        $isAccountantManager = strcasecmp((string) $user?->role?->name, 'Accountant Manager') === 0;
        abort_unless($user && ($user->isSuperAdmin() || $isAccountantManager), 403);

        return $user;
    }

    private function authorizeTargetUser(User $actor, User $target): void
    {
        $belongsToCompany = (int) $target->company_id === (int) $actor->company_id
            || $target->companies()->where('companies.id', $actor->company_id)->exists();
        abort_unless($belongsToCompany, 404);
        abort_unless($this->isAccountingUser($target), 404);
        abort_if(! $actor->isSuperAdmin() && $target->isSuperAdmin(), 403, 'Accountant Managers cannot manage administrators.');
    }

    private function allowedRoles(User $actor)
    {
        return Role::query()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereHas('permissions', fn ($permission) => $permission->where('name', 'like', 'account.%'))
                    ->orWhereIn('name', ['Accountant Manager', 'Super Admin']);
            })
            ->when(! $actor->isSuperAdmin(), function ($query) {
                $query->whereNotIn('name', ['Super Admin', 'Admin', 'Administrator', 'Superadmin']);
            });
    }

    private function authorizeAccountingRole(User $actor, Role $role): void
    {
        $isAccountingRole = in_array($role->name, ['Accountant Manager', 'Super Admin'], true)
            || $role->permissions()->where('name', 'like', 'account.%')->exists();
        abort_unless($isAccountingRole, 404);

        if (! $actor->isSuperAdmin()) {
            abort_if(in_array(strtolower($role->name), ['super admin', 'admin', 'administrator', 'superadmin', 'accountant manager'], true), 403, 'This protected role cannot be edited.');
        }
    }

    private function guardAccountingRoleName(User $actor, string $name): void
    {
        $normalizedName = strtolower(trim($name));
        $privilegedNames = ['super admin', 'admin', 'administrator', 'superadmin'];
        $createsModuleAdmin = str_contains($normalizedName, 'account') && str_contains($normalizedName, 'admin');
        if (! $actor->isSuperAdmin() && (in_array($normalizedName, $privilegedNames, true) || $createsModuleAdmin)) {
            throw ValidationException::withMessages([
                'name' => 'Accountant Managers cannot create or rename an administrator role.',
            ]);
        }
    }

    private function accountingUsersOnly($query): void
    {
        $query->whereHas('permissions', fn ($permission) => $permission->where('name', 'like', 'account.%'))
            ->orWhereHas('role.permissions', fn ($permission) => $permission->where('name', 'like', 'account.%'))
            ->orWhereHas('role', fn ($role) => $role->whereIn('name', ['Accountant Manager', 'Super Admin']));
    }

    private function isAccountingUser(User $user): bool
    {
        return in_array($user->role?->name, ['Accountant Manager', 'Super Admin'], true)
            || $user->permissions()->where('name', 'like', 'account.%')->exists()
            || $user->role?->permissions()->where('name', 'like', 'account.%')->exists();
    }

    private function allowedPermissions()
    {
        return Permission::query()
            ->whereNull('deleted_at')
            ->where('name', 'like', 'account.%')
            ->orderBy('name');
    }

    private function userFormData(?User $user = null): array
    {
        $actor = Auth::user();
        $roles = $this->allowedRoles($actor)
            ->with(['permissions' => fn ($query) => $query->where('name', 'like', 'account.%')])
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
            'rolePermissionIds' => $roles->mapWithKeys(
                fn ($role) => [(string) $role->id => $role->permissions->pluck('id')->values()->all()]
            )->all(),
            'editableRoleIds' => $roles
                ->filter(fn ($role) => $actor->isSuperAdmin() || ! in_array(strtolower($role->name), ['super admin', 'admin', 'administrator', 'superadmin', 'accountant manager'], true))
                ->pluck('id')
                ->values()
                ->all(),
            'permissions' => $this->allowedPermissions()->get()->groupBy(fn ($permission) => explode('.', $permission->name)[1] ?? 'general'),
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

    public function company($id)
    {
        $company = Company::findOrFail($id);

        return view('pages.account.organization-settings.company.show', compact('company'));
    }

    public function companyEdit($id)
    {
        $company = Company::findOrFail($id);
        $countries = Country::latest()->get();
        $currencies = Currency::latest()->get();

        $data = [
            'company' => $company,
            'countries' => $countries,
            'currencies' => $currencies,
        ];

        return view('pages.account.organization-settings.company.edit', $data);
    }
}
