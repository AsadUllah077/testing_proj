<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Services\Account\PaymentBehaviorService;
use App\Services\CustomerAgedReceivableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Customer Aged Receivable (AR Aging) controller — trade customers only.
 * Separate from the legacy ReportController::agedReceivable() flow.
 */
class CustomerAgedReceivableController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;

    public function __construct(
        protected CustomerAgedReceivableService $service
    ) {}

    public function index(Request $request)
    {
        $data = $this->buildData($request);

        // Enrich with AI payment predictions (keyed by customer name for view lookup).
        $behaviorService = app(PaymentBehaviorService::class);
        $predictionsById = $behaviorService->predictAll();

        // Build name→prediction map so the blade can look up by customer name.
        $customerNames = \App\Models\Customer::whereIn('id', array_keys($predictionsById))
            ->pluck('name', 'id');
        $predictionsByName = [];
        foreach ($predictionsById as $id => $pred) {
            $name = $customerNames[$id] ?? null;
            if ($name) {
                $pred['customer_id'] = $id;
                $predictionsByName[$name] = $pred;
            }
        }
        $data['predictions'] = $predictionsByName;
        $data['predictionsById'] = $predictionsById;
        $data['behaviorService'] = $behaviorService;

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Customer Aged Receivable', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.customer-aged-receivable-table', $data)->render(),
                'pagination' => $data['reportData']->hasPages() ? $data['reportData']->links()->toHtml() : '',
                'summary' => [
                    'grand_total' => $data['grandTotal'],
                    'overdue' => $data['grandTotals'][count($data['grandTotals']) - 2] + $data['grandTotals'][count($data['grandTotals']) - 1],
                    'current' => $data['grandTotals'][0],
                ],
            ]);
        }

        $this->logReportAccess($request, 'Customer Aged Receivable', 'view');

        return view('pages.account.reports.customer-aged-receivable', $data);
    }

    public function exportPdf(Request $request)
    {
        $this->logReportAccess($request, 'Customer Aged Receivable', 'export', 'pdf');

        $data = $this->buildData($request, export: true);
        $data['reportData'] = $data['reportData']->getCollection();

        $pdf = Pdf::loadView('pages.account.reports.pdf.customer-aged-receivable-pdf', [
            'title' => 'Customer Aged Receivable Report',
            'reportData' => $data['reportData'],
            'labels' => $data['labels'],
            'grandTotals' => $data['grandTotals'],
            'grandTotal' => $data['grandTotal'],
            'as_of_date' => $data['as_of_date'],
            'from_date' => $data['from_date'],
            'period' => $data['period'],
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('customer-aged-receivable_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $this->logReportAccess($request, 'Customer Aged Receivable', 'export', 'csv');

        $data = $this->buildData($request, export: true);
        $data['reportData'] = $data['reportData']->getCollection();

        $colHeaders = array_merge(['Customer', 'Invoice No', 'Invoice Date'], $data['labels'], ['Total']);
        $colCount = count($colHeaders);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Aged Receivable');
        $row = 1;

        $this->writeTitle($sheet, 'Customer Aged Receivable Report', $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);

        $meta = [['As of Date:', $data['as_of_date']]];
        if ($data['from_date']) {
            $meta[] = ['From:', $data['from_date']];
        }
        $meta[] = ['Generated:', now()->format('d M Y, h:i A')];
        $this->writeMeta($sheet, $meta, $row);

        $this->writeColumnHeaders($sheet, $colHeaders, $row);

        foreach ($data['reportData'] as $entity) {
            $this->writeSectionHeader($sheet, $entity['name'] ?? 'Unknown', $row, $colCount);

            foreach ($entity['rows'] as $item) {
                $vals = array_merge(
                    [$entity['name'] ?? 'Unknown', $item['invoice_no'], $item['invoice_date']],
                    array_map('numberFormat', $item['buckets']),
                    [numberFormat($item['total'])]
                );
                $this->writeDataRow($sheet, $vals, $row, $colCount);
            }

            $vals = array_merge(
                [($entity['name'] ?? 'Unknown') . ' - TOTAL', '', ''],
                array_map('numberFormat', $entity['totals']),
                [numberFormat($entity['total'])]
            );
            $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
            $row++;
        }

        $vals = array_merge(
            ['GRAND TOTAL', '', ''],
            array_map('numberFormat', $data['grandTotals']),
            [numberFormat($data['grandTotal'])]
        );
        $this->writeGrandTotalRow($sheet, $vals, $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        return $this->downloadExcel($spreadsheet, 'customer-aged-receivable_' . now()->format('Ymd_His') . '.xlsx');
    }

    private function buildData(Request $request, bool $export = false): array
    {
        $asOfDate = $request->as_of_date ?? now()->toDateString();
        $fromDate = $request->from_date;

        if ($request->fiscal_year) {
            $fiscalPeriod = \App\Models\AccountingPeriod::find($request->fiscal_year);
            if ($fiscalPeriod) {
                $fromDate = $fiscalPeriod->start_date->format('Y-m-d');
                $asOfDate = $fiscalPeriod->end_date->format('Y-m-d');
            }
        }

        return $this->service->getAgedData(
            $asOfDate,
            $request->periods ?? '4x1',
            $fromDate,
            $export ? 10000 : ($request->per_page ?? 20),
            $request->group_by ?? 'unique_no'
        );
    }
}
