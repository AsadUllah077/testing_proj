<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gate Pass Print - {{ $gatePass->gate_pass_id }} - {{ $printCopy ?? 'Customer Copy' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        @media print {
            body { background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; size: A4; }
            .table-wrapper { overflow: visible !important; }
            table { min-width: unset !important; width: 100% !important; font-size: 0.75rem !important; }
            thead th { padding: 0.4rem 0.5rem !important; font-size: 0.65rem !important; }
            tbody td { padding: 0.4rem 0.5rem !important; font-size: 0.75rem !important; }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            color: #000;
            padding: 2rem;
            max-width: 64rem;
            margin: 0 auto;
            font-size: 0.875rem;
        }

        /* HEADER */
        .header {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .header-title { font-size: 1.5rem; font-weight: 700; color: #111827; }
        .header-company { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }
        .header-right { text-align: right; }
        .header-id { font-size: 1.25rem; font-weight: 700; font-family: monospace; color: #111827; }
        .header-date { font-size: 0.875rem; color: #4b5563; margin-top: 0.25rem; }
        .copy-label {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.65rem;
            border: 1px solid #111827;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #111827;
        }
        .invoice-label {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.65rem;
            border: 1px solid #111827;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #111827;
        }

        /* FIRST BOX - ENHANCED QC APPROVAL SECTION */
        .info-box {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .info-col { flex: 1; min-width: 200px; }
        .section-heading {
            font-size: 0.875rem;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            color: #374151;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
            font-size: 0.875rem;
        }
        .info-label { color: #6b7280; display: block; font-size: 0.75rem; margin-bottom: 0.15rem; }
        .info-value { font-weight: 500; }

        /* FULL QC APPROVAL DETAILS */
        .qc-full-details {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-left: 3px solid #0078C8;
        }
        .qc-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .qc-approved-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .qc-pending-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .qc-info-block {
            font-size: 0.75rem;
            line-height: 1.4;
            margin-top: 0.5rem;
            color: #1e293b;
        }
        .qc-info-block strong {
            color: #0f172a;
            font-weight: 600;
        }
        .qc-remarks-box {
            background: white;
            padding: 0.5rem 0.6rem;
            border-radius: 0.375rem;
            border: 1px solid #e2e8f0;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #334155;
        }
        .qc-meta-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 0.5rem;
            gap: 0.5rem;
            font-size: 0.7rem;
            color: #475569;
        }

        /* APPROVALS (SECURITY) */
        .approval-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; font-size: 0.875rem; }
        .approval-label { color: #6b7280; display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem; }
        .approval-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 0.25rem;
            margin-top: 0.25rem;
            flex-wrap: wrap;
        }
        .text-green { color: #15803d; font-weight: 500; }
        .text-gray-pending { color: #9ca3af; }
        .remarks-text { font-size: 0.75rem; color: #4b5563; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 20rem; }
        .incharge-text { font-size: 0.75rem; color: #4b5563; }

        /* TABLE SECTION */
        .table-section { margin-bottom: 1.5rem; }
        .table-heading { font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937; }

        .table-wrapper {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            overflow-x: auto;
            width: 100%;
        }
        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            table-layout: auto;
        }
        thead { background-color: #f3f4f6; }
        thead th {
            padding: 0.625rem 0.75rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: #0078C8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        tbody tr { border-top: 1px solid #e5e7eb; }
        tbody td { padding: 0.625rem 0.75rem; font-size: 0.8125rem; }

        .td-num  { font-size: 0.75rem; color: #6b7280; white-space: nowrap; }
        .td-mono { font-family: monospace; color: #111827; white-space: nowrap; }
        .td-text { color: #1f2937; white-space: nowrap; }
        .td-mono-gray { font-family: monospace; color: #9ca3af; text-decoration: line-through; white-space: nowrap; }
        .td-text-gray { color: #9ca3af; text-decoration: line-through; white-space: nowrap; }

        .spec-col { display: flex; flex-direction: column; gap: 0.125rem; }
        .spec-item { font-size: 0.6875rem; color: #4b5563; white-space: nowrap; }
        .spec-key { font-weight: 500; color: #1f2937; text-transform: capitalize; }
        .spec-item-gray { font-size: 0.6875rem; white-space: nowrap; }
        .spec-key-gray { text-transform: capitalize; }

        .text-active { font-weight: 600; color: #15803d; white-space: nowrap; }
        .returned-badge { color: #ea580c; font-weight: 600; white-space: nowrap; }
        .returned-date { font-size: 0.625rem; color: #9ca3af; margin-top: 0.125rem; white-space: nowrap; }
        .tr-returned { background-color: #f9fafb; }

        tfoot tr td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #1f2937;
            text-align: right;
            background-color: #f3f4f6;
            border-top: 1px solid #d1d5db;
        }
        .text-orange { color: #ea580c; }

        /* NOTES & SIGNATURE */
        .footer-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        .notes-col { max-width: 28rem; width: 50%; }
        .notes-heading { font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; color: #374151; }
        .notes-box {
            font-size: 0.875rem;
            font-style: italic;
            color: #4b5563;
            border: 1px solid #d1d5db;
            padding: 0.75rem;
            border-radius: 0.375rem;
            background-color: #f9fafb;
        }
        .sig-col { width: 33%; text-align: right; }
        .sig-line { margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid #1f2937; width: 100%; display: inline-block; }
        .sig-label { font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #1f2937; }
        .sig-meta { margin-top: 0.25rem; font-size: 0.8rem; color: #374151; }
        .sig-invoice { margin-top: 0.35rem; font-size: 0.78rem; color: #4b5563; }

        /* extra qc details responsiveness */
        @media (max-width: 640px) {
            .info-box { flex-direction: column; }
            .qc-meta-row { flex-direction: column; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- HEADER / LOGO -->
    <div class="header">
        <div>
            <h1 class="header-title">GATE PASS</h1>
            <p class="header-company">{{ auth()->user()->company->name ?? 'NexGen Logistics' }}</p>
            @if($gatePass->invoice_no)
                <p class="invoice-label">Invoice No: {{ $gatePass->invoice_no }}</p>
            @endif
        </div>
        <div class="header-right">
            <h2 class="header-id">{{ $gatePass->gate_pass_id }}</h2>
            <p class="header-date">Date: {{ now()->format('d M Y, H:i') }}</p>
            <p class="copy-label">{{ $printCopy ?? 'Customer Copy' }}</p>
        </div>
    </div>

    <!-- FIRST BOX: FULL QC APPROVAL + Information + Security -->
    <div class="info-box">

        <!-- Information Column -->
        <div class="info-col">
            <h3 class="section-heading">Information</h3>
            <div class="info-grid">
                <div>
                    <span class="info-label">Source Location</span>
                    <span class="info-value">{{ $gatePass->warehouseLocation->name ?? 'Main Warehouse' }}</span>
                </div>
                <div>
                    <span class="info-label">Departure Date</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($gatePass->departure_date)->format('d M Y, H:i') }}</span>
                </div>
                <div>
                    <span class="info-label">Prepared By</span>
                    <span class="info-value">{{ $gatePass->preparedBy?->full_name ?? $gatePass->prepared_by_name ?? 'System' }}</span>
                </div>
                <div>
                    <span class="info-label">Created At</span>
                    <span class="info-value">{{ $gatePass->created_at->format('d M Y, H:i') }}</span>
                </div>
                @if($gatePass->customer_info)
                <div>
                    <span class="info-label">Customer Info</span>
                    <span class="info-value">{{ $gatePass->customer_info }}</span>
                </div>
                @endif
                @if($gatePass->po_number)
                <div>
                    <span class="info-label">PO Number</span>
                    <span class="info-value">{{ $gatePass->po_number }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- FULL QC APPROVAL COLUMN (Enhanced Detailed View) -->
        <div class="info-col">
            <h3 class="section-heading">Quality Control Approval</h3>
            <div class="qc-full-details">
                @php
                    $qcApprovedAt = $gatePass->qc_approved_at;
                    $qcRemarks = $gatePass->qc_remarks;
                    $qcApprovedBy = $gatePass->qc_approved_by_name ?? ($gatePass->qcApprovedBy?->name ?? $gatePass->qc_approved_by ?? 'N/A');
                    $qcInspectorId = $gatePass->qc_inspector_id ?? ($gatePass->qc_approver_id ?? null);
                    $qcDecision = $gatePass->qc_status ?? ($qcApprovedAt ? 'approved' : 'pending');
                    $qcChecklist = $gatePass->qc_checklist_summary ?? null;
                @endphp

                <div class="qc-status-badge">
                    @if($qcApprovedAt)
                        <span class="qc-approved-badge">✓ QC APPROVED</span>
                        <span style="font-size:0.7rem; color:#047857;">&#10004; Verified</span>
                    @else
                        <span class="qc-pending-badge">⏳ QC PENDING</span>
                        <span style="font-size:0.7rem; color:#b45309;">Awaiting inspection</span>
                    @endif
                </div>

                @if($qcApprovedAt)
                    <div class="qc-info-block">
                        <strong>Approved On:</strong> {{ $qcApprovedAt instanceof \Carbon\Carbon ? $qcApprovedAt->format('d M Y, h:i A') : \Carbon\Carbon::parse($qcApprovedAt)->format('d M Y, h:i A') }}<br>
                        <strong>Approved By:</strong> {{ $qcApprovedBy }}
                        @if($qcInspectorId) <span style="font-weight:normal;">(ID: {{ $qcInspectorId }})</span> @endif<br>
                        @if($gatePass->qc_department_signature)
                            <strong>Dept. Ref:</strong> {{ $gatePass->qc_department_signature }}<br>
                        @endif
                        @if($gatePass->quality_check_result)
                            <strong>Inspection Result:</strong> <span style="color:#15803d;">{{ ucfirst($gatePass->quality_check_result) }}</span><br>
                        @endif
                    </div>
                @else
                    <div class="qc-info-block">
                        <strong>Status:</strong> Not yet approved by QC department.<br>
                        <strong>Initiated By:</strong> {{ $gatePass->preparedBy?->username ?? 'N/A' }}<br>
                        <strong>Request Date:</strong> {{ $gatePass->created_at->format('d M Y') }}
                    </div>
                @endif

                @if($qcRemarks)
                    <div class="qc-remarks-box">
                        <strong>📋 QC Remarks:</strong><br>
                        {{ $qcRemarks }}
                    </div>
                @elseif($qcApprovedAt)
                    <div class="qc-remarks-box" style="color:#6c757d;">
                        <em>No additional remarks recorded.</em>
                    </div>
                @else
                    <div class="qc-remarks-box" style="background:#fef9e6;">
                        <strong>ⓘ Note:</strong> QC approval pending – items awaiting inspection.
                    </div>
                @endif

                @if($qcChecklist)
                    <div class="qc-meta-row">
                        <span><strong>Checklist:</strong> {{ $qcChecklist }}</span>
                    </div>
                @endif

                @if($gatePass->qc_approved_at && $gatePass->warranty_days)
                    <div class="qc-meta-row" style="border-top: 1px dashed #cbd5e1; margin-top: 0.5rem; padding-top: 0.4rem;">
                        <span><strong>Warranty active:</strong> {{ $gatePass->warranty_days }} days (QC validated)</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Security Approvals Column -->
        <div class="info-col">
            <h3 class="section-heading">Security & Exit</h3>
            <div class="approval-grid">
                <div>
                    <span class="approval-label">Security Approval</span>
                    <div class="approval-row">
                        <div>
                            @if($gatePass->security_approved_at)
                                <span class="text-green">Approved on {{ \Carbon\Carbon::parse($gatePass->security_approved_at)->format('d M Y, H:i') }}</span>
                            @else
                                <span class="text-gray-pending">Pending clearance</span>
                            @endif
                        </div>
                        @if($gatePass->security_incharge_name)
                            <div class="incharge-text">
                                🛡️ In charge: {{ $gatePass->security_incharge_name }}
                            </div>
                        @endif
                    </div>
                </div>
                @if($gatePass->security_gate_number)
                <div>
                    <span class="approval-label">Exit Gate</span>
                    <div class="info-value">{{ $gatePass->security_gate_number }}</div>
                </div>
                @endif
                @if($gatePass->security_vehicle_number)
                <div>
                    <span class="approval-label">Vehicle No.</span>
                    <div class="info-value">{{ $gatePass->security_vehicle_number }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MANIFEST ITEMS TABLE -->
    <div class="table-section">
        <h3 class="table-heading">Manifest Items</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Unique ID</th>
                        <th>Product Name</th>
                        <th>Category Name</th>
                        <th>Specification</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rowNum = 1;
                        $itemsCollection = $gatePass->items ?? collect();
                        $activeItems = $itemsCollection->whereNull('returned_at');
                        $returnedItems = $itemsCollection->whereNotNull('returned_at');
                        $totalItems = $itemsCollection->count();
                        $activeCount = $activeItems->count();
                        $returnedCount = $returnedItems->count();
                        $totalQty = $itemsCollection->sum(function($item) {
                            return ($item->product?->manual_status == 1 || $item->is_manual_item) ? ($item->quantity ?? 1) : 1;
                        });
                    @endphp

                    @forelse($activeItems as $item)
                        <tr>
                            <td class="td-num">{{ $rowNum++ }}</td>
                            <td class="td-mono">{{ $item->unique_id ?? $item->id }}</td>
                            <td class="td-text">{{ $item->product_name ?? $item->name ?? 'Item' }}</td>
                            <td class="td-text">{{ $item->category_name ?? $item->category ?? 'General' }}</td>
                            <td style="padding:0.625rem 0.75rem;font-size:0.8125rem;">
                                @if($item->product && $item->product->specifications && count($item->product->specifications) > 0)
                                    <div class="spec-col">
                                        @foreach($item->product->specifications as $spec)
                                            @php
                                                $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                                $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                            @endphp
                                            @if($specKey)
                                                <span class="spec-item"><span class="spec-key">{{ str_replace('_', ' ', $specKey) }}:</span> {{ $specValue ?: '—' }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span style="font-size:0.75rem;color:#9ca3af;">-</span>
                                @endif
                            </td>
                            <td class="td-text">{{ ($item->product?->manual_status == 1 || $item->is_manual_item) ? ($item->quantity ?? 1) : 1 }}</td>
                            <td class="text-active">Active</td>
                        </tr>
                    @empty
                        @if($returnedItems->isEmpty())
                            <tr>
                                <td colspan="7" style="padding:1.5rem 1rem;text-align:center;color:#6b7280;font-size:0.875rem;">No items found</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($returnedItems as $item)
                        <tr class="tr-returned">
                            <td class="td-num" style="color:#9ca3af;">{{ $rowNum++ }}</td>
                            <td class="td-mono-gray">{{ $item->unique_id ?? $item->id }}</td>
                            <td class="td-text-gray">{{ $item->product_name ?? $item->name ?? 'Item' }}</td>
                            <td class="td-text-gray">{{ $item->category_name ?? $item->category ?? 'General' }}</td>
                            <td style="padding:0.625rem 0.75rem;font-size:0.8125rem;color:#9ca3af;">
                                @if($item->product && $item->product->specifications && count($item->product->specifications) > 0)
                                    <div class="spec-col">
                                        @foreach($item->product->specifications as $spec)
                                            @php
                                                $specKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                                $specValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                            @endphp
                                            @if($specKey)
                                                <span class="spec-item-gray"><span class="spec-key-gray">{{ str_replace('_', ' ', $specKey) }}:</span> {{ $specValue ?: '—' }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span style="font-size:0.75rem;">-</span>
                                @endif
                            </td>
                            <td style="padding:0.625rem 0.75rem;font-size:0.8125rem;color:#9ca3af;white-space:nowrap;">{{ ($item->product?->manual_status == 1 || $item->is_manual_item) ? ($item->quantity ?? 1) : 1 }}</td>
                            <td style="padding:0.625rem 0.75rem;">
                                <span class="returned-badge">Returned</span>
                                <div class="returned-date">{{ isset($item->returned_at) ? (\Carbon\Carbon::parse($item->returned_at)->format('d M Y, H:i')) : 'Returned' }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                @if($totalItems > 0)
                    <tfoot>
                        <tr>
                            <td colspan="7">
                                Total Qty: {{ $totalQty }}
                                &nbsp;·&nbsp; Active: {{ $activeCount }}
                                @if($returnedCount > 0)
                                    &nbsp;·&nbsp; <span class="text-orange">Returned: {{ $returnedCount }}</span>
                                    &nbsp;·&nbsp; Total Items: {{ $totalItems }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- NOTES AND SIGNATURE + additional QC compliance note -->
    <div class="footer-row">
        <div class="notes-col">
            <h4 class="notes-heading">Notes & Compliance</h4>
            <div class="notes-box">
                Please do not remove the sticker from the backside of the item. If the sticker is removed, the item will not be eligible for return or repair. Thank you.
                @if((int)($gatePass->warranty_days ?? 0) > 0)
                    <br><br><strong>🔧 Warranty:</strong> This gate pass includes a check warranty of {{ $gatePass->warranty_days }} {{ Str::plural('day', $gatePass->warranty_days) }}.
                @endif

            </div>
        </div>

        <div class="sig-col">
            <p class="sig-meta" style="margin-bottom: 0.25rem;">{{ $gatePass->preparedBy?->full_name ?? auth('web')->user()?->full_name ?? 'N/A' }}</p>
            <div class="sig-line" style="margin-top: 0;"></div>
            <p class="sig-label">Authorized Signature</p>
            @if($gatePass->invoice_no)
                <p class="sig-invoice"><strong>Invoice No:</strong> {{ $gatePass->invoice_no }}</p>
            @endif

        </div>
    </div>
</body>
</html>
