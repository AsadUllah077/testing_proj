<?php

namespace App\Traits;

use App\Models\ReportAuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait LogsReportAccess
{
    protected function logReportAccess(Request $request, string $reportName, string $action = 'view', ?string $exportFormat = null): void
    {
        $filters = collect($request->except(['_token', '_method', 'page']))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->toArray();

        ReportAuditTrail::create([
            'company_id'    => loggedCompanyId()['company_id'],
            'user_id'       => Auth::id(),
            'report_name'   => $reportName,
            'action'        => $action,
            'filters'       => !empty($filters) ? $filters : null,
            'export_format' => $exportFormat,
            'ip_address'    => $request->ip(),
            'created_at'    => now(),
        ]);
    }
}
