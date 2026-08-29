@extends('layouts.auth')

@section('title', 'Warehouse Dashboard')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand-50: #eff6ff;
            --brand-100: #dbeafe;
            --brand-500: #3b82f6;
            --brand-600: #2563eb;
            --brand-700: #1d4ed8;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --emerald: #10b981;
            --amber: #f59e0b;
            --red: #ef4444;
            --purple: #8b5cf6;
            --cyan: #06b6d4;
            --slate: #475569;
            --radius: 14px;
            --radius-sm: 8px;
            --radius-md: 12px;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-sizing: border-box;
        }

        .dash-wrap {
            padding: 28px 32px;
            background: #f1f5f9;
            min-height: 100vh;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .dash-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin: 0;
        }

        .dash-header p {
            color: var(--text-muted);
            font-size: 0.8125rem;
            margin: 4px 0 0;
            font-weight: 500;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .period-select {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: inherit;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px;
        }

        .period-select:focus {
            border-color: var(--brand-500);
        }

        .refresh-btn {
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, color 0.15s;
            color: var(--text-muted);
        }

        .refresh-btn:hover {
            border-color: var(--brand-500);
            background: var(--brand-50);
            color: var(--brand-600);
        }

        .refresh-btn svg {
            width: 16px;
            height: 16px;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1.5px solid var(--border);
            padding: 26px;
            transition: all 0.2s;
        }

        .card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--brand-100);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 24px;
            margin-bottom: 28px;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            padding: 24px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
        }

        .stat-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .stat-delta {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }

        .stat-delta svg {
            width: 14px;
            height: 14px;
        }

        .delta-up {
            color: var(--emerald);
        }

        .delta-down {
            color: var(--red);
        }

        .delta-label {
            font-size: 0.75rem;
            font-weight: 600;
        }

        .delta-period {
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .badge-yellow {
            background: #fef9c3;
            color: #92400e;
        }

        .badge-orange {
            background: #ffedd5;
            color: #9a3412;
        }

        .badge-green {
            background: #dcfce7;
            color: #14532d;
        }

        .progress-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .progress-track {
            flex: 1;
            height: 5px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--brand-500);
            transition: width 0.6s cubic-bezier(.23, 1, .32, 1);
        }

        .progress-pct {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }

        @media (max-width: 640px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        .mid-grid {
            display: grid;
            grid-template-columns: 360px 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }

        @media (max-width: 1200px) {
            .mid-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .mid-grid {
                grid-template-columns: 1fr;
            }
        }

        .card-inner {
            padding: 22px;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 20px;
            letter-spacing: -0.01em;
        }

        .test-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }

        .test-cell {
            border-radius: var(--radius);
            padding: 20px 16px;
            text-align: center;
            border: 1.5px solid;
            transition: all 0.2s;
        }

        .test-cell-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
        }

        .test-cell-icon svg {
            width: 15px;
            height: 15px;
        }

        .test-cell-label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .test-cell-val {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-top: 2px;
        }

        .test-pass {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .test-pass .test-cell-icon {
            background: #dcfce7;
        }

        .test-pass .test-cell-icon svg,
        .test-pass .test-cell-val {
            color: #16a34a;
        }

        .test-scrap {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .test-scrap .test-cell-icon {
            background: #ffe4e6;
        }

        .test-scrap .test-cell-icon svg,
        .test-scrap .test-cell-val {
            color: #dc2626;
        }

        .test-hold {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .test-hold .test-cell-icon {
            background: #fef3c7;
        }

        .test-hold .test-cell-icon svg,
        .test-hold .test-cell-val {
            color: #d97706;
        }

        .test-fail {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .test-fail .test-cell-icon {
            background: #f1f5f9;
        }

        .test-fail .test-cell-icon svg,
        .test-fail .test-cell-val {
            color: #64748b;
        }

        .ready-today {
            border-top: 1.5px solid var(--border);
            padding-top: 16px;
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ready-today-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
        }

        .ready-today-val {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--brand-600);
            letter-spacing: -0.03em;
        }

        .ready-today-unit {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        #dispositionChart {
            height: 280px;
        }

        #readyForSaleChart {
            height: 260px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        @media (max-width: 640px) {
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        .timeline {
            position: relative;
            padding-left: 28px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 4px;
            bottom: 0;
            width: 1.5px;
            background: linear-gradient(to bottom, #e2e8f0 80%, transparent);
        }

        .activity-item {
            position: relative;
            margin-bottom: 18px;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-dot {
            position: absolute;
            left: -22px;
            top: 4px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--surface);
            box-shadow: 0 0 0 1.5px var(--border);
        }

        .activity-dot svg {
            width: 11px;
            height: 11px;
        }

        .activity-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 2px 8px;
        }

        .activity-desc {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.4;
            margin: 0 0 4px 8px;
        }

        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-time {
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .activity-time strong {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .activity-link {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--brand-600);
            text-decoration: none;
        }

        .activity-link:hover {
            color: var(--brand-700);
        }

        .part-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: var(--surface-muted);
            border-radius: var(--radius);
            border: 1.5px solid transparent;
            transition: border-color 0.15s, background 0.15s;
            margin-bottom: 10px;
        }

        .part-row:hover {
            border-color: var(--brand-200);
            background: var(--surface);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .part-row:last-child {
            margin-bottom: 0;
        }

        .part-icon {
            width: 34px;
            height: 34px;
            background: #fff7ed;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .part-icon svg {
            width: 16px;
            height: 16px;
            color: #f97316;
        }

        .part-info {
            min-width: 0;
            flex: 1;
            margin-left: 10px;
        }

        .part-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .part-num {
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            margin-top: 1px;
        }

        .part-qty {
            text-align: right;
            flex-shrink: 0;
            margin-left: 12px;
        }

        .part-qty-val {
            font-size: 0.875rem;
            font-weight: 800;
        }

        .qty-out {
            color: var(--red);
        }

        .qty-low {
            color: #f97316;
        }

        .part-qty-denom {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .part-qty-label {
            font-size: 0.6875rem;
            color: var(--text-muted);
        }

        .scrap-section {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1.5px solid var(--border);
        }

        .section-subhead {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin: 0 0 12px;
        }

        .scrap-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .scrap-cell {
            background: var(--surface-muted);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
        }

        .scrap-cell-label {
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .scrap-cell-val {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .scrap-cell-unit {
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-left: 2px;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .card-head-link {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--brand-600);
            text-decoration: none;
        }

        .card-head-link:hover {
            color: var(--brand-700);
        }

        .empty-state {
            text-align: center;
            padding: 28px 0;
        }

        .empty-state svg {
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 0.8125rem;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff1f2;
            border: 1.5px solid #fecdd3;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .error-alert svg {
            color: var(--red);
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .error-alert span {
            font-size: 0.8125rem;
            color: #dc2626;
            font-weight: 500;
        }

        .stat-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .badge-link {
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinning {
            animation: spin 0.8s linear infinite;
        }
    </style>
@endpush

@section('content')
    <div class="dash-wrap" x-data="dashboard()" x-init="setup()">

        {{-- ===== HEADER ===== --}}
        <div class="dash-header">
            <div>
                <h1>Warehouse Control Center</h1>
                <p style="font-weight: 600; color: var(--text-secondary);">Enterprise Logistics Intelligence & Analytics</p>
            </div>
            <div class="header-controls">
                <form action="" method="GET">
                    <select name="days" class="period-select" onchange="this.form.submit()">
                        <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="14" {{ $days == 14 ? 'selected' : '' }}>Last 14 Days</option>
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                </form>
                <button type="button" class="refresh-btn" onclick="window.location.reload()" title="Refresh Dashboard">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                <a href="{{ route('warehouse.products.create') }}"
                   style="background: var(--brand-600); color: white; padding: 10px 20px; border-radius: var(--radius-sm); font-weight: 800; font-size: 0.8rem; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);"
                   onmouseenter="this.style.background='var(--brand-700)'; this.style.transform='translateY(-1px)'"
                   onmouseleave="this.style.background='var(--brand-600)'; this.style.transform='none'">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    NEW PRODUCT
                </a>
            </div>
        </div>

        {{-- ===== ERROR ALERT ===== --}}
        @if (isset($error))
            <div class="error-alert">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $error }}</span>
            </div>
        @endif

        @if (!isset($error))

            {{-- Revised Dashboard Analytics --}}
            <div class="stats-grid" style="margin-top: 10px;">
                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.products.index') }}'">
                            <p class="stat-label">Total Inventory</p>
                            <p class="stat-value">{{ number_format($stats['total_products'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:var(--brand-50)"><svg fill="none" stroke="var(--brand-500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg></div>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.scan.logs') }}'">
                            <p class="stat-label">Scans Today</p>
                            <p class="stat-value">{{ number_format($stats['scans_today'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#ecfeff"><svg fill="none" stroke="var(--cyan)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h2" /></svg></div>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.products.index', ['status' => 'active']) }}'">
                            <p class="stat-label">Stock Status</p>
                            <p class="stat-value">{{ number_format($stats['ready_for_sale'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#f0fdf4"><svg fill="none" stroke="var(--emerald)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    </div>
                    <div class="badge-row" style="margin-top: 10px; display: flex; gap: 8px;">
                        <a href="{{ route('warehouse.products.index', ['status' => 'active']) }}"
                           class="badge" style="background:#dcfce7; color:#166534; font-weight:700; font-size:0.7rem; text-decoration:none; padding:4px 8px; border-radius:4px;">
                            ✓ READY: {{ number_format($stats['ready_for_sale'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.products.index', ['status' => 'inactive']) }}"
                           class="badge" style="background:#fee2e2; color:#991b1b; font-weight:700; font-size:0.7rem; text-decoration:none; padding:4px 8px; border-radius:4px;">
                            ✕ NOT READY: {{ number_format($stats['not_ready_for_sale'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.products.index', ['status' => 'sold']) }}"
                           class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.7rem; text-decoration:none; padding:4px 8px; border-radius:4px;">
                             SOLD: {{ number_format($stats['sold_products'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.product-issue.index', ['status' => 'Issued']) }}"
                           class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:0.7rem; text-decoration:none; padding:4px 8px; border-radius:4px;">
                             ISSUED: {{ number_format($stats['issued_products'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.products.index', ['status' => 'hold']) }}"
                           class="badge" style="background:#fef3c7; color:#92400e; font-weight:700; font-size:0.7rem; text-decoration:none; padding:4px 8px; border-radius:4px;">
                             HOLD: {{ number_format($stats['hold_products'] ?? 0) }}
                        </a>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.gate-pass.index') }}'">
                            <p class="stat-label">Gate Pass Overview</p>
                            <p class="stat-value">{{ number_format($stats['total_gate_passes'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#fff7ed"><svg fill="none" stroke="var(--amber)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8m0 8a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg></div>
                    </div>
                    <div class="badge-row" style="margin-top: 10px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                        <a href="{{ route('warehouse.gate-pass.index', ['status' => 'QC_AND_SECURITY_APPROVED']) }}"
                           class="badge" style="background:#dcfce7; color:#166534; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            APPROVED: {{ number_format($gatePassStatus['approved'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.gate-pass.index', ['status' => 'QC_APPROVAL_PENDING']) }}"
                           class="badge" style="background:#fff7ed; color:#9a3412; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            QC PEND: {{ number_format($gatePassStatus['pending_qc'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.gate-pass.index', ['status' => 'SECURITY_APPROVAL_PENDING']) }}"
                           class="badge" style="background:#fdf9c3; color:#713f12; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            SEC PEND: {{ number_format($gatePassStatus['pending_security'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.gate-pass.index', ['status' => 'REJECTED']) }}"
                           class="badge" style="background:#fee2e2; color:#991b1b; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            REJECTED: {{ number_format($gatePassStatus['rejected'] ?? 0) }}
                        </a>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.product-issue.index') }}'">
                            <p class="stat-label">Product Issues</p>
                            <p class="stat-value">{{ number_format($productIssueStatus['total'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#f3e8ff"><svg fill="none" stroke="var(--purple)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></div>
                    </div>
                    <div class="badge-row" style="margin-top: 10px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                        <a href="{{ route('warehouse.product-issue.index', ['status' => 'Issued']) }}"
                           class="badge" style="background:#dcfce7; color:#166534; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            ISSUED: {{ number_format($productIssueStatus['issued'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.product-issue.index', ['status' => 'Returned']) }}"
                           class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.65rem; text-decoration:none; padding:4px 6px; border-radius:4px; display: flex; justify-content: center; text-align: center;">
                            RETURNED: {{ number_format($productIssueStatus['returned'] ?? 0) }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:28px;">
                <p class="card-title">Inventory by Category</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px; margin-top:20px;">
                    @foreach($categoryWiseCount as $item)
                        @if($item['count'] > 0)
                            <div role="link" tabindex="0"
                               style="background:var(--surface-muted); padding:20px 24px; border-radius:var(--radius); border:1.5px solid var(--border); transition: all 0.2s; text-decoration: none; display: block; cursor: pointer;"
                                onclick="if (!event.target.closest('a')) window.location.href='{{ route('warehouse.products.index', ['category' => $item['id']]) }}'"
                                onkeydown="if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a')) { event.preventDefault(); window.location.href='{{ route('warehouse.products.index', ['category' => $item['id']]) }}'; }"
                                onmouseenter="this.style.borderColor='var(--brand-300)'; this.style.background='var(--surface)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)'"
                                onmouseleave="this.style.borderColor='var(--border)'; this.style.background='var(--surface-muted)'; this.style.boxShadow='none'">
                                <p style="font-size:0.7rem; color:var(--text-primary); font-weight:700; text-transform:uppercase; margin:0; letter-spacing:0.03em;">{{ $item['name'] }}</p>
                                <p style="font-size:1.5rem; font-weight:800; color:var(--text-primary); margin:8px 0 0; letter-spacing:-0.02em;">{{ number_format($item['count']) }}</p>
                                <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; margin-top:14px;">
                                    <a href="{{ route('warehouse.products.index', ['category' => $item['id'], 'status' => 'active']) }}"
                                       style="background:#dcfce7; color:#166534; border-radius:8px; padding:8px; text-decoration:none; display:block;">
                                        <p style="font-size:0.62rem; font-weight:800; text-transform:uppercase; margin:0;">Ready</p>
                                        <p style="font-size:1rem; font-weight:800; margin:3px 0 0;">{{ number_format($item['ready_for_sale'] ?? 0) }}</p>
                                    </a>
                                    <a href="{{ route('warehouse.products.index', ['category' => $item['id'], 'status' => 'inactive']) }}"
                                       style="background:#fee2e2; color:#991b1b; border-radius:8px; padding:8px; text-decoration:none; display:block;">
                                        <p style="font-size:0.62rem; font-weight:800; text-transform:uppercase; margin:0;">Not Ready</p>
                                        <p style="font-size:1rem; font-weight:800; margin:3px 0 0;">{{ number_format($item['not_ready_for_sale'] ?? 0) }}</p>
                                    </a>
                                    <a href="{{ route('warehouse.product-issue.index', ['category' => $item['id'], 'status' => 'Issued']) }}"
                                       style="background:#e0e7ff; color:#3730a3; border-radius:8px; padding:8px; text-decoration:none; display:block;">
                                        <p style="font-size:0.62rem; font-weight:800; text-transform:uppercase; margin:0;">Issued</p>
                                        <p style="font-size:1rem; font-weight:800; margin:3px 0 0;">{{ number_format($item['issued'] ?? 0) }}</p>
                                    </a>
                                    <a href="{{ route('warehouse.products.index', ['category' => $item['id'], 'status' => 'hold']) }}"
                                       style="background:#fef3c7; color:#92400e; border-radius:8px; padding:8px; text-decoration:none; display:block;">
                                        <p style="font-size:0.62rem; font-weight:800; text-transform:uppercase; margin:0;">Hold</p>
                                        <p style="font-size:1rem; font-weight:800; margin:3px 0 0;">{{ number_format($item['hold'] ?? 0) }}</p>
                                    </a>
                                    <a href="{{ route('warehouse.products.index', ['category' => $item['id'], 'status' => 'sold']) }}"
                                       style="background:#e0f2fe; color:#0369a1; border-radius:8px; padding:8px; text-decoration:none; display:block;">
                                        <p style="font-size:0.62rem; font-weight:800; text-transform:uppercase; margin:0;">Sold</p>
                                        <p style="font-size:1rem; font-weight:800; margin:3px 0 0;">{{ number_format($item['sold'] ?? 0) }}</p>
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Product and gate pass status graphs --}}
            <div class="mid-grid" style="margin-top: 28px; grid-template-columns: 1.5fr 1fr;">
                <div class="card">
                    <p class="card-title">Product Status</p>
                    <p class="card-subtitle">Hold, sold, issued, ready, and not-ready inventory</p>
                    <div id="productStatusChart" style="min-height: 320px; margin-top:20px;"></div>
                </div>
                <div class="card">
                    <p class="card-title">Gate Passes</p>
                    <p class="card-subtitle">Approval status distribution</p>
                    <div id="gatePassChart" style="min-height: 320px; margin-top:20px;"></div>
                </div>
            </div>

            {{-- Row 3: Operational Flow --}}
            <div class="mid-grid" style="margin-top: 28px; grid-template-columns: 1.2fr 1fr;">
                <div class="card">
                    <p class="card-title">Workflow Stages</p>
                    <p class="card-subtitle">Products currently assigned to each workflow stage</p>
                    <div id="workflowBarChart" style="min-height: 320px; margin-top:15px;"></div>
                </div>
                <div class="card">
                    <p class="card-title">Dispositions</p>
                    <p class="card-subtitle">Products grouped by their current disposition</p>
                    <div id="dispositionDonutChart" style="min-height: 320px; margin-top:15px;"></div>
                </div>
            </div>

            <div style="margin-top:28px;">
                <div class="card">
                    <p class="card-title">Technicians</p>
                    <p class="card-subtitle">Active assignments compared with completed assignments</p>
                    <div id="technicianChart" style="min-height: 360px; margin-top:15px;"></div>
                </div>
            </div>



            {{-- Legacy detail panels intentionally hidden in favor of the graph dashboard above. --}}
            @if(false)
            <div class="mid-grid">

                {{-- Testing Results --}}
                <div class="card card-inner">
                    <p class="card-title">Testing Results</p>
                    <div class="test-grid">
                        <a href="{{ route('warehouse.products.index', ['disposition_group' => 'pass']) }}" class="test-cell test-pass stat-card-link">
                            <div class="test-cell-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="test-cell-label">Pass</p>
                            <p class="test-cell-val">{{ number_format($testingResults['pass'] ?? 0) }}</p>
                        </a>
                        <a href="{{ route('warehouse.products.index', ['status' => 'scrap']) }}" class="test-cell test-scrap stat-card-link">
                            <div class="test-cell-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <p class="test-cell-label">Scrap</p>
                            <p class="test-cell-val">{{ number_format($testingResults['scrap'] ?? 0) }}</p>
                        </a>
                        <a href="{{ route('warehouse.products.index', ['disposition_group' => 'hold']) }}" class="test-cell test-hold stat-card-link">
                            <div class="test-cell-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="test-cell-label">Hold</p>
                            <p class="test-cell-val">{{ number_format($testingResults['hold'] ?? 0) }}</p>
                        </a>
                        <a href="{{ route('warehouse.products.index', ['disposition_group' => 'fail']) }}" class="test-cell test-fail stat-card-link">
                            <div class="test-cell-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="test-cell-label">Fail</p>
                            <p class="test-cell-val">{{ number_format($testingResults['fail'] ?? 0) }}</p>
                        </a>
                    </div>
                    <a href="{{ route('warehouse.products.index', ['status' => 'active', 'updated_today' => '1']) }}" class="ready-today stat-card-link">
                        <div>
                            <p class="ready-today-label">Ready For Sale Today</p>
                            <p style="font-size:0.6875rem;color:var(--text-muted);font-weight:500;margin:2px 0 0;">
                                Active status · updated today
                            </p>
                        </div>
                        <div style="text-align:right">
                            <span class="ready-today-val">{{ $readyForSaleToday ?? 0 }}</span>
                            <span class="ready-today-unit"> products</span>
                        </div>
                    </a>
                </div>

                {{-- Recent Activities --}}
                <div class="card card-inner">
                    <p class="card-title">Recent Activities</p>
                    <div class="timeline">
                        @forelse($recentActivities as $activity)
                            <div class="activity-item">
                                <div class="activity-dot {{ $activity['icon_bg'] }}">
                                    <svg class="{{ $activity['icon_color'] }}" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        @switch($activity['icon'])
                                            @case('package')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            @break

                                            @case('truck')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            @break

                                            @case('user-check')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            @break

                                            @default
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        @endswitch
                                    </svg>
                                </div>
                                <p class="activity-title">{{ $activity['title'] }}</p>
                                <p class="activity-desc">{{ $activity['description'] }}</p>
                                <div class="activity-meta">
                                    <span class="activity-time">{{ $activity['time'] }} by
                                        <strong>{{ $activity['user'] }}</strong></span>
                                    <a href="{{ $activity['link'] }}" class="activity-link">View →</a>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg width="36" height="36" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>No recent activities</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Low Stock + Scrap Summary --}}
                <div class="card card-inner" style="display:flex; flex-direction:column;">
                    <div class="card-head">
                        <p class="card-title" style="margin-bottom:0">Low Stock Parts</p>
                        <a href="{{ route('warehouse.parts.index', ['status' => 'LOW_STOCK']) }}"
                            class="card-head-link">View All →</a>
                    </div>

                    <div style="flex:1">
                        @forelse($lowStockParts as $part)
                            <div class="part-row">
                                <div class="part-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="part-info">
                                    <p class="part-name">{{ $part->name }}</p>
                                    <p class="part-num">{{ $part->part_number }}</p>
                                </div>
                                <div class="part-qty">
                                    <p class="part-qty-val {{ $part->quantity == 0 ? 'qty-out' : 'qty-low' }}">
                                        {{ $part->quantity }}<span
                                            class="part-qty-denom">/{{ $part->reorder_level }}</span>
                                    </p>
                                    <p class="part-qty-label">in stock</p>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg width="36" height="36" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>All parts well stocked</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="scrap-section">
                        <p class="section-subhead">Scrap Summary</p>
                        <div class="scrap-grid">
                            <div class="scrap-cell">
                                <p class="scrap-cell-label">Total Weight</p>
                                <p class="scrap-cell-val">{{ number_format($scrapSummary['total_weight'] ?? 0, 1) }}<span
                                        class="scrap-cell-unit">kg</span></p>
                            </div>
                            <div class="scrap-cell">
                                <p class="scrap-cell-label">In Storage</p>
                                <p class="scrap-cell-val">{{ $scrapSummary['in_storage'] ?? 0 }}</p>
                            </div>
                            <div class="scrap-cell">
                                <p class="scrap-cell-label">Disposed</p>
                                <p class="scrap-cell-val">{{ $scrapSummary['disposed'] ?? 0 }}</p>
                            </div>
                            <div class="scrap-cell">
                                <p class="scrap-cell-label">Total Items</p>
                                <p class="scrap-cell-val">
                                    {{ ($scrapSummary['in_storage'] ?? 0) + ($scrapSummary['disposed'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            @endif

        @endif
    </div>
@endsection

@push('scripts')
    @php
        $productStatusChartData = [
            $stats['hold_products'] ?? 0,
            $stats['sold_products'] ?? 0,
            $stats['issued_products'] ?? 0,
            $stats['ready_for_sale'] ?? 0,
            $stats['not_ready_for_sale'] ?? 0,
        ];
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function dashboard() {
            return {
                setup() {
                    this.initProductStatusChart();
                    this.initGatePassChart();
                    this.initWorkflowChart();
                    this.initDispositionChart();
                    this.initTechnicianChart();
                },

                initProductStatusChart() {
                    const data = @json($productStatusChartData);
                    new ApexCharts(document.getElementById('productStatusChart'), {
                        chart: { type: 'bar', height: 320, toolbar: { show: false } },
                        series: [{ name: 'Products', data }],
                        plotOptions: { bar: { borderRadius: 7, horizontal: true, barHeight: '58%', distributed: true } },
                        xaxis: {
                            categories: ['Hold', 'Sold', 'Issued', 'Ready for Sale', 'Not Ready for Sale'],
                            labels: { style: { colors: '#64748b' }, formatter: value => Number(value).toLocaleString() }
                        },
                        colors: ['#f59e0b', '#0ea5e9', '#6366f1', '#10b981', '#ef4444'],
                        legend: { show: false },
                        grid: { borderColor: '#f1f5f9' },
                        dataLabels: { enabled: true, formatter: value => Number(value).toLocaleString() },
                        tooltip: { y: { formatter: value => `${Number(value).toLocaleString()} products` } },
                        noData: { text: 'No product status data available' }
                    }).render();
                },

                initGatePassChart() {
                    const gatePasses = @json($gatePassStatus ?? []);
                    new ApexCharts(document.getElementById('gatePassChart'), {
                        chart: { type: 'donut', height: 320 },
                        series: [gatePasses.pending_qc || 0, gatePasses.pending_security || 0, gatePasses.approved || 0, gatePasses.rejected || 0],
                        labels: ['QC Pending', 'Security Pending', 'Approved', 'Rejected'],
                        colors: ['#f59e0b', '#eab308', '#10b981', '#ef4444'],
                        plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Gate Passes', formatter: options => options.globals.seriesTotals.reduce((total, value) => total + value, 0).toLocaleString() } } } } },
                        legend: { position: 'bottom' },
                        dataLabels: { enabled: false },
                        noData: { text: 'No gate pass data available' }
                    }).render();
                },

                initWorkflowChart() {
                    const data = @json($workflowChart ?? []);
                    new ApexCharts(document.getElementById('workflowBarChart'), {
                        chart: { type: 'bar', height: 350, toolbar: { show: false } },
                        series: [{ name: 'Products', data: data.map(d => d.count) }],
                        plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '60%', distributed: true } },
                        xaxis: { categories: data.map(d => d.name), labels: { style: { colors: '#64748b' } } },
                        colors: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1'],
                        legend: { show: false },
                        dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'] }, offsetX: 10 }
                    }).render();
                },

                initDispositionChart() {
                    const data = @json($dispositionChart ?? []);
                    new ApexCharts(document.getElementById('dispositionDonutChart'), {
                        chart: { type: 'donut', height: 350 },
                        series: data.map(d => d.count),
                        labels: data.map(d => d.name),
                        colors: data.map(d => d.color),
                        plotOptions: { pie: { donut: { size: '70%' } } },
                        legend: { position: 'bottom' }
                    }).render();
                },

                initTechnicianChart() {
                    const technicians = @json($technicianStats ?? []);
                    const chartHeight = Math.max(360, technicians.length * 46);
                    new ApexCharts(document.getElementById('technicianChart'), {
                        chart: { type: 'bar', height: chartHeight, toolbar: { show: false } },
                        series: [
                            { name: 'Active', data: technicians.map(technician => technician.active_count) },
                            { name: 'Completed', data: technicians.map(technician => technician.completed_count) }
                        ],
                        plotOptions: { bar: { borderRadius: 5, horizontal: true, barHeight: '65%' } },
                        xaxis: {
                            categories: technicians.map(technician => technician.full_name),
                            labels: { style: { colors: '#64748b' }, formatter: value => Number(value).toLocaleString() }
                        },
                        colors: ['#f59e0b', '#10b981'],
                        legend: { position: 'top', horizontalAlign: 'right' },
                        grid: { borderColor: '#f1f5f9' },
                        dataLabels: { enabled: false },
                        tooltip: { y: { formatter: value => `${Number(value).toLocaleString()} assignments` } },
                        noData: { text: 'No technician assignment data available' }
                    }).render();
                }
            };
        }
    </script>
@endpush
