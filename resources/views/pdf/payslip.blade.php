<!DOCTYPE html>
<html
    @if (isset($isDoc) && $isDoc) xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40" @endif>

<head>
    <meta charset="utf-8">
    <title>Salary Slip - {{ $payslip->employee->full_name }}</title>
    @if (isset($isDoc) && $isDoc)
        <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
        <style>
            @page WordSection1 {
                size: 595.3pt 841.9pt;
                /* A4 size */
                margin: 36pt 36pt 36pt 36pt;
                /* 0.5 inch margins */
                mso-header-margin: 36pt;
                mso-footer-margin: 36pt;
                mso-paper-source: 0;
            }

            div.WordSection1 {
                page: WordSection1;
            }
        </style>
    @endif
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px 25px;
            background: #fff;
        }

        /* ========== HEADER ========== */
        .header {
            background: #2E75B6;
            color: #fff;
            text-align: center;
            padding: 18px 20px 6px 20px;
            margin-bottom: 0;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-address {
            font-size: 9px;
            color: #d0e4f5;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        .slip-title-bar {
            background: #1a5a96;
            padding: 7px 0;
            margin: 0 -20px;
            margin-bottom: 0;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #fff;
        }

        /* ========== SECTION HEADERS ========== */
        .section-header {
            background: #2E75B6;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #2E75B6;
        }

        /* ========== TABLES ========== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 6px 10px;
            border: 1px solid #b8cce4;
            font-size: 10px;
            vertical-align: middle;
        }

        th {
            background: #dce6f1;
            font-weight: bold;
            color: #333;
            text-align: left;
        }

        .label-cell {
            font-weight: bold;
            color: #222;
            width: 22%;
            background: #fff;
        }

        .value-cell {
            color: #333;
            width: 28%;
            background: #fff;
        }

        /* ========== EMPLOYEE INFO TABLE ========== */
        .employee-info-table {
            margin-bottom: 14px;
        }

        .employee-info-table td {
            padding: 7px 10px;
        }

        /* ========== EARNINGS TABLE ========== */
        .earnings-table {
            margin-bottom: 14px;
        }

        .earnings-table td {
            padding: 6px 10px;
        }

        .earnings-table .item-label {
            width: 50%;
        }

        .earnings-table .item-amount {
            width: 50%;
        }

        .total-row td {
            font-weight: bold;
            background: #f0f4f8;
            border-top: 2px solid #2E75B6;
        }

        /* ========== DEDUCTIONS TABLE ========== */
        .deductions-table {
            margin-bottom: 14px;
        }

        .deductions-table td {
            padding: 6px 10px;
        }

        .deductions-table .item-label {
            width: 50%;
        }

        .deductions-table .item-amount {
            width: 50%;
        }

        /* ========== NET SALARY BAR ========== */
        .net-salary-bar {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .net-salary-bar .net-label {
            display: table-cell;
            background: #2E75B6;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
            padding: 9px 14px;
            width: 70%;
            vertical-align: middle;
            border: 1px solid #2E75B6;
        }

        .net-salary-bar .net-amount {
            display: table-cell;
            background: #dce6f1;
            color: #2E75B6;
            font-weight: bold;
            font-size: 13px;
            padding: 9px 14px;
            text-align: right;
            width: 30%;
            vertical-align: middle;
            border: 1px solid #b8cce4;
        }

        /* ========== BANK DETAILS TABLE ========== */
        .bank-table {
            margin-bottom: 20px;
        }

        .bank-table td {
            padding: 7px 10px;
        }

        /* ========== YTD SECTION ========== */
        .ytd-section {
            border: 1px solid #b8cce4;
            background: #edf2f9;
            padding: 10px;
            margin-bottom: 14px;
        }

        .ytd-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #2E75B6;
            margin-bottom: 8px;
        }

        .ytd-row {
            display: table;
            width: 100%;
        }

        .ytd-cell {
            display: table-cell;
            text-align: center;
            width: 33.33%;
        }

        .ytd-label {
            font-size: 9px;
            color: #2E75B6;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .ytd-value {
            font-size: 13px;
            font-weight: bold;
            color: #1a5a96;
        }

        /* ========== LEAVE SECTION ========== */
        .leave-section {
            border: 1px solid #b8cce4;
            background: #f0f7f0;
            padding: 10px;
            margin-bottom: 14px;
        }

        .leave-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #065f46;
            margin-bottom: 8px;
        }

        .leave-grid {
            display: table;
            width: 100%;
        }

        .leave-item {
            display: table-cell;
            text-align: center;
            padding: 6px;
            border: 1px solid #b8cce4;
            background: white;
        }

        .leave-type {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .leave-bal {
            font-size: 12px;
            font-weight: bold;
            color: #059669;
        }

        .leave-sub {
            font-size: 8px;
            color: #9ca3af;
        }

        /* ========== SIGNATURES ========== */
        .sig-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .sig-cell {
            display: table-cell;
            width: 33%;
            vertical-align: bottom;
        }

        .sig-cell.left {
            text-align: left;
        }

        .sig-cell.center {
            text-align: center;
        }

        .sig-cell.right {
            text-align: right;
        }

        .sig-line {
            border-top: 1px solid #333;
            display: inline-block;
            width: 140px;
            padding-top: 4px;
            font-size: 9px;
            font-style: italic;
            color: #2E75B6;
        }

        .sig-sub {
            font-size: 8px;
            color: #999;
            font-style: italic;
            margin-top: 2px;
        }

        /* ========== SPACING ========== */
        .spacer {
            height: 14px;
        }

        .footer {
            border-top: 1px solid #b8cce4;
            padding-top: 8px;
            margin-top: 14px;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>

<body>
    @if (isset($isDoc) && $isDoc)
        <div class="WordSection1">
    @endif

    {{-- ========== HEADER ========== --}}
    {{-- ========== HEADER ========== --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 0;">
        <tr>
            <td bgcolor="#2E75B6" align="center" style="background-color: #2E75B6; padding: 18px 20px 6px 20px;">
                <div class="company-name"
                    style="font-size: 20px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; color: #fff; margin-bottom: 4px;">
                    {{ $payslip->employee->company->name ?? config('app.name') }}</div>
                <div class="company-address"
                    style="font-size: 9px; color: #d0e4f5; letter-spacing: 0.3px; margin-bottom: 10px;">
                    {{ $payslip->employee->company->address ?? 'Peshawar Hayatabad Street No. 1 and 2, Plot No. 43-B/44-B, Industrial Road, Phase-1, Hayatabad, Peshawar' }}</div>
            </td>
        </tr>
        <tr>
            <td bgcolor="#1a5a96" align="center" style="background-color: #1a5a96; padding: 7px 0;">
                <div class="slip-title" style="font-size: 14px; font-weight: bold; letter-spacing: 1.5px; color: #fff;">
                    SALARY SLIP</div>
            </td>
        </tr>
    </table>

    <div class="spacer"></div>

    {{-- ========== EMPLOYEE INFORMATION ========== --}}
    <table width="100%" cellpadding="0" cellspacing="0" class="employee-info-table">
        <tr>
            <td colspan="4" bgcolor="#1a5a96"
                style="background-color: #1a5a96; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 10px; text-transform: uppercase; border: 1px solid #1a5a96;">
                Employee Information
            </td>
        </tr>
        <tr>
            <td class="label-cell">Employee Name</td>
            <td class="value-cell">{{ $payslip->employee->full_name }}</td>
            <td class="label-cell">Employee ID</td>
            <td class="value-cell">{{ $payslip->employee->employee_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Designation</td>
            <td class="value-cell">{{ $payslip->employee->designation ?? 'N/A' }}</td>
            <td class="label-cell">Department</td>
            <td class="value-cell">{{ $payslip->employee->department->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Pay Period</td>
            <td class="value-cell">
                {{ $payslip->payRun->period_start->format('d M Y') }} –
                {{ $payslip->payRun->period_end->format('d M Y') }}
            </td>
            <td class="label-cell">Date of Joining</td>
            <td class="value-cell">
                {{ $payslip->employee->date_of_joining ? \Carbon\Carbon::parse($payslip->employee->date_of_joining)->format('d M Y') : 'N/A' }}
            </td>
        </tr>
    </table>

    {{-- ========== EARNINGS ========== --}}
    @php
        $earnings = $payslip->items->filter(
            fn($i) => $i->payItem && in_array($i->payItem->type, ['earning', 'reimbursement']),
        );
        $deductions = $payslip->items->filter(fn($i) => $i->payItem && $i->payItem->type === 'deduction');
        $sym = $payslip->employee->company?->currency?->symbol ?? 'PKR';
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" class="earnings-table">
        <tr>
            <td colspan="2" bgcolor="#1a5a96"
                style="background-color: #1a5a96; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 10px; text-transform: uppercase; border: 1px solid #1a5a96;">
                Earnings
            </td>
        </tr>
        @if ($earnings->count())
            @foreach ($earnings as $item)
                <tr>
                    <td class="item-label">{{ $item->description }}</td>
                    <td class="item-amount">{{ $sym }} {{ numberFormat($item->amount) }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="item-label">Basic Salary</td>
                <td class="item-amount">{{ $sym }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="item-label" bgcolor="#dce6f1" style="background-color: #dce6f1;"><strong>GROSS SALARY</strong>
            </td>
            <td class="item-amount" bgcolor="#dce6f1" style="background-color: #dce6f1;"><strong>{{ $sym }}
                    {{ numberFormat($payslip->gross_earnings) }}</strong></td>
        </tr>
    </table>

    {{-- ========== DEDUCTIONS ========== --}}
    <table width="100%" cellpadding="0" cellspacing="0" class="deductions-table">
        <tr>
            <td colspan="2" bgcolor="#1a5a96"
                style="background-color: #1a5a96; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 10px; text-transform: uppercase; border: 1px solid #1a5a96;">
                Deductions
            </td>
        </tr>
        @if ($deductions->count())
            @foreach ($deductions as $item)
                <tr>
                    <td class="item-label">{{ $item->description }}</td>
                    <td class="item-amount">{{ $sym }} {{ numberFormat($item->amount) }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="item-label">Income Tax</td>
                <td class="item-amount">{{ $sym }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="item-label" bgcolor="#dce6f1" style="background-color: #dce6f1;"><strong>TOTAL
                    DEDUCTIONS</strong></td>
            <td class="item-amount" bgcolor="#dce6f1" style="background-color: #dce6f1;"><strong>{{ $sym }}
                    {{ numberFormat($payslip->total_deductions) }}</strong></td>
        </tr>
    </table>

    {{-- ========== NET SALARY BAR ========== --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
        <tr>
            <td bgcolor="#2E75B6"
                style="background-color: #2E75B6; color: #fff; font-weight: bold; font-size: 12px; padding: 9px 14px; border: 1px solid #2E75B6; width: 70%;">
                NET SALARY (Gross - Total Deductions)
            </td>
            <td bgcolor="#2E75B6"
                style="background-color: #2E75B6; color: #fff; font-weight: bold; font-size: 13px; padding: 9px 14px; text-align: right; border: 1px solid #2E75B6; width: 30%;">
                {{ $sym }} {{ numberFormat($payslip->net_pay) }}
            </td>
        </tr>
    </table>

    {{-- ========== YTD SECTION ========== --}}
    {{-- and for future if needed then remove `&& 1 == 0` from below if condition --}}
    @if ($payslip->ytd_gross > 0 && 1 == 0)
        <table width="100%" cellpadding="0" cellspacing="0"
            style="margin-bottom: 14px; border: 1px solid #b8cce4; background-color: #edf2f9;">
            <tr>
                <td colspan="3"
                    style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1a5a96; padding: 8px 10px 4px 10px;">
                    Year-to-Date Summary ({{ $payslip->payRun->period_end->year }})
                </td>
            </tr>
            <tr>
                <td align="center" style="padding: 4px 10px 10px 10px; width: 33%;">
                    <div style="font-size: 9px; color: #1a5a96; text-transform: uppercase;">YTD Gross</div>
                    <div style="font-size: 13px; font-weight: bold; color: #1a5a96;">{{ $sym }}
                        {{ numberFormat($payslip->ytd_gross) }}</div>
                </td>
                <td align="center" style="padding: 4px 10px 10px 10px; width: 33%;">
                    <div style="font-size: 9px; color: #1a5a96; text-transform: uppercase;">YTD Deductions</div>
                    <div style="font-size: 13px; font-weight: bold; color: #1a5a96;">{{ $sym }}
                        {{ numberFormat($payslip->ytd_deductions) }}</div>
                </td>
                <td align="center" style="padding: 4px 10px 10px 10px; width: 34%;">
                    <div style="font-size: 9px; color: #1a5a96; text-transform: uppercase;">YTD Net Pay</div>
                    <div style="font-size: 13px; font-weight: bold; color: #1a5a96;">{{ $sym }}
                        {{ numberFormat($payslip->ytd_net) }}</div>
                </td>
            </tr>
        </table>
    @endif

    {{-- ========== LEAVE BALANCES ========== --}}
    {{-- and for future if needed then remove `&& 1 == 0` from below if condition --}}
    @if (isset($leaveBalances) && $leaveBalances->count() && 1 == 0)
        <div class="leave-section">
            <div class="leave-title">Leave Balances</div>
            <div class="leave-grid">
                @foreach ($leaveBalances as $lb)
                    <div class="leave-item">
                        <div class="leave-type">{{ $lb->leaveType->name ?? 'Leave' }}</div>
                        <div class="leave-bal">{{ numberFormat($lb->remaining_leaves) }}</div>
                        <div class="leave-sub">of {{ $lb->total_leaves }} days remaining</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========== BANK TRANSFER DETAILS ========== --}}
    @if ($payslip->employee->bank_account_number)
        <table width="100%" cellpadding="0" cellspacing="0" class="bank-table">
            <tr>
                <td colspan="4" bgcolor="#1a5a96"
                    style="background-color: #1a5a96; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 10px; text-transform: uppercase; border: 1px solid #1a5a96;">
                    Bank Transfer Details
                </td>
            </tr>
            <tr>
                <td class="label-cell">Bank Name</td>
                <td class="value-cell">{{ $payslip->employee->bank_name ?? 'N/A' }}</td>
                <td class="label-cell">Account Number</td>
                <td class="value-cell" style="font-weight: bold;">{{ $payslip->employee->bank_account_number }}</td>
            </tr>
            @if ($payslip->employee->bank_iban)
                <tr>
                    <td class="label-cell">IBAN</td>
                    <td class="value-cell" colspan="3">{{ $payslip->employee->bank_iban }}</td>
                </tr>
            @endif
        </table>
    @endif

    {{-- ========== SIGNATURES ========== --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px; border: none;">
        <tr>
            <td style="border: none; text-align: left; width: 33%; vertical-align: bottom;">
                <div style="font-size: 9px; font-weight: bold; color: #1a5a96; margin-bottom: 30px;">Employee Signature
                </div>
                <table width="140" align="left" cellpadding="0" cellspacing="0">
                    <tr>
                        <td
                            style="border-top: 1px solid #999; border-bottom: none; border-left: none; border-right: none; padding: 0;">
                        </td>
                    </tr>
                </table>
            </td>
            <td style="border: none; text-align: center; width: 33%; vertical-align: bottom;">
                <div style="font-size: 9px; font-weight: bold; color: #1a5a96; margin-bottom: 30px;">Company Stamp
                </div>
            </td>
            <td style="border: none; text-align: right; width: 34%; vertical-align: bottom;">
                <div style="font-size: 9px; font-weight: bold; color: #1a5a96; margin-bottom: 30px;">Authorized
                    Signature</div>
                <table width="140" align="right" cellpadding="0" cellspacing="0">
                    <tr>
                        <td
                            style="border-top: 1px solid #999; border-bottom: none; border-left: none; border-right: none; padding: 0;">
                        </td>
                    </tr>
                </table>
                <div style="clear: both;"></div>
                <div style="font-size: 8px; color: #999; font-style: italic; margin-top: 4px;">HR / Finance Manager
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This is a system-generated salary slip. For queries, please contact the HR department.</p>
        <p>Generated on {{ now()->format('d M Y H:i') }}</p>
    </div>

    @if (isset($isDoc) && $isDoc)
        </div>
    @endif
</body>

</html>
