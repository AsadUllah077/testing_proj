<?php

namespace App\Http\Controllers;

use App\Models\ComplianceEvent;
use App\Models\ComplianceAlert;
use App\Models\StatutoryBody;
use App\Services\StatutoryComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatutoryComplianceController extends Controller
{
    protected $complianceService;

    public function __construct(StatutoryComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Display the compliance dashboard.
     */
    public function dashboard(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $dashboardData = $this->complianceService->getComplianceDashboard($companyId);

        if ($request->ajax()) {
            return response()->json($dashboardData);
        }

        return view('pages.compliance.dashboard', $dashboardData);
    }

    /**
     * Display compliance calendar.
     */
    public function calendar(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = ComplianceEvent::with('statutoryBody')
            ->where('company_id', $companyId);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->statutory_body_id) {
            $query->where('statutory_body_id', $request->statutory_body_id);
        }

        $events = $query->orderBy('due_date')->get();

        $statutoryBodies = StatutoryBody::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $data = compact('events', 'statutoryBodies');

        if ($request->ajax()) {
            return response()->json([
                'events' => $events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'start' => $event->due_date->format('Y-m-d'),
                        'end' => $event->due_date->format('Y-m-d'),
                        'backgroundColor' => $this->getEventColor($event),
                        'extendedProps' => [
                            'eventId' => $event->id,
                            'status' => $event->status,
                            'amount_due' => $event->amount_due,
                            'statutory_body' => $event->statutoryBody->name ?? 'Unknown Authority',
                            'is_overdue' => $event->isOverdue(),
                            'is_due_soon' => $event->isDueSoon(),
                        ],
                    ];
                }),
            ]);
        }

        return view('pages.compliance.calendar', $data);
    }

    /**
     * Display compliance alerts.
     */
    public function alerts(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = ComplianceAlert::with(['complianceEvent.statutoryBody', 'acknowledgedBy'])
            ->where('company_id', $companyId);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        if ($request->alert_type) {
            $query->where('alert_type', $request->alert_type);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'alerts' => $alerts,
                'pagination' => $alerts->hasPages() ? $alerts->withQueryString()->links()->toHtml() : null,
            ]);
        }

        return view('pages.compliance.alerts', compact('alerts'));
    }

    /**
     * Generate compliance events.
     */
    public function generateEvents(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        DB::transaction(function () use ($companyId) {
            $this->complianceService->generatePayrollComplianceEvents($companyId);
            // $this->complianceService->generateTaxComplianceEvents($companyId);
        });

        return response()->json([
            'success' => true,
            'message' => 'Compliance events generated successfully.',
        ]);
    }

    /**
     * Check for regulatory changes and create alerts.
     */
    public function checkRegulatoryChanges(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $alerts = $this->complianceService->checkRegulatoryChanges($companyId);

        return response()->json([
            'success' => true,
            'message' => count($alerts) . ' new alerts created.',
            'alert_count' => count($alerts),
        ]);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(Request $request, $alertId)
    {
        $alert = ComplianceAlert::where('company_id', loggedCompanyId()['company_id'])
            ->findOrFail($alertId);

        $alert->acknowledge(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged.',
        ]);
    }

    /**
     * Mark compliance event as completed.
     */
    public function completeEvent(Request $request, $eventId)
    {
        $event = ComplianceEvent::where('company_id', loggedCompanyId()['company_id'])
            ->findOrFail($eventId);

        $event->markCompleted();

        return response()->json([
            'success' => true,
            'message' => 'Compliance event marked as completed.',
        ]);
    }

    /**
     * Get compliance statistics.
     */
    public function statistics(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $stats = [
            'events_by_status' => ComplianceEvent::where('company_id', $companyId)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),

            'events_by_body' => ComplianceEvent::with('statutoryBody')
                ->where('company_id', $companyId)
                ->select('statutory_body_id', DB::raw('count(*) as count'))
                ->groupBy('statutory_body_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->statutoryBody->name ?? 'Unknown' => $item->count];
                }),

            'alerts_by_severity' => ComplianceAlert::where('company_id', $companyId)
                ->active()
                ->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity'),

            'monthly_events' => ComplianceEvent::where('company_id', $companyId)
                ->whereYear('created_at', now()->year)
                ->select(DB::raw('EXTRACT(MONTH FROM created_at) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month'),

            'liability_trend' => ComplianceEvent::where('company_id', $companyId)
                ->whereYear('due_date', now()->year)
                ->select(DB::raw('EXTRACT(MONTH FROM due_date) as month'), DB::raw('SUM(amount_due) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month'),

            'penalty_risk' => [
                'high' => ComplianceEvent::where('company_id', $companyId)->overdue()->count(),
                'medium' => ComplianceEvent::where('company_id', $companyId)->dueSoon(3)->count(),
                'low' => ComplianceEvent::where('company_id', $companyId)->dueSoon(14)->count(),
            ]
        ];

        if ($request->ajax()) {
            return response()->json($stats);
        }

        return view('pages.compliance.statistics');
    }

    /**
     * Get event color for calendar display.
     */
    private function getEventColor($event)
    {
        if ($event->isOverdue()) {
            return '#dc3545'; // red
        } elseif ($event->isDueSoon()) {
            return '#ffc107'; // yellow
        } elseif ($event->status === 'completed') {
            return '#28a745'; // green
        } else {
            return '#007bff'; // blue
        }
    }
}
