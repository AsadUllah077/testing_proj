<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Sale;
use App\Services\Account\CashFlowForecastService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFlowForecastController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    /**
     * Project cash position for the next N days in weekly buckets.
     *
     * Performance strategy (PostgreSQL):
     *  - Totals  : SQL SUM  — no rows loaded
     *  - Buckets : GROUP BY DATE_TRUNC('week', ...) — ~13 aggregate rows max
     *  - Items   : UNION ALL + LIMIT/OFFSET — exactly PER_PAGE rows per request
     */
    public function index(Request $request, CashFlowForecastService $forecastService)
    {
        $horizon = (int) ($request->horizon ?? 90);
        $horizon = min(max($horizon, 30), 180);

        $today = Carbon::today();

        // ── AI-enriched forecast (historical trends + recurring + confidence) ─
        $forecast    = $forecastService->buildForecast($horizon);
        $buckets     = $forecast['buckets'];
        $totals      = $forecast['totals'];
        $currentCash = $forecast['currentCash'];
        $insights    = $forecast['insights'];

        // ── First page of items (unchanged deterministic AR/AP list) ─────────
        $cutoff  = $today->copy()->addDays($horizon);
        $maxDate = $cutoff->copy()->subDays(30)->format('Y-m-d');

        $sortBy  = $this->resolveSortBy($request->input('sort_by'));
        $sortDir = $this->resolveSortDir($request->input('sort_dir'));
        $perPage = $this->resolvePerPage((int) $request->input('per_page', self::DEFAULT_PER_PAGE));

        ['items' => $firstPage, 'total' => $totalItems] = $this->fetchItems($maxDate, 1, $sortBy, $sortDir, $perPage);

        $totalPages = (int) ceil($totalItems / $perPage);

        return view(
            'pages.account.cash-flow-forecast.index',
            compact('buckets', 'totals', 'currentCash', 'insights', 'firstPage', 'totalItems', 'totalPages', 'horizon', 'today', 'sortBy', 'sortDir', 'perPage')
        );
    }

    /**
     * AJAX endpoint — returns one paginated page of transactions as JSON.
     */
    public function items(Request $request)
    {
        $horizon = (int) ($request->horizon ?? 90);
        $horizon = min(max($horizon, 30), 180);
        $page    = max(1, (int) ($request->page ?? 1));

        $today   = Carbon::today();
        $cutoff  = $today->copy()->addDays($horizon);
        $maxDate = $cutoff->copy()->subDays(30)->format('Y-m-d');

        $sortBy  = $this->resolveSortBy($request->input('sort_by'));
        $sortDir = $this->resolveSortDir($request->input('sort_dir'));
        $perPage = $this->resolvePerPage((int) $request->input('per_page', self::DEFAULT_PER_PAGE));

        ['items' => $items, 'total' => $total] = $this->fetchItems($maxDate, $page, $sortBy, $sortDir, $perPage);

        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'has_more'    => $page < $totalPages,
        ]);
    }

    /**
     * Paginate AR + AP items via a DB-level UNION ALL query (PostgreSQL).
     *
     * - COUNT  uses Eloquent so BelongsToCompany scope is applied automatically.
     * - SELECT uses raw SQL for LIMIT/OFFSET; company_id is injected manually
     *   to replicate the global scope.
     * - "date" is double-quoted because it is a reserved word in PostgreSQL.
     * - LIMIT/OFFSET are interpolated (not bound) to avoid PostgreSQL PDO
     *   parameter-type issues with LIMIT.
     *
     * @return array{items: array, total: int}
     */
    private function fetchItems(string $maxDate, int $page, string $sortBy = 'date', string $sortDir = 'asc', int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $offset    = ($page - 1) * $perPage;
        $companyId = auth('web')->user()?->company_id;
        $limit     = $perPage;

        // Counts go through Eloquent — BelongsToCompany scope applied automatically
        $total = Sale::where('approval_status', 'approved')->where('remaining_amount', '>', 0)->where('date', '<=', $maxDate)->count()
            + Bill::where('approval_status', 'approved')->where('remaining_amount', '>', 0)->where('date', '<=', $maxDate)->count();

        $cWhere  = $companyId ? 'AND company_id = ?' : '';
        $cParam  = $companyId ? [$companyId] : [];

        // Map sort_by to the alias used in the outer SELECT
        $colMap  = ['date' => 'issue_date', 'ref' => 'ref', 'amount' => 'amount'];
        $orderBy = ($colMap[$sortBy] ?? 'issue_date') . ' ' . strtoupper($sortDir);

        // Alias "date" as issue_date to avoid the reserved-word in the outer ORDER BY
        $sql = "SELECT type, issue_date, amount, ref
                FROM (
                    SELECT 'inflow'  AS type,
                           \"date\"  AS issue_date,
                           remaining_amount AS amount,
                           COALESCE(invoice_no, unique_no) AS ref
                    FROM sales
                    WHERE remaining_amount > 0
                      AND approval_status = 'approved'
                      AND \"date\" <= ?
                      {$cWhere}
                      AND deleted_at IS NULL
                    UNION ALL
                    SELECT 'outflow' AS type,
                           \"date\"  AS issue_date,
                           remaining_amount AS amount,
                           COALESCE(invoice_no, unique_no) AS ref
                    FROM bills
                    WHERE remaining_amount > 0
                      AND approval_status = 'approved'
                      AND \"date\" <= ?
                      {$cWhere}
                      AND deleted_at IS NULL
                ) combined
                ORDER BY {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        $rows = DB::select(
            $sql,
            array_merge([$maxDate], $cParam, [$maxDate], $cParam)
        );

        $items = collect($rows)->map(fn($row) => [
            'ref'      => $row->ref,
            'type'     => $row->type,
            'due_date' => Carbon::parse($row->issue_date)->addDays(30)->format('d M Y'),
            'amount'   => (float) $row->amount,
        ])->values()->all();

        return ['items' => $items, 'total' => $total];
    }

    private function resolveSortBy(?string $value): string
    {
        return in_array($value, ['date', 'ref', 'amount'], true) ? $value : 'date';
    }

    private function resolveSortDir(?string $value): string
    {
        return strtolower((string) $value) === 'desc' ? 'desc' : 'asc';
    }

    private function resolvePerPage(int $value): int
    {
        return in_array($value, [15, 25, 50, 100, 200, 300, 400, 500, 1000], true) ? $value : self::DEFAULT_PER_PAGE;
    }
}
