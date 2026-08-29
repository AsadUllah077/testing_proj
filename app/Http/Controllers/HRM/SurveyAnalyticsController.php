<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SurveyAnalyticsController extends Controller
{
    /**
     * Show analytics dashboard for a specific survey.
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $survey = Survey::where('company_id', $companyId)
                ->with(['questions.options', 'questions.answers', 'responses.employee'])
                ->findOrFail($id);

            $totalResponses  = $survey->responses()->count();
            $totalAssignees  = $this->countAssignees($survey);
            $responsePercent = $totalAssignees > 0 ? round(($totalResponses / $totalAssignees) * 100, 1) : 0;

            // Per-question analytics
            $questionAnalytics = [];
            foreach ($survey->questions as $question) {
                $answers = $question->answers;
                $analytics = [
                    'question'      => $question,
                    'total_answers' => $answers->count(),
                ];

                switch ($question->type) {
                    case 'rating':
                        $values = $answers->map(fn($a) => (int) $a->answer_value)->filter(fn($v) => $v > 0);
                        $analytics['average']      = $values->count() ? round($values->avg(), 2) : 0;
                        $analytics['distribution'] = [];
                        for ($i = 1; $i <= 5; $i++) {
                            $analytics['distribution'][$i] = $values->filter(fn($v) => $v === $i)->count();
                        }
                        break;

                    case 'radio':
                    case 'dropdown':
                        $dist = [];
                        foreach ($question->options as $opt) {
                            $dist[$opt->option_text] = $answers->where('answer_value', $opt->option_text)->count();
                        }
                        $analytics['distribution'] = $dist;
                        break;

                    case 'checkbox':
                        $dist = [];
                        foreach ($question->options as $opt) {
                            $dist[$opt->option_text] = 0;
                        }
                        foreach ($answers as $ans) {
                            $selected = json_decode($ans->answer_value, true) ?? [];
                            foreach ($selected as $sel) {
                                if (isset($dist[$sel])) {
                                    $dist[$sel]++;
                                }
                            }
                        }
                        $analytics['distribution'] = $dist;
                        break;

                    case 'text':
                    case 'textarea':
                        $analytics['responses'] = $answers
                            ->pluck('answer_value')
                            ->filter()
                            ->take(20)
                            ->values();
                        break;
                }

                $questionAnalytics[] = $analytics;
            }

            return view('pages.hrm.surveys.analytics', compact(
                'survey',
                'totalResponses',
                'totalAssignees',
                'responsePercent',
                'questionAnalytics'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading survey analytics: ' . $e->getMessage());
            return redirect()->route('hrm.surveys.index')->with('error', 'Error loading analytics.');
        }
    }

    /**
     * Export analytics as CSV.
     */
    public function export($id)
    {
        try {
            $companyId = Auth::user()->company_id;
            $survey = Survey::where('company_id', $companyId)
                ->with(['questions', 'responses.answers.question', 'responses.employee'])
                ->findOrFail($id);

            $filename = 'survey_' . $survey->id . '_responses_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $questions = $survey->questions;

            $callback = function () use ($survey, $questions) {
                $handle = fopen('php://output', 'w');

                // Header row
                $headerRow = ['Response ID', 'Submitted At'];
                if (!$survey->is_anonymous) {
                    $headerRow[] = 'Employee';
                }
                foreach ($questions as $q) {
                    $headerRow[] = $q->question_text;
                }
                fputcsv($handle, $headerRow);

                // Data rows
                foreach ($survey->responses as $response) {
                    $row = [$response->id, $response->submitted_at?->format('Y-m-d H:i')];
                    if (!$survey->is_anonymous) {
                        $row[] = $response->employee?->full_name ?? 'Unknown';
                    }
                    foreach ($questions as $q) {
                        $answer = $response->answers->where('question_id', $q->id)->first();
                        $value  = $answer?->answer_value ?? '';
                        // Decode JSON checkbox arrays
                        if ($q->type === 'checkbox' && $value) {
                            $decoded = json_decode($value, true);
                            $value   = is_array($decoded) ? implode(', ', $decoded) : $value;
                        }
                        $row[] = $value;
                    }
                    fputcsv($handle, $row);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting survey: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed.');
        }
    }

    /**
     * Count total number of employees assigned to this survey.
     */
    private function countAssignees(Survey $survey): int
    {
        $companyId = $survey->company_id;
        $count = 0;

        foreach ($survey->assignments as $assignment) {
            if ($assignment->assignable_type === 'All') {
                return \App\Models\Employee::where('company_id', $companyId)->count();
            }
            if ($assignment->assignable_type === 'Employee') {
                $count++;
            }
            if ($assignment->assignable_type === 'Department') {
                $count += \App\Models\Employee::where('company_id', $companyId)
                    ->where('department_id', $assignment->assignable_id)
                    ->count();
            }
            if ($assignment->assignable_type === 'Team') {
                $count += \Illuminate\Support\Facades\DB::table('team_members')
                    ->where('team_id', $assignment->assignable_id)
                    ->count();
            }
        }

        return $count;
    }
}
