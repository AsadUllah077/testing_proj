<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectTaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->tasks()->with('assignee');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('q')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tasks = $query->latest()->paginate($request->get('per_page', 20))->withQueryString();
        $users = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();

        if ($request->ajax()) {
            return view('pages.projects.tasks.partials.table', compact('project', 'tasks'));
        }

        return view('pages.projects.tasks.index', compact('project', 'tasks', 'users'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:employees,id',
            'due_date'    => 'nullable|date',
            'priority'    => 'required|in:low,medium,high,critical',
            'status'      => 'required|in:todo,in-progress,review,completed',
        ]);

        $task = $project->tasks()->create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        // Update project progress automatically
        $this->updateProjectProgress($project);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'task'    => $task->load('assignee'),
        ]);
    }

    public function update(Request $request, Project $project, ProjectTask $task)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:employees,id',
            'due_date'    => 'nullable|date',
            'priority'    => 'required|in:low,medium,high,critical',
            'status'      => 'required|in:todo,in-progress,review,completed',
        ]);

        $task->update($validated);
        $this->updateProjectProgress($project);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
        ]);
    }

    public function destroy(Project $project, ProjectTask $task)
    {
        $task->delete();
        $this->updateProjectProgress($project);

        return response()->json(['success' => true, 'message' => 'Task deleted successfully.']);
    }

    private function updateProjectProgress(Project $project): void
    {
        $total     = $project->tasks()->count();
        $completed = $project->tasks()->where('status', 'completed')->count();
        $progress  = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $project->update(['progress' => $progress]);
    }
}
