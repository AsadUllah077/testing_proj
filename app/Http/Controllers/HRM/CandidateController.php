<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\HrmCandidate;
use App\Models\HrmJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CandidateController extends Controller
{
    /**
     * Display a listing of candidates.
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // Get search parameters
            $search = $request->input('q');
            $jobId = $request->input('job_id');
            $stage = $request->input('stage');
            $source = $request->input('source');
            $perPage = $request->input('per_page', 20);

            // Start query
            $query = HrmCandidate::with(['job', 'addedBy'])
                ->where('company_id', $companyId)
                ->latest('applied_date');

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            // Apply job filter
            if (! empty($jobId)) {
                $query->where('hrm_job_id', $jobId);
            }

            // Apply stage filter
            if (! empty($stage)) {
                $query->where('stage', $stage);
            }

            // Apply source filter
            if (! empty($source)) {
                $query->where('source', $source);
            }

            // Get jobs for filter dropdown
            $jobs = HrmJob::where('company_id', $companyId)
                ->orderBy('job_title')
                ->get();

            // Get candidate statistics
            $totalCandidates = HrmCandidate::where('company_id', $companyId)->count();
            $newCandidates = HrmCandidate::where('company_id', $companyId)
                ->where('stage', 'new')
                ->count();
            $interviewingCandidates = HrmCandidate::where('company_id', $companyId)
                ->where('stage', 'interviewing')
                ->count();

            // Get stage distribution
            $stageStats = DB::table('hrm_candidates')
                ->select('stage', DB::raw('COUNT(*) as count'))
                ->where('company_id', $companyId)
                ->groupBy('stage')
                ->get()
                ->keyBy('stage');

            // Paginate results
            $candidates = $query->paginate($perPage)->withQueryString();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.candidates.partials.table', compact('candidates'))->render(),
                    'pagination' => $candidates->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.candidates.index', compact(
                'candidates',
                'jobs',
                'totalCandidates',
                'newCandidates',
                'interviewingCandidates',
                'stageStats'
            ));

        } catch (\Exception $e) {
            Log::error('Error fetching candidates: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error fetching candidates: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new candidate.
     */
    public function create()
    {
        try {
            $companyId = Auth::user()->company_id;

            $jobs = HrmJob::where('company_id', $companyId)
                ->where('status', 'open')
                ->orderBy('job_title')
                ->get();

            return view('pages.hrm.candidates.create', compact('jobs'));

        } catch (\Exception $e) {
            Log::error('Error loading candidate create form: '.$e->getMessage());

            return redirect()->route('hrm.candidates.index')->with('error', 'Error loading form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created candidate in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:hrm_candidates,email',
                'phone' => 'nullable|string|max:20',
                'hrm_job_id' => 'nullable|exists:hrm_jobs,id',
                'current_role' => 'nullable|string|max:255',
                'source' => 'nullable|in:'.implode(',', array_keys(HrmCandidate::getSourceOptions())),
                'stage' => 'required|in:'.implode(',', array_keys(HrmCandidate::getStageOptions())),
                'applied_date' => 'nullable|date',
                'resume' => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
                'cover_letter' => 'nullable|string',
                'rating' => 'nullable|numeric|min:0|max:5',
                'notes' => 'nullable|string',
                // Interview fields - required if stage is interviewing
                'interview_date' => 'nullable|date',
                'interview_time' => 'nullable|date_format:H:i',
                'interview_location' => 'nullable|string|max:255',
                'interviewer' => 'nullable|string|max:255',
                'interview_notes' => 'nullable|string',
            ]);

            // Add custom validation for interview fields
            $validator->sometimes('interview_date', 'required|date', function ($input) {
                return $input->stage == 'interviewing';
            });

            $validator->sometimes('interview_time', 'required|date_format:H:i', function ($input) {
                return $input->stage == 'interviewing';
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle resume upload
            $resumePath = null;
            $resumeOriginalName = null;
            if ($request->hasFile('resume')) {
                $file = $request->file('resume');
                $resumeOriginalName = $file->getClientOriginalName();
                $resumePath = $file->store('candidates/resumes', 'public');
            }

            // Create candidate
            $candidate = HrmCandidate::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'hrm_job_id' => $request->hrm_job_id,
                'current_role' => $request->current_role,
                'source' => $request->source,
                'stage' => $request->stage,
                'applied_date' => $request->applied_date ?? now(),
                'resume_path' => $resumePath,
                'resume_original_name' => $resumeOriginalName,
                'cover_letter' => $request->cover_letter,
                'rating' => $request->rating,
                'notes' => $request->notes,
                // Interview fields
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'interview_location' => $request->interview_location,
                'interviewer' => $request->interviewer,
                'interview_notes' => $request->interview_notes,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Candidate added successfully!',
                'data' => $candidate,
                'redirect' => route('hrm.candidates.index'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating candidate: '.$e->getMessage());
            Log::error('Error trace:', ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating candidate: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified candidate.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::with(['job', 'addedBy'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            return view('pages.hrm.candidates.show', compact('candidate'));

        } catch (\Exception $e) {
            Log::error('Error fetching candidate: '.$e->getMessage());

            return redirect()->route('hrm.candidates.index')->with('error', 'Candidate not found.');
        }
    }

    /**
     * Show the form for editing the specified candidate.
     */
    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $candidate = HrmCandidate::with(['job'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $jobs = HrmJob::where('company_id', $companyId)
                ->where('status', 'open')
                ->orderBy('job_title')
                ->get();

            return view('pages.hrm.candidates.edit', compact('candidate', 'jobs'));

        } catch (\Exception $e) {
            Log::error('Error loading candidate edit form: '.$e->getMessage());

            return redirect()->route('hrm.candidates.index')->with('error', 'Candidate not found.');
        }
    }

    /**
     * Update the specified candidate in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:hrm_candidates,email,'.$id,
                'phone' => 'nullable|string|max:20',
                'hrm_job_id' => 'nullable|exists:hrm_jobs,id',
                'current_role' => 'nullable|string|max:255',
                'source' => 'nullable|in:'.implode(',', array_keys(HrmCandidate::getSourceOptions())),
                'stage' => 'required|in:'.implode(',', array_keys(HrmCandidate::getStageOptions())),
                'applied_date' => 'nullable|date',
                'resume' => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
                'cover_letter' => 'nullable|string',
                'rating' => 'nullable|numeric|min:0|max:5',
                'notes' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'remove_resume' => 'nullable|boolean',
                // Interview fields
                'interview_date' => 'nullable|date',
                'interview_time' => 'nullable|date_format:H:i',
                'interview_location' => 'nullable|string|max:255',
                'interviewer' => 'nullable|string|max:255',
                'interview_notes' => 'nullable|string',
            ]);

            // Add custom validation for interview fields
            $validator->sometimes('interview_date', 'required|date', function ($input) {
                return $input->stage == 'interviewing';
            });

            $validator->sometimes('interview_time', 'required|date_format:H:i', function ($input) {
                return $input->stage == 'interviewing';
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle resume upload
            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'hrm_job_id' => $request->hrm_job_id,
                'current_role' => $request->current_role,
                'source' => $request->source,
                'stage' => $request->stage,
                'applied_date' => $request->applied_date,
                'cover_letter' => $request->cover_letter,
                'rating' => $request->rating,
                'notes' => $request->notes,
                'is_active' => $request->is_active ?? $candidate->is_active,
                // Interview fields
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'interview_location' => $request->interview_location,
                'interviewer' => $request->interviewer,
                'interview_notes' => $request->interview_notes,
            ];

            // Handle resume removal
            if ($request->has('remove_resume') && $request->remove_resume) {
                if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
                    Storage::disk('public')->delete($candidate->resume_path);
                }
                $updateData['resume_path'] = null;
                $updateData['resume_original_name'] = null;
            }

            // Handle new resume upload
            if ($request->hasFile('resume')) {
                // Delete old resume if exists
                if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
                    Storage::disk('public')->delete($candidate->resume_path);
                }

                $file = $request->file('resume');
                $updateData['resume_path'] = $file->store('candidates/resumes', 'public');
                $updateData['resume_original_name'] = $file->getClientOriginalName();
            }

            // Clear interview fields if stage is not interviewing
            if ($request->stage != 'interviewing') {
                $updateData['interview_date'] = null;
                $updateData['interview_time'] = null;
                $updateData['interview_location'] = null;
                $updateData['interviewer'] = null;
                $updateData['interview_notes'] = null;
            }

            $candidate->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Candidate updated successfully!',
                'data' => $candidate,
                'redirect' => route('hrm.candidates.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating candidate: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating candidate: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified candidate from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::where('company_id', $companyId)
                ->findOrFail($id);

            // Delete resume file if exists from public disk
            if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
                Storage::disk('public')->delete($candidate->resume_path);
                Log::info('Resume deleted:', ['path' => $candidate->resume_path]);
            }

            // Delete the candidate
            $candidate->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Candidate deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting candidate: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting candidate: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update candidate stage.
     */
    public function updateStage(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'stage' => 'required|in:'.implode(',', array_keys(HrmCandidate::getStageOptions())),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $candidate->update(['stage' => $request->stage]);

            return response()->json([
                'success' => true,
                'message' => 'Candidate stage updated successfully!',
                'data' => $candidate,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating candidate stage: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating candidate stage: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download candidate resume.
     */
    public function downloadResume($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::where('company_id', $companyId)
                ->findOrFail($id);

            if (! $candidate->resume_path || ! Storage::disk('public')->exists($candidate->resume_path)) {
                Log::error('Resume not found:', [
                    'candidate_id' => $id,
                    'resume_path' => $candidate->resume_path,
                    'exists' => $candidate->resume_path ? Storage::disk('public')->exists($candidate->resume_path) : 'no path',
                ]);

                return redirect()->back()->with('error', 'Resume file not found.');
            }

            $headers = [
                'Content-Type' => 'application/octet-stream',
            ];

            return Storage::disk('public')->download(
                $candidate->resume_path,
                $candidate->resume_original_name ?? 'resume_'.$candidate->id.'.'.pathinfo($candidate->resume_path, PATHINFO_EXTENSION),
                $headers
            );

        } catch (\Exception $e) {
            Log::error('Error downloading resume: '.$e->getMessage());
            Log::error('Error details:', [
                'candidate_id' => $id,
                'resume_path' => $candidate->resume_path ?? 'null',
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error downloading resume: '.$e->getMessage());
        }
    }

    /**
     * Get candidates by job ID.
     */
    public function getByJob($jobId)
    {
        try {
            $companyId = Auth::user()->company_id;

            $candidates = HrmCandidate::where('company_id', $companyId)
                ->where('hrm_job_id', $jobId)
                ->with('addedBy')
                ->latest('applied_date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $candidates,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching candidates by job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching candidates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export candidates to CSV.
     */
    public function export(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $query = HrmCandidate::with(['job', 'addedBy'])
                ->where('company_id', $companyId);

            // Apply filters
            if ($request->has('job_id') && $request->job_id) {
                $query->where('hrm_job_id', $request->job_id);
            }

            if ($request->has('stage') && $request->stage) {
                $query->where('stage', $request->stage);
            }

            $candidates = $query->get();

            $filename = 'candidates_export_'.date('Y-m-d_H-i-s').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($candidates) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Add headers
                fputcsv($file, [
                    'Name',
                    'Email',
                    'Phone',
                    'Job Title',
                    'Current Role',
                    'Source',
                    'Stage',
                    'Applied Date',
                    'Rating',
                    'Added By',
                    'Notes',
                ]);

                // Add data
                foreach ($candidates as $candidate) {
                    fputcsv($file, [
                        $candidate->full_name,
                        $candidate->email,
                        $candidate->phone,
                        $candidate->job->job_title ?? 'N/A',
                        $candidate->current_role,
                        $candidate->source,
                        $candidate->stage,
                        $candidate->applied_date ? $candidate->applied_date->format('Y-m-d') : 'N/A',
                        $candidate->rating,
                        $candidate->addedBy->name ?? 'N/A',
                        substr($candidate->notes ?? '', 0, 100),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting candidates: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error exporting candidates.');
        }
    }

    /**
     * Display interview calendar.
     */
    public function calendar(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Get jobs for filters
        $jobs = HrmJob::where('company_id', $companyId)
            ->where('status', 'open')
            ->orderBy('job_title')
            ->get();

        return view('pages.hrm.candidates.calendar', compact('jobs'));
    }

    /**
     * Get interview calendar data.
     */
    public function getCalendarData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));

            // Calculate start and end dates for the month
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            // Query candidates with interviews scheduled
            $query = HrmCandidate::with([
                'job' => function ($q) {
                    $q->withTrashed();
                },
                'addedBy',
            ])
                ->where('company_id', $companyId)
                ->where('stage', 'interviewing') // Only candidates in interviewing stage
                ->whereNotNull('interview_date') // Only those with interview date set
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('interview_date', [$startDate, $endDate]);
                });

            // Filter by job
            if ($request->filled('job_id') && $request->job_id != '') {
                $query->where('hrm_job_id', $request->job_id);
            }

            // Filter by interviewer
            if ($request->filled('interviewer') && $request->interviewer != '') {
                $query->where('interviewer', 'LIKE', '%'.$request->interviewer.'%');
            }

            $candidates = $query->orderBy('interview_date')
                ->orderBy('interview_time')
                ->get();

            // Format the response
            $formattedCandidates = $candidates->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'candidate' => [
                        'full_name' => $candidate->full_name,
                        'first_name' => $candidate->first_name,
                        'last_name' => $candidate->last_name,
                        'email' => $candidate->email,
                        'phone' => $candidate->phone,
                        'current_role' => $candidate->current_role,
                    ],
                    'job' => [
                        'id' => $candidate->job->id ?? 0,
                        'job_title' => $candidate->job->job_title ?? 'No Job Assigned',
                        'job_id' => $candidate->job->job_id ?? 'N/A',
                    ],
                    'interview_date' => $candidate->interview_date->format('Y-m-d'),
                    'interview_time' => $candidate->interview_time,
                    'formatted_interview_time' => $candidate->formatted_interview_time,
                    'interview_location' => $candidate->interview_location,
                    'interviewer' => $candidate->interviewer,
                    'interview_notes' => $candidate->interview_notes,
                    'stage' => $candidate->stage,
                    'applied_date' => $candidate->applied_date ? $candidate->applied_date->format('Y-m-d') : null,
                    'added_by' => $candidate->addedBy->name ?? 'N/A',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedCandidates,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching interview calendar data: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching interview calendar data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get interview details.
     */
    public function getInterviewDetails(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $candidate = HrmCandidate::with(['job', 'addedBy'])
                ->where('company_id', $companyId)
                ->findOrFail($request->id);

            // Generate resume URL if exists
            $resumeUrl = null;
            if ($candidate->resume_path && Storage::disk('public')->exists($candidate->resume_path)) {
                $resumeUrl = Storage::url($candidate->resume_path);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $candidate->id,
                    'candidate' => [
                        'full_name' => $candidate->full_name,
                        'first_name' => $candidate->first_name,
                        'last_name' => $candidate->last_name,
                        'email' => $candidate->email,
                        'phone' => $candidate->phone,
                        'current_role' => $candidate->current_role,
                        'source' => $candidate->source,
                        'stage' => $candidate->stage,
                        'applied_date' => $candidate->applied_date ? $candidate->applied_date->format('Y-m-d') : null,
                        'rating' => $candidate->rating,
                        'notes' => $candidate->notes,
                        'added_by' => $candidate->addedBy->name ?? 'N/A',
                    ],
                    'job' => [
                        'id' => $candidate->job->id ?? 0,
                        'job_title' => $candidate->job->job_title ?? 'No Job Assigned',
                        'job_id' => $candidate->job->job_id ?? 'N/A',
                        'location' => $candidate->job->location ?? 'N/A',
                    ],
                    'interview_details' => [
                        'interview_date' => $candidate->interview_date ? $candidate->interview_date->format('Y-m-d') : null,
                        'interview_time' => $candidate->interview_time,
                        'formatted_interview_time' => $candidate->formatted_interview_time,
                        'interview_location' => $candidate->interview_location,
                        'interviewer' => $candidate->interviewer,
                        'interview_notes' => $candidate->interview_notes,
                    ],
                    'resume' => [
                        'path' => $candidate->resume_path,
                        'original_name' => $candidate->resume_original_name,
                        'url' => $resumeUrl,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching interview details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching interview details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update interview details.
     */
    public function updateInterviewDetails(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $candidate = HrmCandidate::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'interview_date' => 'nullable|date',
                'interview_time' => 'nullable|date_format:H:i',
                'interview_location' => 'nullable|string|max:255',
                'interviewer' => 'nullable|string|max:255',
                'interview_notes' => 'nullable|string',
                'stage' => 'nullable|in:'.implode(',', array_keys(HrmCandidate::getStageOptions())),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = [
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'interview_location' => $request->interview_location,
                'interviewer' => $request->interviewer,
                'interview_notes' => $request->interview_notes,
            ];

            // If stage is provided, update it too
            if ($request->has('stage')) {
                $updateData['stage'] = $request->stage;
            }

            $candidate->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Interview details updated successfully!',
                'data' => $candidate,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating interview details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating interview details: '.$e->getMessage(),
            ], 500);
        }
    }
}
