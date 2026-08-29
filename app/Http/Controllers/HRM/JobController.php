<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrmCandidate;
use App\Models\HrmJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * Display a listing of jobs.
     */
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // Get search parameters
            $search = $request->input('q');
            $departmentId = $request->input('department_id');
            $status = $request->input('status');
            $employmentType = $request->input('employment_type');
            $experienceLevel = $request->input('experience_level');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Start query
            $query = HrmJob::with(['department', 'hiringManager', 'createdBy'])
                ->withCount('candidates')
                ->where('company_id', $companyId)
                ->latest('created_at');

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('job_title', 'LIKE', "%{$search}%")
                        ->orWhere('job_id', 'LIKE', "%{$search}%")
                        ->orWhere('location', 'LIKE', "%{$search}%");
                });
            }

            // Apply department filter
            if (! empty($departmentId)) {
                $query->where('department_id', $departmentId);
            }

            // Apply status filter
            if (! empty($status)) {
                $query->where('status', $status);
            }

            // Apply employment type filter
            if (! empty($employmentType)) {
                $query->where('employment_type', $employmentType);
            }

            // Apply experience level filter
            if (! empty($experienceLevel)) {
                $query->where('experience_level', $experienceLevel);
            }

            if (! empty($fromDate)) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if (! empty($toDate)) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Paginate results
            $jobs = $query->paginate($perPage)->withQueryString();

            // Get dropdown data
            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $hiringManagers = User::where('company_id', $companyId)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'like', '%manager%');
                })
                ->orderBy('first_name')
                ->get();

            $statusOptions = HrmJob::getStatusOptions();
            $employmentTypeOptions = HrmJob::getEmploymentTypeOptions();
            $experienceLevelOptions = HrmJob::getExperienceLevelOptions();

            // Get statistics
            $totalJobs = HrmJob::where('company_id', $companyId)->count();
            $activeJobs = HrmJob::where('company_id', $companyId)->active()->count();
            $closedJobs = HrmJob::where('company_id', $companyId)->closed()->count();

            // Fix: Join with hrm_jobs table
            $totalApplicants = HrmCandidate::count();

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.hrm.jobs.partials.table', compact('jobs'))->render(),
                    'pagination' => $jobs->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.jobs.index', compact(
                'jobs',
                'departments',
                'hiringManagers',
                'statusOptions',
                'employmentTypeOptions',
                'experienceLevelOptions',
                'totalJobs',
                'activeJobs',
                'closedJobs',
                'totalApplicants'
            ));

        } catch (\Exception $e) {
            Log::error('Error fetching jobs: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error fetching jobs: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new job.
     */
    public function create()
    {
        try {
            $companyId = Auth::user()->company_id;

            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $hiringManagers = User::where('company_id', $companyId)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'like', '%manager%');
                })
                ->orderBy('first_name')
                ->get();

            // Get predefined skills from configuration or database
            $predefinedSkills = config('hrm.predefined_skills', [
                'Figma', 'UI Design', 'Prototyping', 'React', 'Vue.js',
                'Node.js', 'PHP', 'Laravel', 'JavaScript', 'TypeScript',
                'HTML/CSS', 'Tailwind CSS', 'Bootstrap', 'Git', 'REST API',
                'MySQL', 'PostgreSQL', 'MongoDB', 'AWS', 'Docker',
            ]);

            return view('pages.hrm.jobs.create', compact(
                'departments',
                'hiringManagers',
                'predefinedSkills'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading job create form: '.$e->getMessage());

            return redirect()->route('hrm.jobs.index')->with('error', 'Error loading form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created job in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'job_title' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
                'employment_type' => 'required|in:'.implode(',', array_keys(HrmJob::getEmploymentTypeOptions())),
                'experience_level' => 'required|in:'.implode(',', array_keys(HrmJob::getExperienceLevelOptions())),
                'location' => 'required|string|max:255',
                'job_description' => 'required|string',
                'requirements' => 'nullable|string',
                'skills' => 'nullable|json', // Change from array to json validation
                'hiring_manager_id' => 'nullable|exists:users,id',
                'status' => 'required|in:'.implode(',', array_keys(HrmJob::getStatusOptions())),
                'posted_date' => 'nullable|date',
                'closing_date' => 'nullable|date|after:posted_date',
            ], [
                'job_title.required' => 'Job title is required.',
                'department_id.required' => 'Department is required.',
                'location.required' => 'Location is required.',
                'job_description.required' => 'Job description is required.',
                'closing_date.after' => 'Closing date must be after posted date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Generate job ID
            $jobId = HrmJob::generateJobId();

            // Process skills - decode the JSON string
            $skills = null;
            if ($request->has('skills') && $request->skills !== '[]' && $request->skills !== 'null') {
                $skillsArray = json_decode($request->skills, true);
                if (is_array($skillsArray)) {
                    $skillsArray = array_map('trim', $skillsArray);
                    $skillsArray = array_filter($skillsArray);
                    $skillsArray = array_unique($skillsArray);
                    $skills = json_encode($skillsArray);
                }
            }

            // Create job
            $job = HrmJob::create([
                'job_id' => $jobId,
                'job_title' => $request->job_title,
                'department_id' => $request->department_id,
                'employment_type' => $request->employment_type,
                'experience_level' => $request->experience_level,
                'location' => $request->location,
                'job_description' => $request->job_description,
                'requirements' => $request->requirements,
                'skills' => $skills, // Store as JSON string
                'hiring_manager_id' => $request->hiring_manager_id,
                'status' => $request->status,
                'posted_date' => $request->posted_date ?: ($request->status === 'open' ? Carbon::now() : null),
                'closing_date' => $request->closing_date,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job posted successfully!',
                'data' => $job,
                'redirect' => route('hrm.jobs.index'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified job.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            // Eager load with optimized queries
            $job = HrmJob::with([
                'department:id,department',
                'hiringManager:id,first_name,last_name',
                'createdBy:id,first_name,last_name',
                'candidates' => function ($query) {
                    $query->with('addedBy:id,first_name,last_name')
                        ->latest('applied_date');
                },
            ])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            // Get application statistics - using 'new' instead of 'applied' if that's what your model uses
            $applicationStats = [
                'applied' => $job->candidates->where('stage', 'applied')->count() ?: $job->candidates->where('stage', 'new')->count(),
                'reviewed' => $job->candidates->where('stage', 'reviewed')->count() ?: $job->candidates->where('stage', 'screening')->count(),
                'interviewed' => $job->candidates->where('stage', 'interviewed')->count(),
                'hired' => $job->candidates->where('stage', 'hired')->count(),
                'rejected' => $job->candidates->where('stage', 'rejected')->count(),
            ];

            return view('pages.hrm.jobs.show', compact('job', 'applicationStats'));

        } catch (\Exception $e) {
            Log::error('Error fetching job: '.$e->getMessage());

            return redirect()->route('hrm.jobs.index')->with('error', 'Job not found.');
        }
    }

    /**
     * Show the form for editing the specified job.
     */
    public function edit($id)
    {
        // dd($id);
        try {
            $companyId = Auth::user()->company_id;

            $job = HrmJob::with(['department', 'hiringManager'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $hiringManagers = User::where('company_id', $companyId)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'like', '%manager%');
                })
                ->orderBy('first_name')
                ->get();

            $predefinedSkills = config('hrm.predefined_skills', [
                'Figma', 'UI Design', 'Prototyping', 'React', 'Vue.js',
                'Node.js', 'PHP', 'Laravel', 'JavaScript', 'TypeScript',
                'HTML/CSS', 'Tailwind CSS', 'Bootstrap', 'Git', 'REST API',
                'MySQL', 'PostgreSQL', 'MongoDB', 'AWS', 'Docker',
            ]);

            return view('pages.hrm.jobs.edit', compact(
                'job',
                'departments',
                'hiringManagers',
                'predefinedSkills'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading job edit form: '.$e->getMessage());

            return redirect()->route('hrm.jobs.index')->with('error', 'Job not found.');
        }
    }

    /**
     * Update the specified job in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $job = HrmJob::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'job_title' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
                'employment_type' => 'required|in:'.implode(',', array_keys(HrmJob::getEmploymentTypeOptions())),
                'experience_level' => 'required|in:'.implode(',', array_keys(HrmJob::getExperienceLevelOptions())),
                'location' => 'required|string|max:255',
                'job_description' => 'required|string',
                'requirements' => 'nullable|string',
                'skills' => 'nullable|json', // Change from array to json validation
                'hiring_manager_id' => 'nullable|exists:users,id',
                'status' => 'required|in:'.implode(',', array_keys(HrmJob::getStatusOptions())),
                'posted_date' => 'nullable|date',
                'closing_date' => 'nullable|date|after:posted_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Process skills - decode the JSON string
            $skills = null;
            if ($request->has('skills') && $request->skills !== '[]' && $request->skills !== 'null') {
                $skillsArray = json_decode($request->skills, true);
                if (is_array($skillsArray)) {
                    $skillsArray = array_map('trim', $skillsArray);
                    $skillsArray = array_filter($skillsArray);
                    $skillsArray = array_unique($skillsArray);
                    $skills = json_encode($skillsArray);
                }
            }

            // Update posted_date if status changed to open
            $postedDate = $job->posted_date;
            if ($job->status !== 'open' && $request->status === 'open' && ! $postedDate) {
                $postedDate = Carbon::now();
            }

            // Update job
            $updateData = [
                'job_title' => $request->job_title,
                'department_id' => $request->department_id,
                'employment_type' => $request->employment_type,
                'experience_level' => $request->experience_level,
                'location' => $request->location,
                'job_description' => $request->job_description,
                'requirements' => $request->requirements,
                'skills' => $skills, // Store as JSON string
                'hiring_manager_id' => $request->hiring_manager_id,
                'status' => $request->status,
                'posted_date' => $postedDate,
                'closing_date' => $request->closing_date,
            ];

            $job->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job updated successfully!',
                'data' => $job,
                'redirect' => route('hrm.jobs.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified job from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $job = HrmJob::where('company_id', $companyId)
                ->findOrFail($id);

            // Delete associated applications first
            $job->applications()->delete();

            // Delete the job
            $job->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting job: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update job status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $job = HrmJob::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:'.implode(',', array_keys(HrmJob::getStatusOptions())),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update posted_date if status changed to open
            $updateData = ['status' => $request->status];
            if ($job->status !== 'open' && $request->status === 'open' && ! $job->posted_date) {
                $updateData['posted_date'] = Carbon::now();
            }

            $job->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Job status updated successfully!',
                'data' => $job,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating job status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating job status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get jobs statistics for dashboard.
     */
    public function getStatistics()
    {
        try {
            $companyId = Auth::user()->company_id;

            $totalJobs = HrmJob::where('company_id', $companyId)->count();
            $activeJobs = HrmJob::where('company_id', $companyId)->active()->count();
            $onHoldJobs = HrmJob::where('company_id', $companyId)->where('status', 'on_hold')->count();

            // Fix: Join with hrm_jobs table
            $totalApplicants = DB::table('job_applications')
                ->join('hrm_jobs', 'job_applications.hrm_job_id', '=', 'hrm_jobs.id')
                ->where('hrm_jobs.company_id', $companyId)
                ->count();

            // Get recent jobs
            $recentJobs = HrmJob::where('company_id', $companyId)
                ->with('department')
                ->latest('created_at')
                ->limit(5)
                ->get();

            // Get jobs with most applicants
            $popularJobs = HrmJob::where('company_id', $companyId)
                ->with('department')
                ->orderBy('applicants_count', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_jobs' => $totalJobs,
                    'active_jobs' => $activeJobs,
                    'on_hold_jobs' => $onHoldJobs,
                    'total_applicants' => $totalApplicants,
                    'recent_jobs' => $recentJobs,
                    'popular_jobs' => $popularJobs,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching job statistics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clone an existing job.
     */
    public function clone($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $originalJob = HrmJob::where('company_id', $companyId)
                ->findOrFail($id);

            // Generate new job ID
            $newJobId = HrmJob::generateJobId();

            // Clone the job
            $newJob = $originalJob->replicate();
            $newJob->job_id = $newJobId;
            $newJob->status = 'draft';
            $newJob->applicants_count = 0;
            $newJob->views_count = 0;
            $newJob->posted_date = null;
            $newJob->created_by = Auth::id();
            $newJob->created_at = Carbon::now();
            $newJob->updated_at = Carbon::now();
            $newJob->save();

            DB::commit();

            return redirect()->route('hrm.jobs.edit', $newJob->id)
                ->with('success', 'Job cloned successfully! You can now edit the new job.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cloning job: '.$e->getMessage());

            return redirect()->route('hrm.jobs.index')
                ->with('error', 'Error cloning job: '.$e->getMessage());
        }
    }
}
