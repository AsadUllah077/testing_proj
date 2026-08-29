<?php

namespace App\Http\Controllers\QMS\Training;

use App\Http\Controllers\Controller;
use App\Models\QmsTraining;
use App\Models\QmsTrainingEmployee;
use App\Models\QmsSop;
use App\Models\QmsPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search  = $request->input('q');
            $status  = $request->input('status');
            $perPage = $request->input('per_page', 20);

            $query = QmsTraining::with(['sop', 'policy', 'employees'])
                ->where('company_id', Auth::user()->company_id)
                ->latest();

            if (!empty($search)) {
                $query->where('title', 'LIKE', "%{$search}%");
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            $trainings = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            $stats = [
                'total'       => QmsTraining::where('company_id', Auth::user()->company_id)->count(),
                'completed'   => QmsTraining::where('company_id', Auth::user()->company_id)->where('status', 'completed')->count(),
                'pending'     => QmsTraining::where('company_id', Auth::user()->company_id)->where('status', 'pending')->count(),
                'in_progress' => QmsTraining::where('company_id', Auth::user()->company_id)->where('status', 'in_progress')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.qms.training.partials.table', compact('trainings'))->render(),
                ]);
            }

            return view('pages.qms.training.index', compact('trainings', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching trainings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching trainings.');
        }
    }

    public function create()
    {
        $sops     = QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'approved')->get();
        $policies = QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'active')->get();

        return view('pages.qms.training.create', compact('sops', 'policies'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'            => 'required|string|max:255',
                'description'      => 'nullable|string',
                'linked_sop_id'    => 'nullable|exists:qms_sops,id',
                'linked_policy_id' => 'nullable|exists:qms_policies,id',
                'status'           => 'required|in:pending,in_progress,completed',
                'employee_ids'     => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            $training = QmsTraining::create([
                'title'                  => $request->title,
                'description'            => $request->description,
                'linked_sop_id'          => $request->linked_sop_id,
                'linked_policy_id'       => $request->linked_policy_id,
                'status'                 => $request->status,
                'certification_enabled'  => $request->boolean('certification_enabled'),
                'certification_criteria' => $request->certification_criteria,
                'has_assessment'         => $request->boolean('has_assessment'),
                'company_id'             => Auth::user()->company_id,
                'created_by'             => Auth::id(),
            ]);

            if ($request->has('employee_ids') && is_array($request->employee_ids)) {
                foreach ($request->employee_ids as $empId) {
                    QmsTrainingEmployee::create([
                        'training_id' => $training->id,
                        'employee_id' => $empId,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Training created successfully!', 'data' => $training], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error creating training: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $training = QmsTraining::with(['sop', 'policy', 'employees.employee'])
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($id);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'     => $training,
                ]);
            }

            return view('pages.qms.training.show', compact('training'));

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Training not found.'], 404);
            }
            return redirect()->route('qms.training.index')->with('error', 'Training not found.');
        }
    }

    public function edit($id)
    {
        try {
            $training = QmsTraining::with('employees')->where('company_id', Auth::user()->company_id)->findOrFail($id);
            $sops     = QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'approved')->get();
            $policies = QmsPolicy::where('company_id', Auth::user()->company_id)->where('status', 'active')->get();

            return view('pages.qms.training.edit', compact('training', 'sops', 'policies'));
        } catch (\Exception $e) {
            return redirect()->route('qms.training.index')->with('error', 'Training not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $training = QmsTraining::where('company_id', Auth::user()->company_id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'  => 'required|string|max:255',
                'status' => 'required|in:pending,in_progress,completed',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $training->update([
                'title'                  => $request->title,
                'description'            => $request->description,
                'linked_sop_id'          => $request->linked_sop_id,
                'linked_policy_id'       => $request->linked_policy_id,
                'status'                 => $request->status,
                'certification_enabled'  => $request->boolean('certification_enabled'),
                'certification_criteria' => $request->certification_criteria,
                'has_assessment'         => $request->boolean('has_assessment'),
            ]);

            return response()->json(['success' => true, 'message' => 'Training updated successfully!', 'data' => $training]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating training: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $training = QmsTraining::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $training->delete();
            return response()->json(['success' => true, 'message' => 'Training deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting training.'], 500);
        }
    }
}
