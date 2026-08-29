<?php

namespace App\Http\Controllers\QMS\ManagementReview;

use App\Http\Controllers\Controller;
use App\Models\QmsCapa;
use App\Models\QmsPolicy;
use App\Models\QmsSop;
use App\Models\QmsTraining;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagementReviewController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $stats = [
            'policies'  => [
                'total'        => QmsPolicy::where('company_id', $companyId)->count(),
                'active'       => QmsPolicy::where('company_id', $companyId)->where('status', 'active')->count(),
                'under_review' => QmsPolicy::where('company_id', $companyId)->where('status', 'under_review')->count(),
                'draft'        => QmsPolicy::where('company_id', $companyId)->where('status', 'draft')->count(),
            ],
            'sops'      => [
                'total'    => QmsSop::where('company_id', $companyId)->count(),
                'approved' => QmsSop::where('company_id', $companyId)->where('status', 'approved')->count(),
                'review'   => QmsSop::where('company_id', $companyId)->where('status', 'review')->count(),
            ],
            'trainings' => [
                'total'       => QmsTraining::where('company_id', $companyId)->count(),
                'completed'   => QmsTraining::where('company_id', $companyId)->where('status', 'completed')->count(),
                'pending'     => QmsTraining::where('company_id', $companyId)->where('status', 'pending')->count(),
                'in_progress' => QmsTraining::where('company_id', $companyId)->where('status', 'in_progress')->count(),
            ],
            'capas'     => [
                'total'       => QmsCapa::where('company_id', $companyId)->count(),
                'open'        => QmsCapa::where('company_id', $companyId)->where('status', 'open')->count(),
                'in_progress' => QmsCapa::where('company_id', $companyId)->where('status', 'in_progress')->count(),
                'closed'      => QmsCapa::where('company_id', $companyId)->where('status', 'closed')->count(),
            ],
        ];

        // Monthly CAPA trend (last 6 months)
        $capaTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $capaTrend[] = [
                'month' => $month->format('M'),
                'open'  => QmsCapa::where('company_id', $companyId)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->where('status', 'open')->count(),
                'closed'=> QmsCapa::where('company_id', $companyId)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->where('status', 'closed')->count(),
            ];
        }

        // Compliance rate
        $totalPolicies = $stats['policies']['total'];
        $complianceRate = $totalPolicies > 0
            ? round(($stats['policies']['active'] / $totalPolicies) * 100, 1)
            : 0;

        $trainingRate = $stats['trainings']['total'] > 0
            ? round(($stats['trainings']['completed'] / $stats['trainings']['total']) * 100, 1)
            : 0;

        return view('pages.qms.management-review.index', compact('stats', 'capaTrend', 'complianceRate', 'trainingRate'));
    }
}
