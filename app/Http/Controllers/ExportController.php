<?php

namespace App\Http\Controllers;

use App\Jobs\ExportAttendanceByNameJob;
use App\Jobs\ExportAttendanceDepartmentJob;
use App\Jobs\ExportAttendanceIndividualJob;
use App\Jobs\ExportAttendanceMonthlyJob;
use App\Jobs\ExportBillJob;
use App\Jobs\ExportCreditNotePdf;
use App\Jobs\ExportCustomersJob;
use App\Jobs\ExportEmployeeDepartmentWiseJob;
use App\Jobs\ExportJournalEntriesJob;
use App\Jobs\ExportLeaveDepartmentWiseJob;
use App\Jobs\ExportLogisticsBookingsJob;
use App\Jobs\ExportLogisticsCarriersJob;
use App\Jobs\ExportLogisticsEntitiesJob;
use App\Jobs\ExportLogisticsSuppliersJob;
use App\Jobs\ExportPayrollLiabilityJob;
use App\Jobs\ExportPurchaseOrderJob;
use App\Jobs\ExportSaleJob;
use App\Jobs\ExportSaleTransactionJob;
use App\Jobs\ExportTaxAuthorityLedgerJob;
use App\Jobs\ExportTaxPeriodJob;
use App\Jobs\ExportMassMailerContactsJob;
use App\Jobs\ExportVendorsJob;
use App\Jobs\ExportSurveysJob;
use App\Jobs\ExportWarningsJob;
use App\Jobs\ExportComplaintsJob;
use App\Jobs\ExportResignationsJob;
use App\Jobs\PurchaseOrderReportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /**
     * Initiate export job
     */
    public function export(Request $request)
    {
        try {
            $format = $request->input('format'); // 'xlsx' or 'pdf'
            $search = $request->input('search');

            // Validate format
            if (!in_array($format, ['xlsx', 'pdf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid export format',
                ], 400);
            }

            // Generate unique job ID
            $jobId = Str::uuid()->toString();

            if ($request->type == 'user') {
                // Dispatch export job
                \App\Jobs\ExportUsersJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId,
                    auth('web')->user()->isSuperAdmin() ? null : (loggedCompanyId()['company_id'] ?? null),
                    auth('web')->user()->company->name ?? 'All Companies'
                );
            }

            if ($request->type == 'customer') {
                // Dispatch export job
                ExportCustomersJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId,
                    loggedCompanyId()['company_id'],
                    auth('web')->user()->company->name ?? 'Company'
                );
            }
            if ($request->type == 'vendor') {
                // Dispatch export job
                ExportVendorsJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId,
                    loggedCompanyId()['company_id'],
                    auth('web')->user()->company->name ?? 'Company'
                );
            }

            if ($request->type == 'sale') {
                // Dispatch export job
                ExportSaleJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'sale-transaction') {
                // Dispatch export job
                ExportSaleTransactionJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'credit-note') {
                // Dispatch export job
                ExportCreditNotePdf::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'bill') {
                // Dispatch export job
                ExportBillJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'purchase-order') {
                // Dispatch export job
                ExportPurchaseOrderJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'purchase-order-report') {
                // Dispatch export job
                PurchaseOrderReportJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'journal-entries') {
                // Dispatch export job
                ExportJournalEntriesJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                );
            }

            if ($request->type == 'tax-authority-ledger') {
                // Dispatch export job
                ExportTaxAuthorityLedgerJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    loggedCompanyId()['company_id'],
                    [
                        'name' => $request->name,
                        'type' => $request->type_filter,
                        'status' => $request->status,
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                    ]
                );
            }

            if ($request->type == 'tax-period') {
                // Dispatch export job
                ExportTaxPeriodJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    $request->tax_period_id
                );
            }

            if ($request->type == 'payroll-liability') {
                abort_unless(auth('web')->user()?->hasPermission('payroll.liability.export'), 403);
                // Dispatch export job
                ExportPayrollLiabilityJob::dispatch(
                    loggedCompanyId()['company_id'],
                    auth('web')->id(),
                    $jobId,
                    $format,
                    [
                        'pay_item_id' => $request->pay_item_id,
                        'pay_run_id' => $request->pay_run_id,
                        'date_from' => $request->date_from,
                        'date_to' => $request->date_to,
                        'status' => $request->status,
                        'payment_status' => $request->payment_status,
                        'employee_id' => $request->employee_id,
                    ]
                );
            }

            if ($request->type == 'attendance-department-wise') {
                // Dispatch export job
                ExportAttendanceDepartmentJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                        'status' => $request->status,
                        'employee_id' => $request->employee_id,
                        'search' => $request->search,
                    ]
                );
            }

            if ($request->type == 'attendance-by-name') {
                ExportAttendanceByNameJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                        'status' => $request->status,
                        'search' => $request->search,
                    ]
                );
            }

            if ($request->type == 'attendance-individual') {
                Log::info('Attendance Individual Export Request', [
                    'employee_id' => $request->individual_employee_id,
                    'all_params' => $request->all()
                ]);

                ExportAttendanceIndividualJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                        'status' => $request->status,
                        'employee_id' => $request->individual_employee_id,
                    ]
                );
            }

            if ($request->type == 'attendance-monthly-summary') {
                Log::info('Dispatching ExportAttendanceMonthlyJob', [
                    'duration' => $request->duration,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ]);
                ExportAttendanceMonthlyJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    [
                        'duration' => $request->duration,
                        'date_from' => $request->date_from,
                        'date_to' => $request->date_to,
                    ]
                );
            }

            if ($request->type == 'leave-department-wise') {
                ExportLeaveDepartmentWiseJob::dispatch(
                    auth('web')->user()->company->id,
                    $jobId,
                    $format,
                    [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                        'status' => $request->status,
                        'employee_id' => $request->employee_id,
                        'search' => $request->search,
                        'leave_type_id' => $request->leave_type_id,
                    ]
                );
            }

            if ($request->type == 'employee-department-wise') {
                ExportEmployeeDepartmentWiseJob::dispatch(
                    auth('web')->user()->company->id,
                    $jobId,
                    $format,
                    [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                        'status' => $request->status,
                        'department_id' => $request->department_id,
                        'search' => $request->search,
                    ]
                );
            }

            if ($request->type == 'supplier') {
                // Dispatch export job
                ExportLogisticsSuppliersJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId
                );
            }

            if ($request->type == 'booking') {
                ExportLogisticsBookingsJob::dispatch(
                    $search,
                    $request->input('status'), // status field carries the active tab mode (Air/Ocean/Inland)
                    $format,
                    auth('web')->id(),
                    $jobId
                );
            }

            if ($request->type == 'carrier') {
                ExportLogisticsCarriersJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId
                );
            }

            if ($request->type == 'client') {
                ExportLogisticsEntitiesJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId
                );
            }

            if ($request->type == 'mass_mailer_contacts') {
                ExportMassMailerContactsJob::dispatch(
                    $search,
                    $request->tag_id,
                    $format,
                    auth('web')->id(),
                    $jobId
                );
            }

            if ($request->type == 'hrm-warnings') {
                ExportWarningsJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    auth('web')->user()->company_id,
                    [
                        'q'         => $request->q,
                        'from_date' => $request->from_date,
                        'to_date'   => $request->to_date,
                    ]
                );
            }

            if ($request->type == 'hrm-resignations') {
                ExportResignationsJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    auth('web')->user()->company_id,
                    [
                        'q'         => $request->q,
                        'from_date' => $request->from_date,
                        'to_date'   => $request->to_date,
                    ]
                );
            }

            if ($request->type == 'hrm-surveys') {
                ExportSurveysJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    auth('web')->user()->company_id,
                    [
                        'q'         => $request->q,
                        'status'    => $request->status,
                        'from_date' => $request->from_date,
                        'to_date'   => $request->to_date,
                    ]
                );
            }

            if ($request->type == 'hrm-complaints') {
                ExportComplaintsJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    auth('web')->user()->company_id,
                    [
                        'q'         => $request->q,
                        'from_date' => $request->from_date,
                        'to_date'   => $request->to_date,
                    ]
                );
            }

            if ($request->type == 'product-issue') {
                \App\Jobs\ExportProductIssueJob::dispatch(
                    $search,
                    $format,
                    auth('web')->id(),
                    $jobId,
                    auth('web')->user()->company_id
                );
            }

            if ($request->type == 'progress-sheet') {
                \App\Jobs\ExportProgressSheetJob::dispatch(
                    $search,
                    $request->date,
                    $request->name,
                    $format,
                    auth('web')->id(),
                    $jobId,
                    auth('web')->user()->company_id ?? null
                );
            }

            if ($request->type == 'warehouse-products') {
                \App\Jobs\ExportWarehouseProductsJob::dispatch(
                    $request->all(),
                    $format,
                    auth('web')->id(),
                    $jobId,
                    auth('web')->user()->company_id
                );
            }

            if ($request->type == 'tickets') {
                \App\Jobs\ExportTicketsJob::dispatch(
                    auth('web')->id(),
                    $jobId,
                    $format,
                    auth('web')->user()->company_id,
                    [
                        'q'        => $request->q,
                        'status'   => $request->status,
                        'severity' => $request->severity,
                        'category' => $request->category,
                        'ids'      => $request->ids,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Export started. Please wait...',
                'job_id' => $jobId,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Check export progress
     */
    public function exportProgress(Request $request)
    {
        try {
            $jobId = $request->job_id;

            $progress = Cache::get("export_progress_{$jobId}", [
                'percentage' => 0,
                'message' => 'Waiting to start...',
            ]);

            $result = Cache::get("export_result_{$jobId}");

            return response()->json([
                'success' => true,
                'progress' => $progress,
                'result' => $result,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
