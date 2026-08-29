<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // Query roles with search and pagination
            $query = Role::where(function ($query) {});

            // Search filter
            if ($request->has('q') && $request->q != '') {
                $query->where('name', 'ILIKE', '%' . $request->q . '%');
            }

            // Date filters
            if ($request->has('from_date') && $request->from_date != '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date != '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Get paginated results
            $roles = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.users.role.table', compact('roles'))->render(),
                    'pagination' => $roles->appends(request()->query())->links()->toHtml(),
                ]);
            }

            return view('pages.users.role.index', compact('roles'));
        } catch (\Exception $e) {
            Log::error('Error fetching roles: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error fetching roles: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.users.role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:100|unique:roles,name',
                ],
                [
                    'name.required' => 'Role name is required',
                    'name.unique' => 'A role with this name already exists',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $role = Role::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully!',
                'data' => $role,
                'redirect' => route('users.role.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating role',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $role = Role::where('id', $id)
                ->where(function ($query) {})
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $role,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $role = Role::where('id', $id)

                ->firstOrFail();

            return view('pages.users.role.edit', compact('role'));
        } catch (\Exception $e) {
            return redirect()->route('users.role.index')
                ->with('error', 'Role not found or cannot be edited');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        try {

            $role = Role::where('id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:roles,name,' . $id . ',id',
            ], [
                'name.required' => 'Role name is required',
                'name.unique' => 'A role with this name already exists for your company',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $role->update([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully!',
                'data' => $role,
                'redirect' => route('users.role.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $role = Role::where('id', $id)
                ->firstOrFail();

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting role: ' . $e->getMessage(),
            ], 500);
        }
    }
}
