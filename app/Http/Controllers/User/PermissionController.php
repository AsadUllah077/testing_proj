<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // Query permissions with search and pagination
            $query = Permission::where(function ($query) {});

            // Search filter
            if ($request->has('q') && $request->q != '') {
                $query->where('name', 'like', '%' . $request->q . '%');
            }

            // Date filters
            if ($request->has('from_date') && $request->from_date != '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date != '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Get paginated results
            $permissions = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.users.permission.table', compact('permissions'))->render(),
                    'pagination' => $permissions->appends(request()->query())->links()->toHtml(),
                ]);
            }

            return view('pages.users.permission.index', compact('permissions'));
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error fetching permissions: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        return view('pages.users.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
            ], [
                'name.required' => 'Permission name is required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permission = Permission::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully!',
                'data' => $permission,
                'redirect' => route('users.permission.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $permission = Permission::where('id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {

            $permission = Permission::where('id', $id)
                ->firstOrFail();

            return view('pages.users.permission.edit', compact('permission'));
        } catch (\Exception $e) {
            return redirect()->route('users.permission.index')
                ->with('error', 'Permission not found or cannot be edited');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {

            $permission = Permission::where('id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permission->update([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully!',
                'data' => $permission,
                'redirect' => route('users.permission.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $permission = Permission::where('id', $id)
                ->firstOrFail();



            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get permission modules for dropdown.
     */
    public function getModules()
    {
        try {
            $companyId = Auth::user()->company_id;

            $modules = Permission::where('company_id', $companyId)
                ->orWhereNull('company_id')
                ->distinct('module')
                ->pluck('module')
                ->filter()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $modules,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching modules',
            ], 500);
        }
    }
}
