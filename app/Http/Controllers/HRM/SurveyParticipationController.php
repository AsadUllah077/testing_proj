<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SurveyParticipationController extends Controller
{
    /**
     * Retrieve the authenticated user's linked employee record.
     */
    private function getEmployee()
    {
        $user = Auth::user();
        return Employee::where('linked_erp_user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();
    }

    /**
     * List all published surveys that the employee is eligible to take.
     */
    public function active()
    {
        try {
            $companyId = Auth::user()->company_id;
            $employee  = $this->getEmployee();
            $today     = now()->toDateString();

            // Fetch all published, active surveys for this company
            $surveys = Survey::where('company_id', $companyId)
                ->where('status', 'Published')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->with('assignments')
                ->get()
                ->filter(function ($survey) use ($employee) {
                    // Check if survey is assigned to this employee
                    return $this->isEmployeeEligible($survey, $employee);
                })
                ->map(function ($survey) use ($employee) {
                    // Check if employee already submitted
                    $survey->already_submitted = $employee
                        ? SurveyResponse::where('survey_id', $survey->id)
                            ->where('employee_id', $employee->id)
                            ->exists()
                        : false;
                    $survey->question_count = $survey->questions()->count();
                    return $survey;
                });

            return view('pages.hrm.surveys.active', compact('surveys', 'employee'));
        } catch (\Exception $e) {
            Log::error('Error loading active surveys: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading surveys.');
        }
    }

    /**
     * Show the survey form for an employee to fill in.
     */
    public function take($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $employee  = $this->getEmployee();
            $today     = now()->toDateString();

            $survey = Survey::where('company_id', $companyId)
                ->where('status', 'Published')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->with(['questions.options', 'assignments'])
                ->findOrFail($id);

            // Eligibility check
            if (!$this->isEmployeeEligible($survey, $employee)) {
                return redirect()->route('hrm.surveys.active')->with('error', 'You are not eligible for this survey.');
            }

            // Duplicate submission check
            if ($employee && SurveyResponse::where('survey_id', $survey->id)->where('employee_id', $employee->id)->exists()) {
                return redirect()->route('hrm.surveys.active')->with('info', 'You have already submitted this survey.');
            }

            $totalQuestions = $survey->questions->count();

            return view('pages.hrm.surveys.take', compact('survey', 'employee', 'totalQuestions'));
        } catch (\Exception $e) {
            Log::error('Error loading survey: ' . $e->getMessage());
            return redirect()->route('hrm.surveys.active')->with('error', 'Survey not found or no longer active.');
        }
    }

    /**
     * Submit survey responses.
     */
    public function submit(Request $request, $id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $employee  = $this->getEmployee();
            $today     = now()->toDateString();

            $survey = Survey::where('company_id', $companyId)
                ->where('status', 'Published')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->with('questions.options')
                ->findOrFail($id);

            // Duplicate submission check
            if ($employee && SurveyResponse::where('survey_id', $survey->id)->where('employee_id', $employee->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'You have already submitted this survey.'], 422);
            }

            // Validate required questions
            $rules = [];
            foreach ($survey->questions as $question) {
                if ($question->is_required) {
                    $rules["answers.{$question->id}"] = 'required';
                }
            }

            if (!empty($rules)) {
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => 'Please answer all required questions.', 'errors' => $validator->errors()], 422);
                }
            }

            DB::beginTransaction();

            // Create response record (anonymous surveys don't store employee_id)
            $response = SurveyResponse::create([
                'survey_id'    => $survey->id,
                'employee_id'  => $survey->is_anonymous ? null : ($employee ? $employee->id : null),
                'submitted_at' => now(),
            ]);

            // Save individual answers
            $answers = $request->input('answers', []);
            foreach ($survey->questions as $question) {
                $answerValue = $answers[$question->id] ?? null;

                // Encode checkbox arrays as JSON
                if (is_array($answerValue)) {
                    $answerValue = json_encode($answerValue);
                }

                SurveyAnswer::create([
                    'response_id'  => $response->id,
                    'question_id'  => $question->id,
                    'answer_value' => $answerValue,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Thank you! Your survey response has been submitted successfully.',
                'redirect_to' => route('hrm.surveys.active'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting survey: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error submitting survey: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check if an employee is eligible for a given survey based on its assignments.
     */
    private function isEmployeeEligible(Survey $survey, ?Employee $employee): bool
    {
        if ($survey->assignments->isEmpty()) {
            return false;
        }

        foreach ($survey->assignments as $assignment) {
            if ($assignment->assignable_type === 'All') {
                return true;
            }

            if (!$employee) {
                continue;
            }

            if ($assignment->assignable_type === 'Employee' && $assignment->assignable_id == $employee->id) {
                return true;
            }

            if ($assignment->assignable_type === 'Department' && $assignment->assignable_id == $employee->department_id) {
                return true;
            }

            // Team check via pivot table
            if ($assignment->assignable_type === 'Team') {
                $inTeam = DB::table('team_members')
                    ->where('team_id', $assignment->assignable_id)
                    ->where('employee_id', $employee->id)
                    ->exists();
                if ($inTeam) {
                    return true;
                }
            }
        }

        return false;
    }
}
