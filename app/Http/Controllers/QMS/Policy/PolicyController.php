<?php

namespace App\Http\Controllers\QMS\Policy;

use App\Http\Controllers\Controller;
use App\Models\QmsPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\QmsPolicyAttachment;
use App\Models\QmsPolicyApproval;
use App\Models\Department;
use Illuminate\Support\Facades\Validator;

class PolicyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search   = $request->input('q');
            $status   = $request->input('status');
            $priority = $request->input('priority');
            $fromDate = $request->input('from_date');
            $toDate   = $request->input('to_date');
            $perPage  = $request->input('per_page', 20);

            $query = QmsPolicy::with('creator')
                ->where('company_id', Auth::user()->company_id)
                ->latest();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('policy_number', 'LIKE', "%{$search}%")
                      ->orWhere('department', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($priority)) {
                $query->where('priority', $priority);
            }

            if (!empty($fromDate)) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $policies = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            $stats = [
                'active'       => QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'active')->count(),
                'under_review' => QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'under_review')->count(),
                'draft'        => QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'draft')->count(),
                'archived'     => QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'archived')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html'       => view('pages.qms.policy.partials.table', compact('policies'))->render(),
                    'pagination' => $policies->links()->toHtml(),
                ]);
            }

            return view('pages.qms.policy.index', compact('policies', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching QMS policies: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching policies: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $departments = Department::where('company_id', Auth::user()->company_id)->orderBy('department')->get();
        return view('pages.qms.policy.create', compact('departments'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'          => 'required|string|max:255',
                'content'        => 'nullable|string',
                'status'         => 'required|in:draft,active,under_review,pending_approval,archived',
                'priority'       => 'required|in:low,medium,high',
                'department'     => 'nullable|string|max:255',
                'classification' => 'nullable|string|max:255',
            ], [
                'title.required'    => 'Policy title is required.',
                'status.required'   => 'Status is required.',
                'priority.required' => 'Priority is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $submittedAt = null;
            $submittedBy = null;
            if ($request->status === 'active' || $request->status === 'under_review') {
                $submittedAt = now();
                $submittedBy = Auth::id();
            }

            $policy = QmsPolicy::create([
                'title'          => $request->title,
                'content'        => $request->input('content'),
                'status'         => $request->status,
                'priority'       => $request->priority,
                'department'     => $request->department,
                'classification' => $request->classification,
                'category'       => $request->category,
                'version'        => '1.0',
                'company_id'     => Auth::user()->company_id,
                'created_by'     => Auth::id(),
                'submitted_at'   => $submittedAt,
                'submitted_by'   => $submittedBy,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('qms/policies', 'public');
                    QmsPolicyAttachment::create([
                        'policy_id'  => $policy->id,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_path'  => $path,
                        'file_size'  => $file->getSize(),
                        'mime_type'  => $file->getMimeType(),
                        'company_id' => Auth::user()->company_id,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Policy created successfully!',
                'data'    => $policy,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating policy: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $policy = QmsPolicy::with(['creator', 'submitter', 'approver', 'reviewer', 'attachments.creator', 'approvalHistory.approver'])
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($id);

            $approvalHistory = $policy->approvalHistory;
            $isRevision      = (int) explode('.', $policy->version ?: '1.0')[0] > 1;

            // Fetch dynamic history/logs
            $logs = \App\Models\ActivityLog::with('user')
                ->where('loggable_type', get_class($policy))
                ->where('loggable_id', $policy->id)
                ->latest('created_at')
                ->get();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'    => $policy,
                    'logs'    => $logs,
                ]);
            }

            return view('pages.qms.policy.show', compact('policy', 'logs', 'approvalHistory', 'isRevision'));

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found.',
                ], 404);
            }
            return redirect()->route('qms.policy.index')->with('error', 'Policy not found.');
        }
    }

    public function edit($id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $departments = Department::where('company_id', Auth::user()->company_id)->orderBy('department')->get();
            return view('pages.qms.policy.edit', compact('policy', 'departments'));

        } catch (\Exception $e) {
            return redirect()->route('qms.policy.index')->with('error', 'Policy not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'          => 'required|string|max:255',
                'content'        => 'nullable|string',
                'status'         => 'required|in:draft,active,under_review,pending_approval,archived',
                'priority'       => 'required|in:low,medium,high',
                'department'     => 'nullable|string|max:255',
                'classification' => 'nullable|string|max:255',
                'category'       => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Versioning: increment major version only when content changes
            $currentVersion = $policy->version ?: '1.0';
            $contentChanged = $policy->content !== $request->input('content');
            if ($contentChanged) {
                $major = (int) explode('.', $currentVersion)[0];
                $currentVersion = ($major + 1) . '.0';
            }

            // If an active policy's content changes, start a new review cycle
            $isRevision = $contentChanged && $policy->status === 'active';

            if ($isRevision) {
                $updateData = [
                    'title'           => $request->title,
                    'content'         => $request->input('content'),
                    'status'          => 'pending_approval',
                    'priority'        => $request->priority,
                    'department'      => $request->department,
                    'category'        => $request->category,
                    'version'         => $currentVersion,
                    'submitted_at'    => now(),
                    'submitted_by'    => Auth::id(),
                    'reviewed_at'     => null,
                    'reviewer_id'     => null,
                    'approved_at'     => null,
                    'approved_by'     => null,
                    'rejection_notes' => null,
                ];
            } else {
                $submittedAt = $policy->submitted_at;
                $submittedBy = $policy->submitted_by;
                if (is_null($submittedAt) && in_array($request->status, ['active', 'under_review'])) {
                    $submittedAt = now();
                    $submittedBy = Auth::id();
                }

                $approvedAt = $policy->approved_at;
                $approvedBy = $policy->approved_by;
                if (is_null($approvedAt) && $request->status === 'active') {
                    $approvedAt = now();
                    $approvedBy = Auth::id();
                }

                $updateData = [
                    'title'          => $request->title,
                    'content'        => $request->input('content'),
                    'status'         => $request->status,
                    'priority'       => $request->priority,
                    'department'     => $request->department,
                    'category'       => $request->category,
                    'version'        => $currentVersion,
                    'submitted_at'   => $submittedAt,
                    'submitted_by'   => $submittedBy,
                    'approved_at'    => $approvedAt,
                    'approved_by'    => $approvedBy,
                ];
            }

            $policy->update($updateData);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('qms/policies', 'public');
                    QmsPolicyAttachment::create([
                        'policy_id'  => $policy->id,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_path'  => $path,
                        'file_size'  => $file->getSize(),
                        'mime_type'  => $file->getMimeType(),
                        'company_id' => Auth::user()->company_id,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Policy updated successfully!',
                'data'    => $policy,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating policy: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting policy: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submitForReview($id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)
                ->where('status', 'draft')
                ->findOrFail($id);

            $policy->update([
                'status'       => 'under_review',
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Policy submitted for QA review.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function qaApprove($id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)
                ->where('status', 'under_review')
                ->findOrFail($id);

            $policy->update([
                'status'          => 'pending_approval',
                'reviewed_at'     => now(),
                'reviewer_id'     => Auth::id(),
                'rejection_notes' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'QA review approved. Awaiting final approval.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function qaReject(Request $request, $id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)
                ->where('status', 'under_review')
                ->findOrFail($id);

            $policy->update([
                'status'          => 'draft',
                'rejection_notes' => $request->input('rejection_notes'),
                'submitted_at'    => null,
                'submitted_by'    => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Policy returned to author for revision.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finalApprove($id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)
                ->where('status', 'pending_approval')
                ->findOrFail($id);

            $now = now();

            $policy->update([
                'status'          => 'active',
                'approved_at'     => $now,
                'approved_by'     => Auth::id(),
                'rejection_notes' => null,
            ]);

            // Record approval in history
            QmsPolicyApproval::create([
                'policy_id'   => $policy->id,
                'version'     => $policy->version,
                'approved_by' => Auth::id(),
                'approved_at' => $now,
            ]);

            return response()->json(['success' => true, 'message' => 'Policy has been finally approved and is now active.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finalReject(Request $request, $id)
    {
        try {
            $policy = QmsPolicy::where('company_id', Auth::user()->company_id)
                ->where('status', 'pending_approval')
                ->findOrFail($id);

            $policy->update([
                'status'          => 'draft',
                'rejection_notes' => $request->input('rejection_notes'),
                'submitted_at'    => null,
                'submitted_by'    => null,
                'reviewed_at'     => null,
                'reviewer_id'     => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Policy returned to author for revision.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteAttachment($id)
    {
        try {
            $attachment = QmsPolicyAttachment::where('company_id', Auth::user()->company_id)->findOrFail($id);
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attachment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
