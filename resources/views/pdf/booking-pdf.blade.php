<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Logistics Bookings — {{ $mode }}</title>
<style>
    @page { margin: 0; size: A4 landscape; }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
        font-size: 9px;
        color: #1e293b;
        background: #ffffff;
    }

    /* ─── HEADER ─── */
    .header {
        position: relative;
        height: 130px;
        width: 100%;
        overflow: hidden;
        background: #ffffff;
    }

    /* Left scattered diamonds */
    .diamonds-left { position: absolute; top: 0; left: 0; width: 200px; height: 130px; }
    .d { position: absolute; transform: rotate(45deg); }

    /* Logo block */
    .logo-block { position: absolute; top: 28px; left: 44px; }
    .logo-block img { height: 58px; }
    .logo-text-name { font-weight: bold; font-size: 20px; color: #1a56a0; line-height: 1.2; }
    .logo-text-sub  { font-size: 9px; color: #64748b; letter-spacing: 0.5px; margin-top: 2px; }

    /* Right geometric pattern */
    .pattern-right { position: absolute; top: 0; right: 0; width: 340px; height: 130px; overflow: hidden; }
    .sq { position: absolute; transform: rotate(45deg); }

    .sq-dark-1  { width:120px;height:120px;background:#0A1628;top:-30px;right:-30px; }
    .sq-dark-2  { width: 95px;height: 95px;background:#0D2B4E;top:-15px;right: 55px; }
    .sq-dark-3  { width:105px;height:105px;background:#0E4C8A;top: 30px;right:-10px; }
    .sq-med-1   { width: 85px;height: 85px;background:#0078C8;top:-10px;right:120px; }
    .sq-med-2   { width: 90px;height: 90px;background:#0E4C8A;top: 55px;right: 65px; }
    .sq-med-3   { width: 75px;height: 75px;background:#0078C8;top: 70px;right:140px; }
    .sq-light-1 { width: 65px;height: 65px;background:#4DA6E0;top: -5px;right:195px; }
    .sq-light-2 { width: 68px;height: 68px;background:#A8D4F0;top: 50px;right:210px; }
    .sq-light-3 { width: 55px;height: 55px;background:#D6EAF8;top:  5px;right:255px; }
    .sq-light-4 { width: 58px;height: 58px;background:#C8D6E0;top: 90px;right:245px; }
    .d-r-1 { width:16px;height:16px;background:#D6EAF8;top:  5px;right:305px; }
    .d-r-2 { width:13px;height:13px;background:#C8D6E0;top: -5px;right:330px; }
    .d-r-3 { width:11px;height:11px;background:#E8F4FA;top: 35px;right:315px; }
    .d-r-4 { width:10px;height:10px;background:#D6EAF8;top: 65px;right:305px; }

    /* Centre title */
    .header-center { position: absolute; top: 0; left: 200px; right: 370px; height: 130px; }
    .header-center-inner { padding: 20px 20px 0; }
    .report-mode-badge {
        display: inline-block;
        background: #0078C8;
        color: #fff;
        font-size: 7.5px;
        font-weight: bold;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }
    .report-title    { font-size: 17px; font-weight: bold; color: #0f172a; margin-top: 7px; }
    .report-subtitle { font-size: 8px; color: #1a56a0; font-weight: bold; margin-top: 3px; }
    .report-meta     { font-size: 7.5px; color: #94a3b8; margin-top: 5px; }

    /* Count block */
    .header-count-block { position: absolute; top: 28px; right: 350px; text-align: right; }
    .count-number { font-size: 26px; font-weight: bold; color: #0f172a; line-height: 1; }
    .count-label  { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    .count-value  { font-size: 8px; color: #0078C8; font-weight: bold; margin-top: 4px; }

    /* ─── BLUE BAR ─── */
    .blue-bar { height: 4px; background: #0078C8; width: 100%; }

    /* ─── SUMMARY STATS ─── */
    .stats-wrap { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px 24px; }
    .stats-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
    .stat-cell { text-align: center; padding: 7px 10px; border-radius: 6px; }
    .stat-number { font-size: 15px; font-weight: bold; line-height: 1; }
    .stat-label  { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

    /* ─── INFO BAR ─── */
    .info-bar { padding: 6px 24px; background: #fff; border-bottom: 1px solid #e2e8f0; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-item  { font-size: 7.5px; color: #64748b; padding-right: 20px; }
    .info-item strong { color: #0f172a; }

    /* ─── SECTION LABEL ─── */
    .section-label { padding: 8px 24px 5px; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }

    /* ─── MAIN TABLE ─── */
    .main-table { width: 100%; border-collapse: collapse; }
    .main-table thead tr { background: #1e293b; }
    .main-table thead th {
        padding: 7px 9px;
        text-align: left;
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: #94a3b8;
        white-space: nowrap;
        border: none;
    }
    .main-table thead th:first-child { padding-left: 24px; }
    .main-table thead th:last-child  { padding-right: 24px; text-align: right; }
    .main-table tbody tr { border-bottom: 1px solid #f1f5f9; }
    .main-table tbody tr:nth-child(even) { background: #f8fafc; }
    .main-table tbody td { padding: 8px 9px; font-size: 8px; color: #334155; vertical-align: middle; }
    .main-table tbody td:first-child { padding-left: 24px; }
    .main-table tbody td:last-child  { padding-right: 24px; text-align: right; }

    .shp-id       { font-weight: bold; color: #0078C8; font-size: 8.5px; }
    .carrier-name { font-weight: bold; color: #0f172a; }
    .carrier-mode { font-size: 7px; color: #94a3b8; margin-top: 2px; }
    .route-origin { font-weight: bold; color: #0f172a; }
    .route-dest   { color: #475569; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; }
    .s-draft     { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .s-booked    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .s-pickedup  { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
    .s-intransit { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .s-delivered { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

    .date-val { color: #475569; }
    .date-tbd { color: #cbd5e1; font-style: italic; }
    .rate-val { font-weight: bold; color: #0f172a; }
    .empty-row td { text-align: center; padding: 28px; color: #94a3b8; font-style: italic; }

    /* ─── FOOTER ─── */
    .footer-wrap { margin-top: 14px; padding: 8px 24px; border-top: 1px solid #e2e8f0; }
    .footer-table { width: 100%; border-collapse: collapse; }
    .footer-left  { font-size: 7px; color: #94a3b8; }
    .footer-right { font-size: 7px; color: #94a3b8; text-align: right; }
    .footer-brand { font-weight: bold; color: #0078C8; }
</style>
</head>
<body>

@php
    $today     = now()->startOfDay();
    $counts    = ['Booked' => 0, 'Picked Up' => 0, 'In Transit' => 0, 'Delivered' => 0, 'Draft' => 0];
    $totalRate = 0;

    foreach ($bookings as $b) {
        $hasPickup   = $b->events->contains('event_type', 'pickup_approved');
        $hasDelivery = $b->events->contains('event_type', 'delivery_approved');
        $pd  = $b->pick_date ? \Carbon\Carbon::parse($b->pick_date)->startOfDay() : null;
        $approvalEvent = $b->events->firstWhere('event_type', 'pickup_approved');
        $approvalDate  = $approvalEvent?->actual_date ? \Carbon\Carbon::parse($approvalEvent->actual_date)->startOfDay() : null;

        if ($b->is_draft)     { $s = 'Draft'; }
        elseif ($hasDelivery) { $s = 'Delivered'; }
        elseif ($hasPickup) {
            if ($pd && $today->lt($pd))                          $s = 'Booked';
            elseif ($approvalDate && $today->lte($approvalDate)) $s = 'Picked Up';
            else                                                  $s = 'In Transit';
        } else { $s = 'Booked'; }

        $counts[$s]++;
        $totalRate += (float)($b->carrier_rate ?? 0);
    }

    // Embed logo as base64 for DomPDF
    $logoPath1 = public_path('assets/images/updated-logo.webp');
    $logoPath2 = public_path('logo.png');
    $logoSrc   = null;
    if (file_exists($logoPath1)) {
        $logoSrc = 'data:image/webp;base64,' . base64_encode(file_get_contents($logoPath1));
    } elseif (file_exists($logoPath2)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath2));
    }
@endphp

{{-- ── HEADER ── --}}
<div class="header">

    {{-- Left scattered diamonds --}}
    <div class="diamonds-left">
        <div class="d" style="width:10px;height:10px;background:#E5E7EB;top:8px;left:28px;"></div>
        <div class="d" style="width:13px;height:13px;background:#D1D5DB;top:2px;left:55px;"></div>
        <div class="d" style="width:9px;height:9px;background:#F3F4F6;top:18px;left:80px;"></div>
        <div class="d" style="width:11px;height:11px;background:#D1D5DB;top:5px;left:105px;"></div>
        <div class="d" style="width:8px;height:8px;background:#E5E7EB;top:22px;left:130px;"></div>
        <div class="d" style="width:7px;height:7px;background:#F3F4F6;top:10px;left:150px;"></div>
        <div class="d" style="width:9px;height:9px;background:#E5E7EB;top:45px;left:40px;"></div>
        <div class="d" style="width:7px;height:7px;background:#D1D5DB;top:55px;left:18px;"></div>
    </div>

    {{-- Logo --}}
    <div class="logo-block">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="Safe Solutions">
        @else
            <div class="logo-text-name">Safe Solutions</div>
            <div class="logo-text-sub">Consultants Pvt Ltd</div>
        @endif
    </div>

    {{-- Centre title --}}
    <div class="header-center">
        <div class="header-center-inner">
            <span class="report-mode-badge">{{ strtoupper($mode) }} Shipments</span>
            <div class="report-title">Logistics Bookings Report</div>
            <div class="report-subtitle">Safe Solutions Consultants Pvt Ltd</div>
            <div class="report-meta">Generated {{ now()->format('F d, Y \a\t h:i A') }}</div>
        </div>
    </div>

    {{-- Shipment count --}}
    <div class="header-count-block">
        <div class="count-number">{{ $bookings->count() }}</div>
        <div class="count-label">Total Shipments</div>
        @if($totalRate > 0)
            <div class="count-value">${{ number_format($totalRate, 2) }}</div>
        @endif
    </div>

    {{-- Right geometric pattern --}}
    <div class="pattern-right">
        <div class="sq sq-dark-1"></div>
        <div class="sq sq-dark-2"></div>
        <div class="sq sq-dark-3"></div>
        <div class="sq sq-med-1"></div>
        <div class="sq sq-med-2"></div>
        <div class="sq sq-med-3"></div>
        <div class="sq sq-light-1"></div>
        <div class="sq sq-light-2"></div>
        <div class="sq sq-light-3"></div>
        <div class="sq sq-light-4"></div>
        <div class="sq d-r-1"></div>
        <div class="sq d-r-2"></div>
        <div class="sq d-r-3"></div>
        <div class="sq d-r-4"></div>
    </div>

</div>

{{-- Brand blue bar --}}
<div class="blue-bar"></div>

{{-- ── SUMMARY STATS ── --}}
<div class="stats-wrap">
    <table class="stats-table">
        <tr>
            <td class="stat-cell" style="background:#eff6ff;">
                <div class="stat-number" style="color:#1d4ed8;">{{ $counts['Booked'] }}</div>
                <div class="stat-label" style="color:#93c5fd;">Booked</div>
            </td>
            <td class="stat-cell" style="background:#f5f3ff;">
                <div class="stat-number" style="color:#6d28d9;">{{ $counts['Picked Up'] }}</div>
                <div class="stat-label" style="color:#c4b5fd;">Picked Up</div>
            </td>
            <td class="stat-cell" style="background:#fffbeb;">
                <div class="stat-number" style="color:#b45309;">{{ $counts['In Transit'] }}</div>
                <div class="stat-label" style="color:#fcd34d;">In Transit</div>
            </td>
            <td class="stat-cell" style="background:#f0fdf4;">
                <div class="stat-number" style="color:#15803d;">{{ $counts['Delivered'] }}</div>
                <div class="stat-label" style="color:#86efac;">Delivered</div>
            </td>
            <td class="stat-cell" style="background:#f1f5f9;">
                <div class="stat-number" style="color:#475569;">{{ $counts['Draft'] }}</div>
                <div class="stat-label" style="color:#94a3b8;">Draft</div>
            </td>
        </tr>
    </table>
</div>

{{-- ── INFO BAR ── --}}
<div class="info-bar">
    <table class="info-table">
        <tr>
            <td class="info-item">Mode: <strong>{{ $mode }}</strong></td>
            <td class="info-item">Generated: <strong>{{ now()->format('Y-m-d') }}</strong></td>
            <td class="info-item">Records: <strong>{{ $bookings->count() }}</strong></td>
            @if($totalRate > 0)
            <td class="info-item" style="text-align:right;">
                Total Value: <strong style="color:#0078C8;">${{ number_format($totalRate, 2) }}</strong>
            </td>
            @endif
        </tr>
    </table>
</div>

{{-- ── SECTION LABEL ── --}}
<div class="section-label">Shipment Details</div>

{{-- ── MAIN TABLE ── --}}
<table class="main-table">
    <thead>
        <tr>
            <th>Shipment ID</th>
            <th>Carrier</th>
            <th>Status</th>
            <th>Origin</th>
            <th>Destination</th>
            <th>Pick Date</th>
            <th>ETA</th>
            <th>Booking Date</th>
            <th>Rate</th>
        </tr>
    </thead>
    <tbody>
        @forelse($bookings as $booking)
        @php
            $hasPickup   = $booking->events->contains('event_type', 'pickup_approved');
            $hasDelivery = $booking->events->contains('event_type', 'delivery_approved');
            $pd  = $booking->pick_date ? \Carbon\Carbon::parse($booking->pick_date)->startOfDay() : null;
            $approvalEvt  = $booking->events->firstWhere('event_type', 'pickup_approved');
            $approvalDate = $approvalEvt?->actual_date ? \Carbon\Carbon::parse($approvalEvt->actual_date)->startOfDay() : null;

            if ($booking->is_draft)   { $status = 'Draft';      $sc = 's-draft'; }
            elseif ($hasDelivery)     { $status = 'Delivered';  $sc = 's-delivered'; }
            elseif ($hasPickup) {
                if ($pd && $today->lt($pd))                          { $status = 'Booked';     $sc = 's-booked'; }
                elseif ($approvalDate && $today->lte($approvalDate)) { $status = 'Picked Up';  $sc = 's-pickedup'; }
                else                                                  { $status = 'In Transit'; $sc = 's-intransit'; }
            } else                    { $status = 'Booked';     $sc = 's-booked'; }
        @endphp
        <tr>
            <td><span class="shp-id">SHP-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span></td>
            <td>
                <div class="carrier-name">{{ $booking->carrier?->legal_carrier_name ?? 'TBD' }}</div>
                <div class="carrier-mode">{{ $booking->service_type }}</div>
            </td>
            <td><span class="badge {{ $sc }}">{{ $status }}</span></td>
            <td>
                <div class="route-origin">{{ $booking->origin_company ?: $booking->origin_country }}</div>
                @if($booking->origin_city)<div class="route-dest">{{ $booking->origin_city }}, {{ $booking->origin_state }}</div>@endif
            </td>
            <td>
                <div class="route-origin">{{ $booking->dest_company ?: $booking->dest_country }}</div>
                @if($booking->dest_city)<div class="route-dest">{{ $booking->dest_city }}, {{ $booking->dest_state }}</div>@endif
            </td>
            <td>
                @if($booking->pick_date)
                    <span class="date-val">{{ $booking->pick_date->format('M d, Y') }}</span>
                @else
                    <span class="date-tbd">—</span>
                @endif
            </td>
            <td>
                @if($booking->eta)
                    <span class="date-val">{{ $booking->eta->format('M d, Y') }}</span>
                @else
                    <span class="date-tbd">—</span>
                @endif
            </td>
            <td>
                @if($booking->loading_date)
                    <span class="date-val">{{ $booking->loading_date->format('M d, Y') }}</span>
                @else
                    <span class="date-tbd">—</span>
                @endif
            </td>
            <td>
                @if($booking->carrier_rate)
                    <span class="rate-val">${{ number_format($booking->carrier_rate, 2) }}</span>
                @else
                    <span class="date-tbd">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr class="empty-row">
            <td colspan="9">No bookings found for this export.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ── --}}
<div class="footer-wrap">
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                <span class="footer-brand">Safe Solutions Consultants Pvt Ltd</span>
                &nbsp;·&nbsp; Confidential — Internal Use Only
            </td>
            <td class="footer-right">
                Generated by {{ auth()->user()->name ?? 'System' }}
                &nbsp;·&nbsp; {{ now()->format('M d, Y h:i A') }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
