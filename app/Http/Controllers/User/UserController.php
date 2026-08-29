<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserCreateRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $search = trim((string) $request->input('q', ''));
        $from = $request->input('from_date');
        $to = $request->input('to_date');

        $query = User::with('company', 'lastLogin')
            ->search($search)
            ->dateRange($from, $to)
            ->latest();

        // Show all users for Super Admin, filter by company for others
        if (!auth('web')->user()->isSuperAdmin()) {
            $query->where('company_id', auth('web')->user()->company_id);
        }

        $users = $query->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.users.user.table', compact('users'))->render(),
                'pagination' => $users->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.users.user.index', compact('users', 'search', 'from', 'to', 'perPage'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'Super Admin')
            ->whereNull('deleted_at')
            ->get();

        return view('pages.users.user.create', compact('roles'));
    }

    public function edit($id)
    {
        $user = User::with('role')->findOrFail($id);
        $companies = Company::orderBy('name')->get();
        $roles = Role::where('name', '!=', 'Super Admin')
            ->whereNull('deleted_at')
            ->get();

        return view('pages.users.user.edit', compact('user', 'companies', 'roles'));
    }

    public function show($id)
    {
        try {
            $user = User::with([
                'company',
                'role' => function ($q) {
                    $q->with([
                        'permissions' => function ($q) {
                            $q->orderBy('name');
                        }
                    ]);
                },
                'lastLogin' => function ($q) {
                    $q->orderBy('id', 'desc')->first();
                },
                'logs' => function ($q) {
                    $q->latest();
                },
                'logs.actor',
            ])->findOrFail($id);

            return view('pages.users.user.show', compact('user'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('users.user.index')
                ->withErrors(['error' => 'Failed to load user details.' . $th->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $original = $user->getOriginal();

            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'role_id' => ['exists:roles,id'],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'username' => ['nullable', 'string', 'max:100', 'unique:users,username,' . $user->id],
                'email' => ['required', 'email', 'max:191', 'unique:users,email,' . $user->id],
                'phone_number' => ['nullable', 'string', 'max:50'],
                'designation' => ['nullable', 'string', 'max:150'],
                'password' => ['nullable', 'string', 'min:6'],
                'profile_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
                'status' => ['nullable', 'boolean'],
            ]);

            $validated['status'] = $request->input('status', 1);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            if ($request->hasFile('profile_pic')) {
                if (!empty($user->profile_pic) && File::exists(public_path($user->profile_pic))) {
                    @File::delete(public_path($user->profile_pic));
                }

                $file = $request->file('profile_pic');
                $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/users'), $imageName);
                $validated['profile_pic'] = 'uploads/users/' . $imageName;
            }

            $user->update($validated);

            // Sync multi-company access
            if ($request->has('companies')) {
                $user->companies()->sync($request->companies);
            }

            // 🧾 Log only changed fields
            $changes = [];
            foreach ($validated as $key => $value) {
                if (array_key_exists($key, $original) && $original[$key] != $value) {
                    $changes[$key] = [
                        'old' => $original[$key],
                        'new' => $value,
                    ];
                }
            }

            if (!empty($changes)) {
                UserLog::create([
                    'user_id' => $user->id,
                    'performed_by' => Auth::id(),
                    'action' => 'update',
                    'changed_fields' => $changes,
                ]);
            }

            return to_route('users.user.index')->with('success', 'User updated and logged successfully!');
        } catch (\Throwable $th) {
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function store(UserCreateRequest $request)
    {
        try {
            $data = $request->validated();

            // Extract companies and remove from data to prevent mass-assignment error
            $companies = $data['companies'] ?? [];
            unset($data['companies']);

            // Handle image upload
            if ($request->hasFile('profile_pic')) {
                $profile_pic = $request->file('profile_pic');
                $imageName = time() . '_' . uniqid() . '.' . $profile_pic->getClientOriginalExtension();
                $profile_pic->move(public_path('uploads/users'), $imageName);
                $data['profile_pic'] = 'uploads/users/' . $imageName;
            } else {
                $data['profile_pic'] = null;
            }

            // Default status enabled
            $data['status'] = $data['status'] ?? 1;

            // Create user
            $user = User::create($data);

            // Sync multi-company access
            if (!empty($companies)) {
                $user->companies()->sync($companies);
            }

            // Log this creation
            UserLog::create([
                'user_id' => $user->id,
                'performed_by' => auth('web')->id() ?? null, // null = system created
                'action' => 'create',
                'changed_fields' => null,
                'created_at' => now(),
            ]);

            // Response for AJAX or JSON
            return response()->json([
                'success' => ['User created successfully!'],
                'user' => $user,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => ['Failed to create user. Please try again. ' . $th->getMessage()],
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'error' => ['User not found.'],
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => ['User deleted successfully!'],
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                'error' => ['Failed to delete user. Please try again.'],
            ], 500);
        }
    }

    public function exportPdf()
    {
        $users = User::latest()->get();
        $pdf = Pdf::loadView('pdf.users', compact('users'));

        return $pdf->download('users.pdf');
    }
}
