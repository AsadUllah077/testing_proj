<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['manager', 'members', 'tasks', 'clientRecord', 'teams'])
            ->where('company_id', Auth::user()->company_id);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('clientRecord', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        $projects = $query->latest()->paginate($request->get('per_page', 15))->withQueryString();

        if ($request->ajax()) {
            return view('pages.projects.partials.table', compact('projects'));
        }

        return view('pages.projects.index', compact('projects'));
    }

    public function create()
    {
        $users   = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $clients = Client::where('company_id', Auth::user()->company_id)->where('status', 'active')->orderBy('name')->get();
        $teams   = Team::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        return view('pages.projects.create', compact('users', 'clients', 'teams'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'client_id'   => 'nullable|exists:clients,id',
            'manager_id'  => 'nullable|exists:employees,id',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'priority'    => 'required|in:low,medium,high,critical',
            'budget'      => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status'      => 'required|in:planning,active,on-hold,completed',
            'team_id'     => 'nullable|exists:teams,id',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $project = Project::create([
                'name'        => $validated['name'],
                'client_id'   => $validated['client_id'] ?? null,
                'manager_id'  => $validated['manager_id'] ?? null,
                'start_date'  => $validated['start_date'] ?? null,
                'end_date'    => $validated['end_date'] ?? null,
                'priority'    => $validated['priority'],
                'budget'      => $validated['budget'] ?? null,
                'description' => $validated['description'] ?? null,
                'status'      => $validated['status'],
                'progress'    => 0,
                'company_id'  => Auth::user()->company_id,
                'created_by'  => Auth::id(),
            ]);

            if (!empty($validated['members'])) {
                $project->members()->sync($validated['members']);
            }

            if (!empty($validated['team_id'])) {
                Team::where('id', $validated['team_id'])->update(['project_id' => $project->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully!',
                'data'    => $project,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@store - failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Project $project)
    {
        $project->load(['manager', 'members', 'tasks.assignee', 'creator', 'teams.members']);

        $totalTasks      = $project->tasks->count();
        $completedTasks  = $project->tasks->where('status', 'completed')->count();
        $inProgressTasks = $project->tasks->where('status', 'in-progress')->count();

        return view('pages.projects.show', compact('project', 'totalTasks', 'completedTasks', 'inProgressTasks'));
    }

    public function edit(Project $project)
    {
        $project->load(['members', 'teams']);
        $users           = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $clients         = Client::where('company_id', Auth::user()->company_id)->where('status', 'active')->orderBy('name')->get();
        $teams           = Team::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $selectedMembers = $project->members->pluck('id')->toArray();
        $selectedTeam    = $project->teams->first()?->id;

        return view('pages.projects.edit', compact('project', 'users', 'clients', 'teams', 'selectedMembers', 'selectedTeam'));
    }

    public function update(Request $request, Project $project)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'client_id'   => 'nullable|exists:clients,id',
            'manager_id'  => 'nullable|exists:employees,id',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'priority'    => 'required|in:low,medium,high,critical',
            'budget'      => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status'      => 'required|in:planning,active,on-hold,completed',
            'progress'    => 'nullable|integer|min:0|max:100',
            'team_id'     => 'nullable|exists:teams,id',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $project->update($validated);
            $project->members()->sync($validated['members'] ?? []);

            // Detach old team(s) then assign new one
            Team::where('project_id', $project->id)->update(['project_id' => null]);
            if (!empty($validated['team_id'])) {
                Team::where('id', $validated['team_id'])->update(['project_id' => $project->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully!',
                'data'    => $project,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@update - failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Project $project)
    {
        try {
            $project->delete();
            return response()->json(['success' => true, 'message' => 'Project deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('ProjectController@destroy - failed', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $projects = Project::with(['manager', 'clientRecord', 'teams'])
                ->where('company_id', Auth::user()->company_id)
                ->when($request->get('q'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
                ->when($request->get('priority'), fn($q, $p) => $q->where('priority', $p))
                ->latest()->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.projects.exports.pdf', compact('projects'));
            return $pdf->download('projects-' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('ProjectController@exportPdf - failed', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'PDF export failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $projects = Project::with(['manager', 'clientRecord', 'teams'])
                ->where('company_id', Auth::user()->company_id)
                ->when($request->get('q'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
                ->when($request->get('priority'), fn($q, $p) => $q->where('priority', $p))
                ->latest()->get();

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="projects-' . now()->format('Y-m-d') . '.csv"',
            ];
            $callback = function () use ($projects) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Name', 'Client', 'Team', 'Manager', 'Start Date', 'End Date', 'Priority', 'Status', 'Progress']);
                foreach ($projects as $project) {
                    fputcsv($handle, [
                        $project->name,
                        $project->clientRecord?->name ?? $project->client ?? '',
                        $project->teams->first()?->name ?? '',
                        $project->manager?->full_name ?? '',
                        $project->start_date?->format('Y-m-d') ?? '',
                        $project->end_date?->format('Y-m-d') ?? '',
                        $project->priority,
                        $project->status,
                        $project->progress . '%',
                    ]);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('ProjectController@exportExcel - failed', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }
}
