<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PolicyController extends Controller
{
    /**
     * Display a listing of the policies.
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $search = $request->input('q');
            $perPage = $request->input('per_page', 20);

            $query = Policy::where('company_id', $companyId)->latest();

            if (!empty($search)) {
                $query->where('title', 'LIKE', "%{$search}%");
            }

            $policies = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.policies.partials.table', compact('policies'))->render(),
                    'pagination' => $policies->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.policies.index', compact('policies'));
        } catch (\Exception $e) {
            Log::error('Error fetching policies: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching policies: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new policy.
     */
    public function create()
    {
        return view('pages.hrm.policies.create');
    }

    /**
     * Store a newly created policy in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('policies', 'public');
            }

            $policy = Policy::create([
                'company_id' => Auth::user()->company_id,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Policy created successfully!',
                    'data' => $policy,
                ], 201);
            }

            return redirect()->route('hrm.policies.index')->with('success', 'Policy created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating policy: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating policy: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error creating policy: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified policy.
     */
    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $policy = Policy::where('company_id', $companyId)->findOrFail($id);
            return view('pages.hrm.policies.edit', compact('policy'));
        } catch (\Exception $e) {
            return redirect()->route('hrm.policies.index')->with('error', 'Policy not found.');
        }
    }

    /**
     * Update the specified policy in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $policy = Policy::where('company_id', $companyId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $filePath = $policy->file_path;
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($policy->file_path && Storage::disk('public')->exists($policy->file_path)) {
                    Storage::disk('public')->delete($policy->file_path);
                }
                $filePath = $request->file('file')->store('policies', 'public');
            }

            $policy->update([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Policy updated successfully!',
                    'data' => $policy,
                ]);
            }

            return redirect()->route('hrm.policies.index')->with('success', 'Policy updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating policy: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating policy: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating policy: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified policy from storage.
     */
    public function destroy($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $policy = Policy::where('company_id', $companyId)->findOrFail($id);

            if ($policy->file_path && Storage::disk('public')->exists($policy->file_path)) {
                Storage::disk('public')->delete($policy->file_path);
            }

            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting policy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting policy: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download the policy file.
     */
    public function download($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $policy = Policy::where('company_id', $companyId)->findOrFail($id);

            if (!$policy->file_path || !Storage::disk('public')->exists($policy->file_path)) {
                return redirect()->back()->with('error', 'File not found on server.');
            }

            return Storage::disk('public')->download($policy->file_path);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error downloading file: ' . $e->getMessage());
        }
    }
}
