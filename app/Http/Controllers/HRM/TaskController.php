<?php

namespace App\Http\Controllers\HRM;

use App\Exports\TaskImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TasksImport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskDeliverable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request)
    {
        try {
            // Get search parameters
            $search = $request->input('q');
            $departmentId = $request->input('department_id');
            $status = $request->input('status');
            $priority = $request->input('priority');
            $category = $request->input('category');
            $employeeId = $request->input('employee_id');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $companyId = Auth::user()->company_id;

            // Get per_page from request or use default
            $perPage = $request->input('per_page', 20);

            // Start query
            $query = Task::with(['addedBy', 'department', 'assignedEmployees'])
                ->where('company_id', $companyId)
                ->latest('created_at');

            // Apply search filter
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhereHas('assignedEmployees', function ($subQuery) use ($search) {
                            $subQuery->where('full_name', 'LIKE', "%{$search}%");
                        });
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

            // Apply priority filter
            if (! empty($priority)) {
                $query->where('priority', $priority);
            }

            // Apply category filter
            if (! empty($category)) {
                $query->where('category', $category);
            }

            // Apply employee filter - NEW
            if (! empty($employeeId)) {
                $query->whereHas('assignedEmployees', function ($q) use ($employeeId) {
                    $q->where('employees.id', $employeeId);
                });
            }

            // Apply date range filter
            if (! empty($fromDate)) {
                $query->whereDate('deadline', '>=', $fromDate);
            }

            if (! empty($toDate)) {
                $query->whereDate('deadline', '<=', $toDate);
            }

            // Check for overdue tasks
            // $query->where(function ($q) {
            //             $q->where('deadline', '<', now());
            // });

            // Paginate results
            $tasks = $query->paginate($perPage)->withQueryString();

            // Get task statistics
            $totalTasks = Task::where('company_id', $companyId)->count();
            $inProgressTasks = Task::where('company_id', $companyId)
                ->where('status', Task::STATUS_IN_PROGRESS)
                ->count();
            $completedTasks = Task::where('company_id', $companyId)
                ->where('status', Task::STATUS_COMPLETED)
                ->count();
            $overdueTasks = Task::where('company_id', $companyId)
                ->where('status', Task::STATUS_OVERDUE)
                ->count();

            // Get dropdown data
            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $statusOptions = Task::getStatusOptions();
            $priorityOptions = Task::getPriorityOptions();
            $categoryOptions = Task::getCategoryOptions();

            return view('pages.tasks.index', compact(
                'tasks',
                'departments',
                'employees',
                'statusOptions',
                'priorityOptions',
                'categoryOptions',
                'totalTasks',
                'inProgressTasks',
                'completedTasks',
                'overdueTasks'
            ));

        } catch (\Exception $e) {
            Log::error('Error fetching tasks: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error fetching tasks: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        try {
            $companyId = Auth::user()->company_id;

            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $priorityOptions = Task::getPriorityOptions();
            $categoryOptions = Task::getCategoryOptions();

            return view('pages.tasks.create', compact(
                'departments',
                'employees',
                'priorityOptions',
                'categoryOptions'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading task create form: '.$e->getMessage());

            return redirect()->route('tasks.index')->with('error', 'Error loading form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
{
    DB::beginTransaction();
    try {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'required|date',
                'department_id' => 'nullable|exists:departments,id',
                'priority' => 'required|in:low,medium,high',
                'category' => 'required|in:daily,weekly',
                'manager_remarks' => 'nullable|string|max:1000',
                'assigned_employees' => 'nullable|array',
                'assigned_employees.*' => 'exists:employees,id',
                'status' => 'required|in:draft,pending,in_progress,review,completed,on_hold,cancelled,overdue',
                'task_type' => 'nullable|in:individual,team,project',
                'enable_notifications' => 'nullable|boolean',
                'allow_updates' => 'nullable|boolean',
                'tags' => 'nullable|string|max:500',
            ],
            [
                'title.required' => 'Please enter a task title.',
                'deadline.required' => 'Please select a deadline.',
                'priority.required' => 'Please select a priority level.',
                'category.required' => 'Please select a task category.',
                'status.required' => 'Please select a task status.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepare task data
        $taskData = [
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'department_id' => $request->department_id,
            'priority' => $request->priority,
            'category' => $request->category,
            'manager_remarks' => $request->manager_remarks,
            'status' => $request->status,
            'added_by' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ];

        // Set completed_at if status is completed
        if ($request->status === 'completed') {
            $taskData['completed_at'] = now();
        } else {
            $taskData['completed_at'] = null;
        }

        // Create task
        $task = Task::create($taskData);

        // Assign employees if any
        if ($request->has('assigned_employees') && is_array($request->assigned_employees)) {
            $task->assignedEmployees()->sync($request->assigned_employees);
        }

        // Check if task is already overdue (only if status is not already set to something else)
        if ($request->status !== 'completed' && $request->status !== 'cancelled' &&
            Carbon::parse($request->deadline) < now()) {
            $task->update(['status' => 'overdue']);
        }

        // Handle notifications if enabled
        if ($request->boolean('enable_notifications')) {
            // Add notification logic here
            // Example: Send email notifications to assigned employees
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully!',
            'data' => $task,
            'redirect' => route('tasks.index'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating task: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error creating task: '.$e->getMessage(),
        ], 500);
    }
}

public function update(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $companyId = Auth::user()->company_id;
        $task = Task::where('company_id', $companyId)
            ->findOrFail($id);

        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'required|date',
                'department_id' => 'nullable|exists:departments,id',
                'priority' => 'required|in:low,medium,high',
                'category' => 'required|in:daily,weekly',
                'manager_remarks' => 'nullable|string|max:1000',
                'assigned_employees' => 'nullable|array',
                'assigned_employees.*' => 'exists:employees,id',
                'status' => 'required',
            ],
            [
                'title.required' => 'Please enter a task title.',
                'deadline.required' => 'Please select a deadline.',
                'priority.required' => 'Please select a priority level.',
                'category.required' => 'Please select a task category.',
                'status.required' => 'Please select a status.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepare update data
        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'department_id' => $request->department_id,
            'priority' => $request->priority,
            'category' => $request->category,
            'manager_remarks' => $request->manager_remarks,
            'status' => $request->status,
        ];

        // Handle completed_at based on status
        if ($request->status === 'completed') {
            // If status is changed to completed, set completed_at to now
            $updateData['completed_at'] = now();
        } elseif ($task->status === 'completed' && $request->status !== 'completed') {
            // If status was completed but is being changed to something else, clear completed_at
            $updateData['completed_at'] = null;
        }
        // If status is not 'completed' and wasn't 'completed' before, keep completed_at as is (likely null)

        // Check if task is overdue (only if new status is not completed or cancelled)
        if ($request->status !== 'completed' && $request->status !== 'cancelled' &&
            Carbon::parse($request->deadline) < now()) {
            $updateData['status'] = 'overdue';
            // If setting to overdue, ensure completed_at is null
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        // Update assigned employees
        if ($request->has('assigned_employees') && is_array($request->assigned_employees)) {
            $task->assignedEmployees()->sync($request->assigned_employees);
        } else {
            $task->assignedEmployees()->detach();
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully!',
            'data' => $task,
            'redirect' => route('tasks.index'),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating task: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error updating task: '.$e->getMessage(),
        ], 500);
    }
}

    /**
     * Display the specified task.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $task = Task::with(['addedBy', 'department', 'assignedEmployees', 'deliverables.submittedBy'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            return view('pages.tasks.show', compact('task'));

        } catch (\Exception $e) {
            Log::error('Error fetching task: '.$e->getMessage());

            return redirect()->route('tasks.index')->with('error', 'Task not found.');
        }
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $task = Task::with(['assignedEmployees'])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            $departments = Department::where('company_id', $companyId)
                ->orderBy('department')
                ->get();

            $employees = Employee::where('company_id', $companyId)
                ->orderBy('full_name')
                ->get();

            $priorityOptions = Task::getPriorityOptions();
            $categoryOptions = Task::getCategoryOptions();
            $statusOptions = Task::getStatusOptions();

            return view('pages.tasks.edit', compact(
                'task',
                'departments',
                'employees',
                'priorityOptions',
                'categoryOptions',
                'statusOptions'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading task edit form: '.$e->getMessage());

            return redirect()->route('tasks.index')->with('error', 'Task not found.');
        }
    }



    /**
     * Remove the specified task from storage.
     */                                                           
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $task = Task::where('company_id', $companyId)
                ->findOrFail($id);

            // Remove assigned employees
            $task->assignedEmployees()->detach();

            // Delete the task
            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting task: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting task: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update task status.
     */
    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $task = Task::where('company_id', $companyId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,in_progress,completed,overdue',
                'remarks' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update task status
            $task->update([
                'status' => $request->status,
                'manager_remarks' => $request->remarks ?: $task->manager_remarks,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully!',
                'data' => $task,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating task status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating task status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get task statistics.
     */
    public function getStatistics()
    {
        try {
            $companyId = Auth::user()->company_id;

            $totalTasks = Task::where('company_id', $companyId)->count();
            $inProgressTasks = Task::where('company_id', $companyId)
                ->where('status', Task::STATUS_IN_PROGRESS)
                ->count();
            $completedTasks = Task::where('company_id', $companyId)
                ->where('status', Task::STATUS_COMPLETED)
                ->count();
            $overdueTasks = Task::where('company_id', $companyId)
                ->overdue()
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks' => $totalTasks,
                    'in_progress' => $inProgressTasks,
                    'completed' => $completedTasks,
                    'overdue' => $overdueTasks,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching task statistics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted($id)
    {
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $task = Task::where('company_id', $companyId)
                ->findOrFail($id);

            $task->update([
                'status' => Task::STATUS_COMPLETED,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task marked as completed!',
                'data' => $task,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking task as completed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating task: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tasks for calendar view.
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

            // Query tasks for the month
            $query = Task::with(['department', 'assignedEmployees'])
                ->where('company_id', $companyId)
                ->whereBetween('deadline', [$startDate, $endDate]);

            // Filter by department
            if ($request->filled('department_id') && $request->department_id != '') {
                $query->where('department_id', $request->department_id);
            }

            // Filter by priority
            if ($request->filled('priority') && $request->priority != '') {
                $query->where('priority', $request->priority);
            }

            $tasks = $query->orderBy('deadline')->get();

            // Format the response
            $formattedTasks = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'deadline' => $task->deadline->format('Y-m-d H:i:s'),
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'category' => $task->category,
                    'department' => $task->department ? $task->department->name : null,
                    'assigned_employees' => $task->assignedEmployees->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'full_name' => $employee->full_name,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTasks,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching calendar data: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching calendar data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new deliverable for a task.
     */
    /**
     * Store a new deliverable for a task.
     */
    public function storeDeliverable(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'task_id' => 'required|exists:tasks,id',
                    'title' => 'required|string|max:255',
                    'type' => 'required|in:file,text',
                    'description' => 'required_if:type,text|nullable|string',
                    'file' => 'required_if:type,file|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,gif,bmp,svg,zip,rar,txt,ppt,pptx|max:20480',
                ],
                [
                    'title.required' => 'Please enter a deliverable title.',
                    'type.required' => 'Please select a deliverable type.',
                    'description.required_if' => 'Please enter a description for text deliverables.',
                    'file.required_if' => 'Please upload a file.',
                    'file.mimes' => 'Allowed file types: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, SVG, ZIP, RAR, TXT, PPT, PPTX.',
                    'file.max' => 'File size must not exceed 20MB.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check if user is assigned to task or has permission
            $task = Task::find($request->task_id);
            $user = Auth::user();

            // if (! $task->isAssignedTo($user) && ! $user->can('add-deliverable')) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'You are not authorized to submit deliverables for this task.',
            //     ], 403);
            // }

            $deliverableData = [
                'task_id' => $request->task_id,
                'submitted_by' => Auth::id(),
                'title' => $request->title,
                'type' => $request->type,
                'description' => $request->description,
            ];

            // Handle file upload
            if ($request->type === 'file' && $request->hasFile('file')) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = time().'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.'.$extension;
                $filePath = $file->storeAs('task_deliverables', $fileName, 'public');

                $deliverableData['file_path'] = $filePath;
                $deliverableData['file_name'] = $originalName;
                $deliverableData['file_size'] = $file->getSize();
                $deliverableData['file_mime'] = $file->getMimeType();
                $deliverableData['file_extension'] = $extension;
            }

            $deliverable = TaskDeliverable::create($deliverableData);

            // Update task status to "in_progress" if it's still draft or revision_requested
            if (in_array($task->status, [Task::STATUS_DRAFT, 'revision_requested'])) {
                $task->update(['status' => Task::STATUS_IN_PROGRESS]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deliverable submitted successfully!',
                'data' => $deliverable,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting deliverable: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error submitting deliverable: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkComplete(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        try {
            $taskIds = $request->input('task_ids');

            // Update tasks to completed status
            Task::whereIn('id', $taskIds)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => count($taskIds).' task(s) marked as completed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tasks: '.$e->getMessage(),
            ], 500);
        }
    }

    // public function bulkDelete(Request $request)
    // {
    //     $request->validate([
    //         'task_ids' => 'required|array',
    //         'task_ids.*' => 'exists:tasks,id',
    //     ]);

    //     try {
    //         $taskIds = $request->input('task_ids');
    //         $count = count($taskIds);

    //         // Delete tasks
    //         Task::whereIn('id', $taskIds)->delete();

    //         return response()->json([
    //             'success' => true,
    //             'message' => $count.' task(s) deleted successfully.',
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to delete tasks: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled',
        ]);

        try {
            $taskIds = $request->input('task_ids');
            $status = $request->input('status');

            // Update tasks status
            Task::whereIn('id', $taskIds)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            $statusLabels = [
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
                'cancelled' => 'Cancelled',
            ];

            return response()->json([
                'success' => true,
                'message' => count($taskIds).' task(s) marked as '.$statusLabels[$status].' successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tasks status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function changeStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled',
        ]);

        try {
            $oldStatus = $task->status;
            $newStatus = $request->input('status');

            $task->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

            $statusLabels = [
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
                'cancelled' => 'Cancelled',
            ];

            return response()->json([
                'success' => true,
                'message' => 'Task status changed from '.$statusLabels[$oldStatus].' to '.$statusLabels[$newStatus].' successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download Excel template for task import.
     */
    public function downloadTemplate()
    {
        try {
            $fileName = 'task-import-template-'.date('Y-m-d').'.xlsx';

            return Excel::download(new TaskImportTemplateExport, $fileName);
        } catch (\Exception $e) {
            Log::error('Error downloading template: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error downloading template: '.$e->getMessage());
        }
    }

    /**
     * Show bulk import form.
     */
    public function showImportForm()
    {
        try {
            return view('pages.tasks.import');
        } catch (\Exception $e) {
            Log::error('Error loading import form: '.$e->getMessage());

            return redirect()->route('tasks.index')->with('error', 'Error loading import form: '.$e->getMessage());
        }
    }

    /**
     * Process bulk import.
     */
    /**
     * Process bulk import.
     */
    public function processImport(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ], [
                'excel_file.required' => 'Please select an Excel file to upload.',
                'excel_file.mimes' => 'Only Excel files (xlsx, xls, csv) are allowed.',
                'excel_file.max' => 'File size must not exceed 10MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('excel_file');

            // Check if file has data using a simple approach
            $hasData = $this->checkFileHasData($file);

            if (! $hasData) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Excel file is empty or has no data rows.',
                ], 422);
            }

            // Process import
            $import = new TasksImport(Auth::user()->company_id);
            Excel::import($import, $file);

            $importResult = $import->getImportResult();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tasks imported successfully!',
                'data' => $importResult,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing tasks: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error importing tasks: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if Excel file has data rows.
     */
    private function checkFileHasData($file)
    {
        try {
            // Use a temporary import class just to check headers and first row
            $tempImport = new class implements \Maatwebsite\Excel\Concerns\ToCollection
            {
                public $hasData = false;

                public $rowCount = 0;

                public function collection(\Illuminate\Support\Collection $rows)
                {
                    $this->rowCount = $rows->count();
                    // Check if we have at least 2 rows (header + at least one data row)
                    $this->hasData = $rows->count() >= 2;

                    // Also check if the first data row has any non-empty values
                    if ($this->hasData) {
                        $dataRows = $rows->slice(1); // Skip header
                        foreach ($dataRows as $row) {
                            $rowArray = $row->toArray();
                            $nonEmptyCells = array_filter($rowArray, function ($cell) {
                                return ! empty($cell) && trim($cell) !== '';
                            });
                            if (count($nonEmptyCells) > 0) {
                                $this->hasData = true;

                                return;
                            }
                        }
                        $this->hasData = false;
                    }
                }
            };

            Excel::import($tempImport, $file);

            return $tempImport->hasData;

        } catch (\Exception $e) {
            Log::error('Error checking file data: '.$e->getMessage());

            return false;
        }
    }
    public function bulkUpdate(Request $request)
{
    DB::beginTransaction();
    try {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled,overdue,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = Auth::user()->company_id;
        $taskIds = $request->input('task_ids');
        $status = $request->input('status');

        // Update tasks that belong to the user's company
        $updatedCount = Task::where('company_id', $companyId)
            ->whereIn('id', $taskIds)
            ->update(['status' => $status]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updatedCount} task(s) to " . str_replace('_', ' ', $status),
            'updated_count' => $updatedCount,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error bulk updating tasks: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error updating tasks: '.$e->getMessage(),
        ], 500);
    }
}

public function bulkDelete(Request $request)
{
    DB::beginTransaction();
    try {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = Auth::user()->company_id;
        $taskIds = $request->input('task_ids');

        // Get tasks before deletion for statistics update
        $tasks = Task::where('company_id', $companyId)
            ->whereIn('id', $taskIds)
            ->get();

        // Detach relationships
        foreach ($tasks as $task) {
            $task->assignedEmployees()->detach();
        }

        // Delete tasks
        $deletedCount = Task::where('company_id', $companyId)
            ->whereIn('id', $taskIds)
            ->delete();

        DB::commit();

        // Get updated statistics
        $totalTasks = Task::where('company_id', $companyId)->count();
        $inProgressTasks = Task::where('company_id', $companyId)
            ->where('status', Task::STATUS_IN_PROGRESS)
            ->count();
        $completedTasks = Task::where('company_id', $companyId)
            ->where('status', Task::STATUS_COMPLETED)
            ->count();
        $overdueTasks = Task::where('company_id', $companyId)
            ->where('status', Task::STATUS_OVERDUE)
            ->count();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} task(s)",
            'deleted_count' => $deletedCount,
            'stats' => [
                'total' => $totalTasks,
                'inProgress' => $inProgressTasks,
                'completed' => $completedTasks,
                'overdue' => $overdueTasks,
            ]
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error bulk deleting tasks: '.$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error deleting tasks: '.$e->getMessage(),
        ], 500);
    }
}

    /**
     * Validate Excel file before import (preview).
     */
    /**
     * Validate Excel file before import (preview).
     */
    public function validateImport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('excel_file');

            // Get the actual headers from the file
            $reader = \Maatwebsite\Excel\Facades\Excel::toArray(new \stdClass, $file);
            $firstSheet = $reader[0] ?? [];

            if (empty($firstSheet)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Excel file is empty.',
                ], 422);
            }

            $headers = array_shift($firstSheet); // Get header row
            $previewRows = array_slice($firstSheet, 0, 5); // Get first 5 data rows

            // Clean up headers - remove any null values
            $headers = array_filter($headers, function ($value) {
                return $value !== null;
            });

            // Expected headers (simplified versions)
            $expectedHeaders = [
                'Title',
                'Description',
                'Deadline',
                'Priority',
                'Category',
                'Department Name',
                'Assigned Employee Full Names', // Updated
                'Manager Remarks',
            ];

            // Normalize headers for comparison
            $normalizedHeaders = array_map(function ($header) {
                // Convert to lowercase, remove special characters and extra text
                return strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $header));
            }, $headers);

            $normalizedExpected = array_map(function ($header) {
                return strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $header));
            }, $expectedHeaders);

            // Check if all expected headers are present
            $headerValidation = [];
            $missingHeaders = [];

            foreach ($normalizedExpected as $expected) {
                $found = false;
                foreach ($normalizedHeaders as $actual) {
                    if (str_contains($actual, $expected) || str_contains($expected, $actual)) {
                        $found = true;
                        break;
                    }
                }
                $headerValidation[$expected] = $found;

                if (! $found) {
                    $missingHeaders[] = $expected;
                }
            }

            // Prepare preview data with original headers
            $previewData = [];
            foreach ($previewRows as $row) {
                $previewData[] = array_slice($row, 0, count($headers));
            }

            return response()->json([
                'success' => true,
                'headers_valid' => empty($missingHeaders),
                'header_validation' => $headerValidation,
                'missing_headers' => $missingHeaders,
                'actual_headers' => $headers,
                'expected_headers' => $expectedHeaders,
                'preview' => $previewData,
                'total_rows' => count($firstSheet),
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating import file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error validating file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get deliverable details.
     */
    public function getDeliverableDetails($id)
    {
        try {
            $deliverable = TaskDeliverable::with('submittedBy')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $deliverable->id,
                    'title' => $deliverable->title,
                    'description' => $deliverable->description,
                    'type' => $deliverable->type,
                    'submitted_by' => $deliverable->submittedBy->full_name,
                    'created_at' => $deliverable->created_at->format('M d, Y h:i A'),
                    'file_name' => $deliverable->file_name,
                    'file_url' => $deliverable->file_path ? Storage::url($deliverable->file_path) : null,
                    'formatted_file_size' => $deliverable->formatted_file_size,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching deliverable details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching deliverable details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a deliverable.
     */
    public function deleteDeliverable($id)
    {
        DB::beginTransaction();
        try {
            $deliverable = TaskDeliverable::findOrFail($id);

            // Check if user has permission to delete
            $user = Auth::user();
            $task = $deliverable->task;

            if ($deliverable->submitted_by !== $user->id &&
                ! $user->can('delete-deliverable') &&
                $user->id !== $task->added_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this deliverable.',
                ], 403);
            }

            // Delete file if exists
            if ($deliverable->file_path && Storage::disk('public')->exists($deliverable->file_path)) {
                Storage::disk('public')->delete($deliverable->file_path);
            }

            $deliverable->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deliverable deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting deliverable: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting deliverable: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle manager actions (approve, request revision, save remarks).
     */
    public function managerAction(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $task = Task::findOrFail($id);
            $user = Auth::user();

            // Check if user is manager or has permission
            if (! $user->can('manage-tasks') && $user->id !== $task->added_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:approve,revision,remarks',
                'remarks' => 'nullable|string|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = [
                'manager_remarks' => $request->remarks ?: $task->manager_remarks,
            ];

            switch ($request->action) {
                case 'approve':
                    $updateData['status'] = Task::STATUS_COMPLETED;
                    $message = 'Task approved successfully!';
                    break;

                case 'revision':
                    $updateData['status'] = 'revision_requested';
                    $message = 'Revision requested successfully!';
                    break;

                case 'remarks':
                    $message = 'Remarks saved successfully!';
                    break;
            }

            $task->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $task,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing manager action: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error performing action: '.$e->getMessage(),
            ], 500);
        }
    }
}
