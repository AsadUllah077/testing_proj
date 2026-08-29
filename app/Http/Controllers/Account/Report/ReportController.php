<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Services\AgedReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use \App\Traits\LogsReportAccess, \App\Traits\ExportsToExcel;

    public function __construct(
        protected AgedReportService $agedReportService
    ) {}

    /* =========================
     * UI VIEWS (UNCHANGED)
     * ========================= */

    public function agedPayable(Request $request)
    {
        // Fiscal year filter: use period dates
        $asOfDate = $request->as_of_date ?? now()->toDateString();
        $fromDate = $request->from_date;
        if ($request->fiscal_year) {
            $fiscalPeriod = \App\Models\AccountingPeriod::find($request->fiscal_year);
            if ($fiscalPeriod) {
                $fromDate = $fiscalPeriod->start_date->format('Y-m-d');
                $asOfDate = $fiscalPeriod->end_date->format('Y-m-d');
            }
        }

        $data = $this->agedReportService->getAgedData(
            'payable',
            $asOfDate,
            $request->periods ?? '4x1',
            $fromDate,
            $request->boolean('include_statutory'),
            $request->per_page ?? 20,
            $request->group_by ?? 'unique_no'
        );

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Aged Payable', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.aged-payable-table', $data)->render(),
                'pagination' => $data['reportData']->hasPages() ? $data['reportData']->links()->toHtml() : '',
                'summary' => [
                    'grand_total' => $data['grandTotal'],
                    'overdue' => $data['grandTotals'][count($data['grandTotals']) - 2] + $data['grandTotals'][count($data['grandTotals']) - 1],
                    'current' => $data['grandTotals'][0],
                ],
            ]);
        }

        $this->logReportAccess($request, 'Aged Payable', 'view');

        return view('pages.account.reports.aged-payable', $data);
    }

    public function agedReceivable(Request $request)
    {
        // Fiscal year filter: use period dates
        $asOfDate = $request->as_of_date ?? now()->toDateString();
        $fromDate = $request->from_date;
        if ($request->fiscal_year) {
            $fiscalPeriod = \App\Models\AccountingPeriod::find($request->fiscal_year);
            if ($fiscalPeriod) {
                $fromDate = $fiscalPeriod->start_date->format('Y-m-d');
                $asOfDate = $fiscalPeriod->end_date->format('Y-m-d');
            }
        }

        $data = $this->agedReportService->getAgedData(
            'receivable',
            $asOfDate,
            $request->periods ?? '4x1',
            $fromDate,
            $request->boolean('include_statutory'),
            $request->per_page ?? 20,
            $request->group_by ?? 'unique_no'
        );

        if ($request->ajax()) {
            $this->logReportAccess($request, 'Aged Receivable', 'filter');

            return response()->json([
                'html' => view('pages.account.reports.partials.aged-receivable-table', $data)->render(),
                'pagination' => $data['reportData']->hasPages() ? $data['reportData']->links()->toHtml() : '',
                'summary' => [
                    'grand_total' => $data['grandTotal'],
                    'overdue' => $data['grandTotals'][count($data['grandTotals']) - 2] + $data['grandTotals'][count($data['grandTotals']) - 1],
                    'current' => $data['grandTotals'][0],
                ],
            ]);
        }

        $this->logReportAccess($request, 'Aged Receivable', 'view');

        return view('pages.account.reports.aged-receivable', $data);
    }

    /* =========================
     * EXCEL EXPORTS
     * ========================= */

    public function exportAgedPayableToCsv(Request $request)
    {
        $this->logReportAccess($request, 'Aged Payable', 'export', 'csv');
        return $this->buildAgedExcel('payable', $request, 'Aged Payables Report', 'Vendor/Employee');
    }

    public function exportAgedReceivableToCsv(Request $request)
    {
        $this->logReportAccess($request, 'Aged Receivable', 'export', 'csv');
        return $this->buildAgedExcel('receivable', $request, 'Aged Receivables Report', 'Customer');
    }

    private function buildAgedExcel(string $type, Request $request, string $title, string $entityLabel)
    {
        $data = $this->getExportData($type, $request);

        $colHeaders = array_merge([$entityLabel, 'Invoice No', 'Invoice Date'], $data['labels'], ['Total']);
        $colCount = count($colHeaders);

        $spreadsheet = $this->createReportSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $row = 1;

        $this->writeTitle($sheet, $title, $row, $colCount);
        $this->writeCompanyRow($sheet, $row, $colCount);

        $meta = [['As of Date:', $data['as_of_date']]];
        if ($data['from_date']) {
            $meta[] = ['From:', $data['from_date']];
        }
        $meta[] = ['Generated:', now()->format('d M Y, h:i A')];
        $this->writeMeta($sheet, $meta, $row);

        $this->writeColumnHeaders($sheet, $colHeaders, $row);

        foreach ($data['reportData'] as $entity) {
            // Entity section header
            $this->writeSectionHeader($sheet, $entity['name'] ?? 'Unknown', $row, $colCount);

            foreach ($entity['rows'] as $item) {
                $vals = array_merge(
                    [$entity['name'] ?? 'Unknown', $item['invoice_no'], $item['invoice_date']],
                    array_map('numberFormat', $item['buckets']),
                    [numberFormat($item['total'])]
                );
                $this->writeDataRow($sheet, $vals, $row, $colCount);
            }

            // Entity subtotal
            $vals = array_merge(
                [($entity['name'] ?? 'Unknown') . ' - TOTAL', '', ''],
                array_map('numberFormat', $entity['totals']),
                [numberFormat($entity['total'])]
            );
            $this->writeSubtotalRow($sheet, $vals, $row, $colCount);
            $row++;
        }

        // Grand total
        $vals = array_merge(
            ['GRAND TOTAL', '', ''],
            array_map('numberFormat', $data['grandTotals']),
            [numberFormat($data['grandTotal'])]
        );
        $this->writeGrandTotalRow($sheet, $vals, $row, $colCount);

        $this->autoSizeColumns($sheet, $colCount);

        $filename = ($type === 'payable' ? 'aged-payables-report_' : 'aged-receivables-report_') . now()->format('Ymd_His') . '.xlsx';
        return $this->downloadExcel($spreadsheet, $filename);
    }

    /* =========================
     * PDF EXPORTS
     * ========================= */

    public function exportAgedPayableToPdf(Request $request)
    {
        $this->logReportAccess($request, 'Aged Payable', 'export', 'pdf');

        $data = $this->getExportData('payable', $request);
        $filename = 'aged-payables-report_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('pages.account.reports.pdf.aged-payable-pdf', [
            'title'        => 'Aged Payables Report',
            'reportData'   => $data['reportData'],
            'labels'       => $data['labels'],
            'grandTotals'  => $data['grandTotals'],
            'grandTotal'   => $data['grandTotal'],
            'as_of_date'   => $data['as_of_date'],
            'from_date'    => $data['from_date'],
            'period'       => $data['period'],
        ]);

        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream($filename);
    }

    public function exportAgedReceivableToPdf(Request $request)
    {
        $this->logReportAccess($request, 'Aged Receivable', 'export', 'pdf');

        $data = $this->getExportData('receivable', $request);
        $filename = 'aged-receivables-report_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('pages.account.reports.pdf.aged-receivable-pdf', [
            'title'        => 'Aged Receivables Report',
            'reportData'   => $data['reportData'],
            'labels'       => $data['labels'],
            'grandTotals'  => $data['grandTotals'],
            'grandTotal'   => $data['grandTotal'],
            'as_of_date'   => $data['as_of_date'],
            'from_date'    => $data['from_date'],
            'period'       => $data['period'],
        ]);

        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream($filename);
    }

    /* =========================
     * SHARED EXPORT HELPER
     * ========================= */

    private function getExportData(string $type, Request $request): array
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

        $data = $this->agedReportService->getAgedData(
            $type,
            $asOfDate,
            $request->periods ?? '4x1',
            $fromDate,
            $request->boolean('include_statutory'),
            10000, // Export all data (no pagination limit)
            $request->group_by ?? 'unique_no'
        );

        // Convert paginator to plain collection for exports
        $data['reportData'] = $data['reportData']->getCollection();

        return $data;
    }
}
