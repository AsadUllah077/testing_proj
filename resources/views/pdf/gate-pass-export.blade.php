<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gate Pass Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #0078C8; }
        .header h1 { color: #0078C8; margin: 0; font-size: 20px; }
        .header p { color: #666; margin: 4px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #0078C8; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        td { padding: 7px 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-green  { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef9c3; color: #92400e; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gate Pass Report</h1>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        @if($filters['status'] && $filters['status'] !== 'All')
            <p>Status Filter: {{ str_replace(['QC_AND_SECURITY_APPROVED','SECURITY_APPROVAL_PENDING','QC_APPROVAL_PENDING','REJECTED'],['Approved','Security Pending','QC Pending','Rejected'], $filters['status']) }}</p>
        @endif
        @if($filters['search'])
            <p>Search: {{ $filters['search'] }}</p>
        @endif
        <p>Total Records: {{ $gatePasses->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%">S.No</th>
                <th style="width:15%">Gate Pass ID</th>
                <th style="width:18%">Source Location</th>
                <th style="width:8%">Items</th>
                <th style="width:18%">Prepared By</th>
                <th style="width:13%">Date</th>
                <th style="width:13%">Status</th>
                <th style="width:10%">QC Approved</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gatePasses as $i => $gp)
                @php
                    $badgeClass = match($gp->status) {
                        'QC_AND_SECURITY_APPROVED'   => 'badge-green',
                        'SECURITY_APPROVAL_PENDING'  => 'badge-yellow',
                        'QC_APPROVAL_PENDING'        => 'badge-blue',
                        'REJECTED'                   => 'badge-red',
                        default                      => '',
                    };
                    $statusText = match($gp->status) {
                        'QC_AND_SECURITY_APPROVED'  => 'Approved',
                        'SECURITY_APPROVAL_PENDING' => 'Security Pending',
                        'QC_APPROVAL_PENDING'       => 'QC Pending',
                        'REJECTED'                  => 'Rejected',
                        default                     => $gp->status,
                    };
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $gp->gate_pass_id }}</td>
                    <td>{{ $gp->warehouseLocation->name ?? 'N/A' }}</td>
                    <td>{{ $gp->items->count() }}</td>
                    <td>{{ $gp->preparedBy?->username ?? 'N/A' }}</td>
                    <td>{{ $gp->created_at->format('d M Y') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $statusText }}</span></td>
                    <td>{{ $gp->qc_approved_at ? \Carbon\Carbon::parse($gp->qc_approved_at)->format('d M Y') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:20px;color:#999">No gate passes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
