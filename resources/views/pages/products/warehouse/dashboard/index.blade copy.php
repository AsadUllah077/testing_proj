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
            --radius: 14px;
            --radius-sm: 8px;
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
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 24px;
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
            padding: 22px 22px 18px;
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
            color: var(--text-muted);
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
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 18px;
            letter-spacing: -0.01em;
        }

        .test-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }

        .test-cell {
            border-radius: var(--radius-sm);
            padding: 14px 12px;
            text-align: center;
            border: 1.5px solid;
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
            padding: 10px 12px;
            background: var(--surface-muted);
            border-radius: var(--radius-sm);
            border: 1.5px solid transparent;
            transition: border-color 0.15s, background 0.15s;
            margin-bottom: 8px;
        }

        .part-row:hover {
            border-color: var(--border);
            background: #f1f5f9;
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
                <h1>Warehouse Dashboard</h1>
                <p>Overview of your warehouse operations</p>
            </div>
            <div class="header-controls">
                <select x-model="days" @change="refreshCharts()" class="period-select">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="60">Last 60 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <button @click="refreshCharts()" class="refresh-btn" title="Refresh">
                    <svg :class="{ 'spinning': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
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

            {{-- ===== STAT CARDS ===== --}}
            <div class="stats-grid">

                {{-- Total Inventory --}}
                <a href="{{ route('warehouse.products.index') }}" class="stat-card-link card stat-card">
                    <div class="stat-top">
                        <div>
                            <p class="stat-label">Total Inventory</p>
                            <p class="stat-value">{{ number_format($stats['total_products'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#eff6ff">
                            <svg fill="none" stroke="#3b82f6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    @php $change = floatval($stats['total_products_change'] ?? '0'); @endphp
                    <div class="stat-delta">
                        <svg class="{{ $change >= 0 ? 'delta-up' : 'delta-down' }}" fill="currentColor" viewBox="0 0 20 20">
                            @if ($change >= 0)
                                <path fill-rule="evenodd"
                                    d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            @else
                                <path fill-rule="evenodd"
                                    d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            @endif
                        </svg>
                        <span class="delta-label {{ $change >= 0 ? 'delta-up' : 'delta-down' }}">{{ $stats['total_products_change'] ?? '0%' }}</span>
                        <span class="delta-period">vs last {{ $days }}d</span>
                    </div>
                </a>

                {{-- Ready For Sale --}}
                {{-- Ready For Sale Card --}}
                <div class="card stat-card">
                    <div class="stat-top">
                        <div class="cursor-pointer" onclick="window.location.href='{{ route('warehouse.products.index', ['status' => 'active']) }}'">
                            <p class="stat-label">Ready For Sale</p>
                            <p class="stat-value">{{ number_format($stats['ready_for_sale'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#f0fdf4">
                            <svg fill="none" stroke="#10b981" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    {{-- Active / Inactive badge row --}}
                    <div class="badge-row">
                        <a href="{{ route('warehouse.products.index', ['status' => 'active']) }}"
                           class="badge badge-green badge-link">
                            ✓ Active: {{ number_format($stats['ready_for_sale'] ?? 0) }}
                        </a>
                        <a href="{{ route('warehouse.products.index', ['status' => 'inactive']) }}"
                           class="badge badge-link" style="background:#fee2e2;color:#991b1b;">
                            ✕ Inactive: {{ number_format($stats['not_ready_for_sale'] ?? 0) }}
                        </a>
                    </div>
                    @php $change = floatval($stats['ready_for_sale_change'] ?? '0'); @endphp
                    <div class="stat-delta" style="margin-top:6px">
                        <svg class="{{ $change >= 0 ? 'delta-up' : 'delta-down' }}" fill="currentColor" viewBox="0 0 20 20">
                            @if ($change >= 0)
                                <path fill-rule="evenodd"
                                    d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            @else
                                <path fill-rule="evenodd"
                                    d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            @endif
                        </svg>
                        <span class="delta-label {{ $change >= 0 ? 'delta-up' : 'delta-down' }}">{{ $stats['ready_for_sale_change'] ?? '0%' }}</span>
                        <span class="delta-period">vs last {{ $days }}d</span>
                    </div>
                </div>

                {{-- Active Technicians --}}
                <a href="{{ route('warehouse.technicians.index', ['status' => 'In Progress']) }}" class="stat-card-link card stat-card">
                    <div class="stat-top">
                        <div>
                            <p class="stat-label">Active Technicians</p>
                            <p class="stat-value">
                                {{ $stats['active_technicians'] ?? 0 }}<span
                                    style="font-size:1.1rem;color:var(--text-muted);font-weight:500">/{{ $stats['total_technicians'] ?? 0 }}</span>
                            </p>
                        </div>
                        <div class="stat-icon" style="background:#f5f3ff">
                            <svg fill="none" stroke="#8b5cf6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    @php
                        $total = $stats['total_technicians'] ?? 0;
                        $active = $stats['active_technicians'] ?? 0;
                        $pct = $total > 0 ? ($active / $total) * 100 : 0;
                    @endphp
                    <div class="progress-wrap">
                        <div class="progress-track">
                            <div class="progress-fill" style="width:{{ min($pct, 100) }}%; background:#8b5cf6;"></div>
                        </div>
                        <span class="progress-pct">{{ number_format($pct, 0) }}%</span>
                    </div>
                </a>

                {{-- Gate Passes --}}
                <a href="{{ route('warehouse.gate-pass.index') }}" class="stat-card-link card stat-card">
                    <div class="stat-top">
                        <div>
                            <p class="stat-label">Gate Passes</p>
                            <p class="stat-value">{{ number_format($stats['total_gate_passes'] ?? 0) }}</p>
                        </div>
                        <div class="stat-icon" style="background:#fffbeb">
                            <svg fill="none" stroke="#f59e0b" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="badge-row">
                        <span onclick="event.stopPropagation(); window.location.href='{{ route('warehouse.gate-pass.index', ['status' => 'QC_APPROVAL_PENDING']) }}'"
                           class="badge badge-yellow badge-link">
                            QC: {{ $gatePassStatus['pending_qc'] ?? 0 }}
                        </span>
                        <span onclick="event.stopPropagation(); window.location.href='{{ route('warehouse.gate-pass.index', ['status' => 'SECURITY_APPROVAL_PENDING']) }}'"
                           class="badge badge-orange badge-link">
                            Sec: {{ $gatePassStatus['pending_security'] ?? 0 }}
                        </span>
                        <span onclick="event.stopPropagation(); window.location.href='{{ route('warehouse.gate-pass.index', ['status' => 'QC_AND_SECURITY_APPROVED']) }}'"
                           class="badge badge-green badge-link">
                            ✓ {{ $gatePassStatus['approved'] ?? 0 }}
                        </span>
                    </div>
                </a>

            </div>

            {{-- ===== CATEGORY WISE COUNT ===== --}}
            <div class="card card-inner" style="margin-bottom: 24px;">
                <p class="card-title">Products by Category</p>
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); margin-bottom: 0;">
                    @forelse($categoryWiseCount ?? [] as $category)
                        <div class="stat-card" style="border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 16px;">
                            <p class="stat-label" style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: none; letter-spacing: normal;">{{ $category['name'] }}</p>
                            <p class="stat-value" style="font-size: 1.5rem;">{{ number_format($category['count']) }}</p>
                        </div>
                    @empty
                        <div class="empty-state" style="grid-column: 1 / -1; padding: 24px;">
                            <p>No categorised products</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ===== CHARTS ROW (full width, side by side) ===== --}}
            <div class="charts-row">

                {{-- Disposition Donut Chart --}}
                <div class="card card-inner">
                    <p class="card-title">Products by Disposition</p>
                    <div id="dispositionChart"></div>
                </div>

                {{-- Products Ready For Sale Today — Area Chart (09:00–19:00) --}}
                <div class="card" style="padding: 24px 24px 12px; border-radius: 18px; border: 1.5px solid var(--border); background:#fff; overflow:hidden;">
                    <p style="font-size:1.15rem; font-weight:800; color:var(--text-primary); margin:0 0 4px; letter-spacing:-0.02em;">Products Ready For Sale Today</p>
                    <div id="readyForSaleChart" style="margin: 0 -8px;"></div>
                </div>

            </div>

            {{-- ===== TESTING RESULTS + BOTTOM ROW ===== --}}
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
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function dashboard() {
            return {
                days: {{ $days ?? 30 }},
                loading: false,
                dispositionChartInstance: null,
                readyForSaleChartInstance: null,

                setup() {
                    @if (!isset($error))
                        this.initDispositionChart();
                        this.initReadyForSaleChart();
                        setInterval(() => this.refreshCharts(), 300_000);
                    @endif
                },

                initDispositionChart() {
                    const data = @json($dispositionChart ?? []);
                    if (!data.length) {
                        document.getElementById('dispositionChart').innerHTML =
                            '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:0.8125rem;font-family:Plus Jakarta Sans,sans-serif;">No data available</div>';
                        return;
                    }
                    this.dispositionChartInstance = new ApexCharts(
                        document.getElementById('dispositionChart'), {
                            chart: {
                                type: 'donut',
                                height: 280,
                                fontFamily: 'Plus Jakarta Sans, sans-serif',
                            },
                            series: data.map(d => d.count),
                            labels: data.map(d => d.name),
                            colors: data.map(d => d.color),
                            legend: {
                                position: 'bottom',
                                fontSize: '12px',
                                fontWeight: 600,
                                itemMargin: { horizontal: 10, vertical: 4 },
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '65%',
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Total',
                                                fontSize: '12px',
                                                fontWeight: 700,
                                                color: '#64748b',
                                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
                                            },
                                            value: {
                                                fontSize: '22px',
                                                fontWeight: 800,
                                                color: '#0f172a',
                                            },
                                        },
                                    },
                                },
                            },
                            dataLabels: { enabled: false },
                            tooltip: { y: { formatter: val => val.toLocaleString() + ' products' } },
                            stroke: { width: 2 },
                        }
                    );
                    this.dispositionChartInstance.render();
                },

                initReadyForSaleChart() {
                    const timeSlotData = @json($timeSlotData ?? []);

                    // Build labels 09:00 → 19:00 every 30 min
                    const labels = [];
                    const seriesData = [];
                    const slotMap = {};
                    timeSlotData.forEach(slot => { slotMap[slot.time] = slot.count; });

                    let h = 9, m = 0;
                    while (h < 19 || (h === 19 && m === 0)) {
                        const label = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        labels.push(label);
                        seriesData.push(slotMap[label] || 0);
                        m += 30;
                        if (m >= 60) { m = 0; h++; }
                    }

                    // Only show 3 evenly spaced x-axis labels like in the reference image
                    const showLabels = [labels[0], labels[Math.floor(labels.length / 2)], labels[labels.length - 1]];

                    this.readyForSaleChartInstance = new ApexCharts(
                        document.getElementById('readyForSaleChart'), {
                            chart: {
                                type: 'area',
                                height: 200,
                                fontFamily: 'Plus Jakarta Sans, sans-serif',
                                toolbar: { show: false },
                                zoom: { enabled: false },
                                sparkline: { enabled: false },
                                animations: { enabled: true, easing: 'easeinout', speed: 800 },
                                background: 'transparent',
                            },
                            series: [{ name: 'Products Ready', data: seriesData }],
                            xaxis: {
                                categories: labels,
                                labels: {
                                    rotate: 0,
                                    style: {
                                        fontSize: '13px',
                                        fontWeight: 600,
                                        colors: Array(labels.length).fill('#94a3b8'),
                                        fontFamily: 'Plus Jakarta Sans, sans-serif',
                                    },
                                    formatter: function(val) {
                                        return showLabels.includes(val) ? val : '';
                                    },
                                    offsetY: 4,
                                },
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                crosshairs: { show: false },
                                tooltip: { enabled: false },
                            },
                            yaxis: { show: false, min: 0 },
                            grid: {
                                show: false,
                                padding: { left: 10, right: 10, top: 10, bottom: 0 },
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2,
                                colors: ['#5bc8d8'],
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    type: 'vertical',
                                    shadeIntensity: 0,
                                    opacityFrom: 0.25,
                                    opacityTo: 0.03,
                                    stops: [0, 100],
                                    colorStops: [
                                        { offset: 0,   color: '#5bc8d8', opacity: 0.25 },
                                        { offset: 100, color: '#5bc8d8', opacity: 0.03 },
                                    ],
                                },
                            },
                            colors: ['#5bc8d8'],
                            dataLabels: { enabled: false },
                            markers: { size: 0, hover: { size: 0 } },
                            tooltip: {
                                theme: 'light',
                                x: { show: true },
                                y: { formatter: val => val + ' products' },
                                marker: { show: false },
                            },
                            legend: { show: false },
                        }
                    );
                    this.readyForSaleChartInstance.render();
                },

                async refreshCharts() {
                    // Placeholder — extend with API call to refresh data if needed
                },
            };
        }
    </script>
@endpush
