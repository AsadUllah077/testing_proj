<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $search    = $request->input('q');
            $status    = $request->input('status');
            $perPage   = $request->input('per_page', 15);
            $fromDate  = $request->input('from_date');
            $toDate    = $request->input('to_date');

            $query = Survey::where('company_id', $companyId)
                ->withCount('responses')
                ->with('assignments')
                ->latest();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($fromDate)) {
                $query->whereDate('start_date', '>=', $fromDate);
            }

            if (!empty($toDate)) {
                $query->whereDate('end_date', '<=', $toDate);
            }

            $surveys = $query->paginate($perPage)->withQueryString();

            $stats = [
                'total'     => Survey::where('company_id', $companyId)->count(),
                'published' => Survey::where('company_id', $companyId)->where('status', 'Published')->count(),
                'draft'     => Survey::where('company_id', $companyId)->where('status', 'Draft')->count(),
                'closed'    => Survey::where('company_id', $companyId)->where('status', 'Closed')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html'       => view('pages.hrm.surveys.partials.table', compact('surveys'))->render(),
                    'pagination' => $surveys->links()->toHtml(),
                ]);
            }

            return view('pages.hrm.surveys.index', compact('surveys', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error fetching surveys: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching surveys.');
        }
    }

    public function create()
    {
        $companyId   = Auth::user()->company_id;
        $employees   = Employee::where('company_id', $companyId)->get();
        $departments = Department::where('company_id', $companyId)->get();
        $teams       = Team::where('company_id', $companyId)->get();

        return view('pages.hrm.surveys.create', compact('employees', 'departments', 'teams'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'                          => 'required|string|max:255',
                'description'                    => 'nullable|string',
                'start_date'                     => 'required|date',
                'end_date'                       => 'required|date|after_or_equal:start_date',
                'status'                         => 'required|in:Draft,Published,Closed',
                'is_anonymous'                   => 'nullable|boolean',
                'assign_type'                    => 'required|in:All,Department,Team,Employee',
                'assign_ids'                     => 'nullable|array',
                'questions'                      => 'required|array|min:1',
                'questions.*.question_text'      => 'required|string',
                'questions.*.type'               => 'required|in:text,textarea,radio,checkbox,dropdown,rating',
                'questions.*.is_required'        => 'nullable|boolean',
                'questions.*.options'            => 'nullable|array',
                'questions.*.options.*'          => 'nullable|string',
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $survey = Survey::create([
                'company_id'   => Auth::user()->company_id,
                'title'        => $request->title,
                'description'  => $request->description,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'status'       => $request->status,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'created_by'   => Auth::id(),
            ]);

            // Save assignment
            $assignType = $request->assign_type;
            if ($assignType === 'All') {
                $survey->assignments()->create(['assignable_type' => 'All', 'assignable_id' => null]);
            } else {
                foreach ((array) $request->assign_ids as $assignId) {
                    $survey->assignments()->create(['assignable_type' => $assignType, 'assignable_id' => $assignId]);
                }
            }

            // Save questions
            foreach ($request->questions as $index => $questionData) {
                $question = $survey->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'type'          => $questionData['type'],
                    'is_required'   => isset($questionData['is_required']) ? (bool) $questionData['is_required'] : false,
                    'sort_order'    => $index,
                ]);

                // Save options for applicable question types
                if (in_array($questionData['type'], ['radio', 'checkbox', 'dropdown']) && !empty($questionData['options'])) {
                    foreach (array_values(array_filter($questionData['options'])) as $optionIndex => $optionText) {
                        $question->options()->create([
                            'option_text' => $optionText,
                            'sort_order'  => $optionIndex,
                        ]);
                    }
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Survey created successfully!', 'redirect_to' => route('hrm.surveys.index')]);
            }
            return redirect()->route('hrm.surveys.index')->with('success', 'Survey created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating survey: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error creating survey: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $companyId   = Auth::user()->company_id;
            $survey      = Survey::where('company_id', $companyId)->with(['questions.options', 'assignments'])->findOrFail($id);
            $employees   = Employee::where('company_id', $companyId)->get();
            $departments = Department::where('company_id', $companyId)->get();
            $teams       = Team::where('company_id', $companyId)->get();

            return view('pages.hrm.surveys.edit', compact('survey', 'employees', 'departments', 'teams'));
        } catch (\Exception $e) {
            return redirect()->route('hrm.surveys.index')->with('error', 'Survey not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $survey    = Survey::where('company_id', $companyId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'                          => 'required|string|max:255',
                'description'                    => 'nullable|string',
                'start_date'                     => 'required|date',
                'end_date'                       => 'required|date|after_or_equal:start_date',
                'status'                         => 'required|in:Draft,Published,Closed',
                'is_anonymous'                   => 'nullable|boolean',
                'assign_type'                    => 'required|in:All,Department,Team,Employee',
                'assign_ids'                     => 'nullable|array',
                'questions'                      => 'required|array|min:1',
                'questions.*.question_text'      => 'required|string',
                'questions.*.type'               => 'required|in:text,textarea,radio,checkbox,dropdown,rating',
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $survey->update([
                'title'        => $request->title,
                'description'  => $request->description,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'status'       => $request->status,
                'is_anonymous' => $request->boolean('is_anonymous'),
            ]);

            // Rebuild assignments
            $survey->assignments()->delete();
            $assignType = $request->assign_type;
            if ($assignType === 'All') {
                $survey->assignments()->create(['assignable_type' => 'All', 'assignable_id' => null]);
            } else {
                foreach ((array) $request->assign_ids as $assignId) {
                    $survey->assignments()->create(['assignable_type' => $assignType, 'assignable_id' => $assignId]);
                }
            }

            // Rebuild questions (delete old, insert new)
            $survey->questions()->each(function ($q) {
                $q->options()->delete();
            });
            $survey->questions()->delete();

            foreach ($request->questions as $index => $questionData) {
                $question = $survey->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'type'          => $questionData['type'],
                    'is_required'   => isset($questionData['is_required']) ? (bool) $questionData['is_required'] : false,
                    'sort_order'    => $index,
                ]);
                if (in_array($questionData['type'], ['radio', 'checkbox', 'dropdown']) && !empty($questionData['options'])) {
                    foreach (array_values(array_filter($questionData['options'])) as $optionIndex => $optionText) {
                        $question->options()->create(['option_text' => $optionText, 'sort_order' => $optionIndex]);
                    }
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Survey updated successfully!', 'redirect_to' => route('hrm.surveys.index')]);
            }
            return redirect()->route('hrm.surveys.index')->with('success', 'Survey updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating survey: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error updating survey: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $survey    = Survey::where('company_id', $companyId)->findOrFail($id);
            $survey->delete();

            return response()->json(['success' => true, 'message' => 'Survey deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Error deleting survey: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting survey.'], 500);
        }
    }
}
