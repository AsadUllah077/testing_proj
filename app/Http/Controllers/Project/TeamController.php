<?php
namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with(['project'])
            ->where('company_id', Auth::user()->company_id)
            ->latest()->paginate(20);
        return view('pages.projects.teams.index', compact('teams'));
    }

    public function create()
    {
        $employees = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $projects  = Project::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        return view('pages.projects.teams.create', compact('employees', 'projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'members'    => 'nullable|array',
            'members.*'  => 'exists:employees,id',
        ]);
        try {
            $team = Team::create([
                'name'       => $request->name,
                'project_id' => $request->project_id ?: null,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);
            if ($request->members) {
                $team->members()->sync($request->members);
            }
            return response()->json(['success' => true, 'message' => 'Team created successfully!', 'redirect' => route('project.teams.index')]);
        } catch (\Exception $e) {
            Log::error('TeamController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create team: ' . $e->getMessage()], 500);
        }
    }

    public function edit(Team $team)
    {
        $employees       = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $projects        = Project::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $selectedMembers = $team->members->pluck('id')->toArray();
        return view('pages.projects.teams.edit', compact('team', 'employees', 'projects', 'selectedMembers'));
    }

    public function update(Request $request, Team $team)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'members'    => 'nullable|array',
            'members.*'  => 'exists:employees,id',
        ]);
        try {
            $team->update([
                'name'       => $request->name,
                'project_id' => $request->project_id ?: null,
            ]);
            $team->members()->sync($request->members ?? []);
            return response()->json(['success' => true, 'message' => 'Team updated successfully!', 'redirect' => route('project.teams.index')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update team: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Team $team)
    {
        try {
            $team->delete();
            return response()->json(['success' => true, 'message' => 'Team deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
