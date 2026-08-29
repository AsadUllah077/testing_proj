<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsBankCharge;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsGoodsDeclaration;
use App\Models\LogisticsLetterOfCredit;
use App\Models\LogisticsShipment;
use App\Models\LogisticsTradeDocument;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function analytics()
    {
        // ── Monthly Bookings Trend (last 12 months) ──────────────────────
        $bookingsByMonth = LogisticsBookingV2::select(
            DB::raw("TO_CHAR(created_at, 'Mon') as month_label"),
            DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month_key"),
            DB::raw('count(*) as total'),
            DB::raw("sum(case when booking_type = 'import' then 1 else 0 end) as imports"),
            DB::raw("sum(case when booking_type = 'export' then 1 else 0 end) as exports")
        )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get();

        $monthlyBookings = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $label = now()->subMonths($i)->format('M');
            $row = $bookingsByMonth->firstWhere('month_key', $key);
            $monthlyBookings[] = [
                'label' => $label,
                'total' => $row ? (int) $row->total : 0,
                'imports' => $row ? (int) $row->imports : 0,
                'exports' => $row ? (int) $row->exports : 0,
            ];
        }
        $maxMonthly = max(array_column($monthlyBookings, 'total') ?: [1]);

        // ── Freight Mode Split ────────────────────────────────────────────
        $freightSplit = LogisticsBookingV2::select('freight_mode', DB::raw('count(*) as total'))
            ->whereNotNull('freight_mode')
            ->groupBy('freight_mode')
            ->pluck('total', 'freight_mode')
            ->toArray();

        $freightTotal = array_sum($freightSplit) ?: 1;
        $freightColors = ['sea' => '#0078C8', 'air' => '#8b5cf6', 'road' => '#f59e0b', 'rail' => '#10b981'];
        $freightData = [];
        foreach ($freightSplit as $mode => $count) {
            $freightData[] = [
                'mode' => ucfirst($mode),
                'count' => $count,
                'pct' => round($count / $freightTotal * 100),
                'color' => $freightColors[$mode] ?? '#94a3b8',
            ];
        }
        usort($freightData, fn ($a, $b) => $b['count'] <=> $a['count']);

        // ── Top Routes ────────────────────────────────────────────────────
        $topRoutes = LogisticsBookingV2::select(
            'origin_port',
            'destination_port',
            DB::raw('count(*) as total'),
            DB::raw("sum(case when booking_type = 'import' then 1 else 0 end) as imports"),
            DB::raw("sum(case when booking_type = 'export' then 1 else 0 end) as exports"),
            // DB::raw("sum(NULLIF(cargo_value, '')::numeric) as total_value")
        )
            ->whereNotNull('origin_port')->whereNotNull('destination_port')
            ->groupBy('origin_port', 'destination_port')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // ── GD Status Breakdown ───────────────────────────────────────────
        $gdStatus = LogisticsGoodsDeclaration::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $gdTotal = array_sum($gdStatus) ?: 1;
        $gdColors = ['Draft' => '#94a3b8', 'Filed' => '#f59e0b', 'Cleared' => '#22c55e', 'Rejected' => '#ef4444'];
        $gdData = [];
        foreach ($gdStatus as $s => $c) {
            $gdData[] = ['status' => $s, 'count' => $c, 'pct' => round($c / $gdTotal * 100), 'color' => $gdColors[$s] ?? '#94a3b8'];
        }

        // ── GD Monthly Duty (last 6 months) ──────────────────────────────
        $dutyByMonth = LogisticsGoodsDeclaration::select(
            DB::raw("TO_CHAR(filed_date, 'Mon') as month_label"),
            DB::raw("TO_CHAR(filed_date, 'YYYY-MM') as month_key"),
            DB::raw('sum(total_duty) as duty'),
            DB::raw('sum(customs_duty) as customs'),
            DB::raw('sum(sales_tax) as st'),
            DB::raw('sum(income_tax) as it')
        )
            ->whereNotNull('filed_date')
            ->where('filed_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get();

        $monthlyDuty = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $lbl = now()->subMonths($i)->format('M');
            $row = $dutyByMonth->firstWhere('month_key', $key);
            $monthlyDuty[] = [
                'label' => $lbl,
                'duty' => $row ? (float) $row->duty : 0,
                'customs' => $row ? (float) $row->customs : 0,
                'st' => $row ? (float) $row->st : 0,
                'it' => $row ? (float) $row->it : 0,
            ];
        }
        $maxDuty = max(array_column($monthlyDuty, 'duty') ?: [1]);

        // ── LC Exposure by Status ─────────────────────────────────────────
        $lcByStatus = LogisticsLetterOfCredit::where('record_type', 'lc')
            ->select('status', DB::raw('count(*) as cnt'), DB::raw('sum(amount) as total'))
            ->groupBy('status')
            ->get();

        $lcStatusColors = [
            'Draft' => '#94a3b8',
            'Active' => '#0078C8',
            'Expiring Soon' => '#f59e0b',
            'Settled' => '#22c55e',
            'Expired' => '#ef4444',
            'Cancelled' => '#d1d5db',
        ];

        // ── LC Monthly Opened (last 6 months) ────────────────────────────
        $lcByMonth = LogisticsLetterOfCredit::where('record_type', 'lc')
            ->select(
                DB::raw("TO_CHAR(issue_date, 'Mon') as month_label"),
                DB::raw("TO_CHAR(issue_date, 'YYYY-MM') as month_key"),
                DB::raw('count(*) as cnt'),
                DB::raw('sum(amount) as total')
            )
            ->whereNotNull('issue_date')
            ->where('issue_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get();

        $monthlyLc = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $lbl = now()->subMonths($i)->format('M');
            $row = $lcByMonth->firstWhere('month_key', $key);
            $monthlyLc[] = [
                'label' => $lbl,
                'count' => $row ? (int) $row->cnt : 0,
                'amount' => $row ? (float) $row->total : 0,
            ];
        }

        // ── Bank Charges by Type ──────────────────────────────────────────
        $chargesByType = LogisticsBankCharge::select(
            'charge_type',
            DB::raw('count(*) as cnt'),
            DB::raw('sum(amount_pkr) as total_pkr')
        )
            ->groupBy('charge_type')
            ->orderByDesc('total_pkr')
            ->limit(6)
            ->get();

        $totalChargesPkr = $chargesByType->sum('total_pkr') ?: 1;

        // ── Summary KPIs ──────────────────────────────────────────────────
        $kpis = [
            'total_bookings' => LogisticsBookingV2::count(),
            'total_imports' => LogisticsBookingV2::where('booking_type', 'import')->count(),
            'total_exports' => LogisticsBookingV2::where('booking_type', 'export')->count(),
            'total_gds' => LogisticsGoodsDeclaration::count(),
            'cleared_gds' => LogisticsGoodsDeclaration::where('status', 'Cleared')->count(),
            'total_lcs' => LogisticsLetterOfCredit::where('record_type', 'lc')->count(),
            'active_lcs' => LogisticsLetterOfCredit::where('record_type', 'lc')->where('status', 'Active')->count(),
            'total_lc_value' => (float) LogisticsLetterOfCredit::where('record_type', 'lc')->whereIn('status', ['Active', 'Expiring Soon'])->sum('amount'),
            'total_duty_pkr' => (float) LogisticsGoodsDeclaration::sum('total_duty'),
            'total_charges_pkr' => (float) LogisticsBankCharge::sum('amount_pkr'),
            'exam_queue' => LogisticsGoodsDeclaration::whereIn('exam_status', ['doc_check', 'physical_exam'])->count(),
        ];

        return view('pages.products.logistics.analytics', compact(
            'monthlyBookings',
            'maxMonthly',
            'freightData',
            'freightTotal',
            'topRoutes',
            'gdData',
            'gdTotal',
            'monthlyDuty',
            'maxDuty',
            'lcByStatus',
            'lcStatusColors',
            'monthlyLc',
            'chargesByType',
            'totalChargesPkr',
            'kpis'
        ));
    }

    public function index()
    {
        // ── Stat Cards ──────────────────────────────────────────────────
        $activeShipments = LogisticsShipment::whereNotIn('status', ['Delivered'])->count();
        $delayedShipments = LogisticsShipment::where('status', 'Delayed')->count();

        $inTransitValue = LogisticsShipment::where('status', 'In-Transit')
            ->whereNotNull('cargo_value')
            ->sum('cargo_value');

        // Average lead time: days between ETD and ATA (for delivered/arrived shipments)
        $avgLeadTime = LogisticsShipment::whereNotNull('etd')
            ->whereNotNull('ata')
            ->selectRaw('AVG(EXTRACT(DAY FROM (ata::timestamp - etd::timestamp))) as avg_days')
            ->value('avg_days');
        $avgLeadTime = $avgLeadTime ? round($avgLeadTime, 1) : null;

        // Pending customs = GDs in Draft or Filed status
        $pendingCustoms = LogisticsGoodsDeclaration::whereIn('status', ['Draft', 'Filed'])->count();

        // Compare with last week for trend
        $lastWeekActive = LogisticsShipment::whereNotIn('status', ['Delivered'])
            ->where('created_at', '<', now()->subWeek())
            ->count();
        $activeChange = $lastWeekActive > 0
            ? round((($activeShipments - $lastWeekActive) / $lastWeekActive) * 100, 1)
            : 0;

        // ── Status Distribution ──────────────────────────────────────────
        $statusCounts = LogisticsShipment::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totalShipments = array_sum($statusCounts);

        $statusDistribution = [];
        $statusColors = [
            'In-Transit' => '#137fec',
            'Booked' => '#8b5cf6',
            'Arrived' => '#10b981',
            'Cleared' => '#06b6d4',
            'Delivered' => '#22c55e',
            'Delayed' => '#ef4444',
        ];
        foreach ($statusCounts as $status => $count) {
            $pct = $totalShipments > 0 ? round(($count / $totalShipments) * 100) : 0;
            $statusDistribution[] = [
                'status' => $status,
                'count' => $count,
                'pct' => $pct,
                'color' => $statusColors[$status] ?? '#94a3b8',
            ];
        }
        usort($statusDistribution, fn ($a, $b) => $b['count'] <=> $a['count']);

        // ── Carrier Performance (on-time %) ─────────────────────────────
        // On-time = ATA <= ETA
        $carrierStats = LogisticsShipment::select(
            'carrier',
            DB::raw('count(*) as total'),
            DB::raw('sum(case when ata <= eta and ata is not null and eta is not null then 1 else 0 end) as on_time')
        )
            ->whereNotNull('carrier')
            ->groupBy('carrier')
            ->having(DB::raw('count(*)'), '>=', 1)
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $pct = $row->total > 0 ? round(($row->on_time / $row->total) * 100) : 0;

                return [
                    'carrier' => $row->carrier,
                    'pct' => $pct,
                    'total' => $row->total,
                    'color' => $pct >= 90 ? '#137fec' : ($pct >= 75 ? '#f59e0b' : '#ef4444'),
                ];
            })->toArray();

        // ── Action Required ──────────────────────────────────────────────
        $actions = [];

        // Missing trade documents
        $missingDocs = LogisticsTradeDocument::where('status', 'Missing')
            ->with('booking')
            ->latest()
            ->limit(3)
            ->get();
        foreach ($missingDocs as $doc) {
            $actions[] = [
                'type' => 'doc',
                'title' => 'Upload '.$doc->document_type,
                'desc' => 'Booking: '.($doc->booking->booking_number ?? '—').' · '.($doc->required_for ?? ''),
                'url' => route('logistics.manage-booking.v2.documents'),
                'color' => 'blue',
            ];
        }

        // Pending confirm bookings
        $pendingBookings = LogisticsBookingV2::where('status', 'Pending Confirm')
            ->latest()
            ->limit(2)
            ->get();
        foreach ($pendingBookings as $booking) {
            $actions[] = [
                'type' => 'booking',
                'title' => 'Confirm Booking '.$booking->booking_number,
                'desc' => $booking->shipper.' → '.$booking->consignee,
                'url' => route('logistics.manage-booking.v2.show', $booking->id),
                'color' => 'amber',
            ];
        }

        // Delayed shipments
        $delayedItems = LogisticsShipment::where('status', 'Delayed')
            ->latest()
            ->limit(2)
            ->get();
        foreach ($delayedItems as $sh) {
            $actions[] = [
                'type' => 'delay',
                'title' => 'Delayed: '.$sh->shipment_number,
                'desc' => ($sh->origin_port ?? '?').' → '.($sh->destination_port ?? '?').($sh->delay_reason ? ' · '.$sh->delay_reason : ''),
                'url' => route('logistics.shipments.delayed'),
                'color' => 'red',
            ];
        }

        $actions = array_slice($actions, 0, 5);

        // ── Recent Activity ──────────────────────────────────────────────
        // Pull latest bookings + shipments as activity feed
        $recentBookings = LogisticsBookingV2::select(
            'id',
            'booking_number',
            'status',
            'created_at',
            DB::raw("'booking' as type")
        )->latest()->limit(5)->get();

        $recentShipments = LogisticsShipment::select(
            'id',
            'shipment_number as booking_number',
            'status',
            'created_at',
            DB::raw("'shipment' as type")
        )->latest()->limit(5)->get();

        $recentActivity = $recentBookings->concat($recentShipments)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        // ── LC & GD Summary ──────────────────────────────────────────────
        $activeLCs = LogisticsLetterOfCredit::where('status', 'Active')->count();
        $totalLcValue = LogisticsLetterOfCredit::where('status', 'Active')->sum('amount');
        $draftGDs = LogisticsGoodsDeclaration::where('status', 'Draft')->count();
        $totalDuty = LogisticsGoodsDeclaration::whereNotNull('total_duty')->sum('total_duty');

        return view('pages.products.logistics.dashboard', compact(
            'activeShipments',
            'delayedShipments',
            'inTransitValue',
            'avgLeadTime',
            'pendingCustoms',
            'activeChange',
            'totalShipments',
            'statusDistribution',
            'carrierStats',
            'actions',
            'recentActivity',
            'activeLCs',
            'totalLcValue',
            'draftGDs',
            'totalDuty'
        ));
    }
}
