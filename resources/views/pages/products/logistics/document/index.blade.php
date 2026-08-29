@extends('layouts.auth')

@section('title', 'Document Management')

@push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --brand: #0078C8;
            --brand-light: #3399E0;
            --brand-glow: rgba(0, 120, 200, 0.18);
            --surface: #ffffff;
            --surface-2: #f8f9fb;
            --surface-3: #f1f3f6;
            --border: rgba(0, 0, 0, 0.07);
            --border-hover: rgba(0, 120, 200, 0.35);
            --text-1: #0d1117;
            --text-2: #4a5568;
            --text-3: #9aa5b4;
            --amber: #f59e0b;
            --amber-bg: #fffbeb;
            --amber-border: #fde68a;
            --red: #ef4444;
            --green: #10b981;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08), 0 2px 6px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.06);
            --shadow-glow: 0 0 0 3px var(--brand-glow);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            font-family: 'DM Sans', system-ui, sans-serif;
            box-sizing: border-box;
        }

        /* ── Page Shell ── */
        .dm-page {
            padding: 24px;
            background: var(--surface-2);
            min-height: 100vh;
        }

        /* ── Header Card ── */
        .dm-header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            animation: slideDown 0.4s var(--transition-ease) both;
        }

        .dm-header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-1);
            letter-spacing: -0.3px;
        }

        .dm-header-sub {
            font-size: 12px;
            color: var(--text-3);
            margin-top: 2px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.18s var(--transition-ease);
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: white;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .btn:hover::after {
            opacity: 0.08;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: var(--brand);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 120, 200, 0.3);
        }

        .btn-primary:hover {
            background: #006bb0;
            box-shadow: 0 4px 14px rgba(0, 120, 200, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f1f3f6;
            color: var(--text-2);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #e8eaed;
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-2);
            padding: 7px 10px;
        }

        .btn-ghost:hover {
            background: var(--surface-3);
        }

        .btn-ghost.active {
            background: rgba(0, 120, 200, 0.08);
            color: var(--brand);
        }

        /* ── Breadcrumb ── */
        .dm-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            animation: slideDown 0.45s var(--transition-ease) 0.05s both;
        }

        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
        }

        .breadcrumb-link {
            color: var(--brand);
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 5px;
            transition: background 0.15s;
            font-weight: 500;
        }

        .breadcrumb-link:hover {
            background: rgba(0, 120, 200, 0.08);
        }

        .breadcrumb-sep {
            color: var(--text-3);
            font-size: 11px;
            margin: 0 1px;
        }

        .item-count {
            font-size: 11px;
            color: var(--text-3);
            background: var(--surface-3);
            padding: 3px 10px;
            border-radius: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
        }

        /* ── Explorer Container ── */
        .dm-explorer {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            animation: slideUp 0.45s var(--transition-ease) 0.1s both;
        }

        .dm-explorer-header {
            padding: 14px 20px;
            background: linear-gradient(to right, var(--surface-2), var(--surface));
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dm-explorer-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-2);
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .view-toggle {
            display: flex;
            gap: 2px;
        }

        /* ── Grid View ── */
        #gridContainer {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
        }

        /* ── Item Cards ── */
        .dm-item {
            position: relative;
            border-radius: var(--radius-sm);
            cursor: pointer;
            animation: itemAppear 0.35s var(--transition) both;
        }

        .dm-item-card {
            padding: 16px 12px 12px;
            border-radius: var(--radius-sm);
            border: 1.5px solid transparent;
            transition: all 0.22s var(--transition-ease);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .dm-item-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 60%, rgba(255, 255, 255, 0.6));
            pointer-events: none;
        }

        /* Folder */
        .dm-item.folder .dm-item-card {
            background: linear-gradient(145deg, #fffdf0, #fef3c7);
            border-color: var(--amber-border);
        }

        .dm-item.folder:hover .dm-item-card {
            border-color: var(--amber);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.2), 0 2px 6px rgba(245, 158, 11, 0.1);
            transform: translateY(-3px);
        }

        /* Document */
        .dm-item.document .dm-item-card {
            background: var(--surface);
            border-color: var(--border);
        }

        .dm-item.document:hover .dm-item-card {
            border-color: var(--border-hover);
            box-shadow: 0 6px 20px var(--brand-glow), 0 2px 6px rgba(0, 0, 0, 0.06);
            transform: translateY(-3px);
        }

        /* Icon */
        .dm-item-icon {
            font-size: 30px;
            margin-bottom: 10px;
            display: block;
            line-height: 1;
            transition: transform 0.3s var(--transition);
        }

        .dm-item:hover .dm-item-icon {
            transform: scale(1.12) translateY(-2px);
        }

        .folder-icon {
            color: var(--amber);
            filter: drop-shadow(0 3px 6px rgba(245, 158, 11, 0.35));
        }

        .file-icon {
            color: var(--brand);
            filter: drop-shadow(0 3px 6px rgba(0, 120, 200, 0.25));
        }

        .dm-item-name {
            font-size: 11.5px;
            font-weight: 500;
            color: var(--text-1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .dm-item-meta {
            font-size: 10px;
            color: var(--text-3);
            margin-top: 3px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Actions overlay */
        .dm-item-actions {
            position: absolute;
            bottom: -40px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 4px;
            transition: all 0.22s var(--transition-ease);
            padding: 0 8px;
        }

        .dm-item:hover .dm-item-actions {
            bottom: 2px;
        }

        .action-btn {
            padding: 3px 8px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 10px;
            font-weight: 500;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .action-btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--red);
        }

        .action-btn-delete:hover {
            background: rgba(239, 68, 68, 0.18);
        }

        .action-btn-download {
            background: rgba(16, 185, 129, 0.1);
            color: var(--green);
        }

        .action-btn-download:hover {
            background: rgba(16, 185, 129, 0.18);
        }

        /* ── List View ── */
        #listContainer {
            display: none;
            overflow-x: auto;
        }

        .dm-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dm-table thead th {
            padding: 11px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }

        .dm-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            animation: rowSlide 0.3s var(--transition-ease) both;
        }

        .dm-table tbody tr:hover {
            background: var(--surface-2);
        }

        .dm-table tbody tr:last-child {
            border-bottom: none;
        }

        .dm-table td {
            padding: 11px 16px;
            font-size: 13px;
            color: var(--text-2);
            vertical-align: middle;
        }

        .dm-table .td-name {
            color: var(--text-1);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dm-table .td-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        /* ── Empty State ── */
        .empty-state {
            grid-column: 1 / -1;
            padding: 64px 24px;
            text-align: center;
            animation: fadeIn 0.4s ease both;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            background: var(--surface-3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 26px;
            color: var(--text-3);
        }

        .empty-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-2);
            margin-bottom: 6px;
        }

        .empty-sub {
            font-size: 13px;
            color: var(--text-3);
        }

        /* ── Modals ── */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(13, 17, 23, 0.55);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.22s ease;
        }

        .modal-backdrop.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            background: var(--surface);
            border-radius: 16px;
            padding: 28px;
            width: 420px;
            max-width: calc(100vw - 32px);
            box-shadow: var(--shadow-lg), 0 0 0 1px var(--border);
            transform: scale(0.92) translateY(12px);
            transition: transform 0.3s var(--transition);
        }

        .modal-backdrop.open .modal {
            transform: scale(1) translateY(0);
        }

        .modal-title {
            font-size: 17px;
            font-weight: 600;
            color: var(--text-1);
            margin-bottom: 20px;
            letter-spacing: -0.3px;
        }

        .modal-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-2);
            margin-bottom: 6px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .modal-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--text-1);
            outline: none;
            transition: border-color 0.18s, box-shadow 0.18s;
            background: var(--surface-2);
        }

        .modal-input:focus {
            border-color: var(--brand);
            box-shadow: var(--shadow-glow);
            background: var(--surface);
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 22px;
        }

        /* ── Drop Zone ── */
        .drop-zone {
            border: 2px dashed #d1d9e0;
            border-radius: var(--radius-sm);
            padding: 32px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s var(--transition-ease);
            background: var(--surface-2);
            position: relative;
            margin-bottom: 0;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--brand);
            background: rgba(0, 120, 200, 0.04);
            box-shadow: var(--shadow-glow);
        }

        .drop-zone.has-file {
            border-color: var(--green);
            background: rgba(16, 185, 129, 0.04);
        }

        .drop-zone-icon {
            font-size: 28px;
            color: var(--text-3);
            margin-bottom: 10px;
            display: block;
            transition: transform 0.3s var(--transition), color 0.2s;
        }

        .drop-zone:hover .drop-zone-icon {
            transform: translateY(-4px);
            color: var(--brand);
        }

        .drop-zone.has-file .drop-zone-icon {
            color: var(--green);
        }

        .drop-zone-text {
            font-size: 13px;
            color: var(--text-2);
        }

        .drop-zone-text span {
            color: var(--brand);
            font-weight: 500;
        }

        .drop-zone-file {
            font-size: 12px;
            color: var(--green);
            margin-top: 6px;
            font-weight: 500;
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── Toast ── */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 200;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: var(--text-1);
            color: white;
            padding: 12px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastIn 0.35s var(--transition) both;
            min-width: 220px;
        }

        .toast.success {
            background: #0d7a50;
        }

        .toast.error {
            background: #b91c1c;
        }

        .toast.exiting {
            animation: toastOut 0.3s ease forwards;
        }

        .toast-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            flex-shrink: 0;
        }

        .toast.success .toast-dot {
            background: #6ee7b7;
        }

        .toast.error .toast-dot {
            background: #fca5a5;
        }

        /* ── Loading Overlay ── */
        .loader-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .loader-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .loader-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--surface-3);
            border-top-color: var(--brand);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        /* ── Progress Bar (upload) ── */
        .upload-progress {
            display: none;
            margin-top: 12px;
        }

        .upload-progress.active {
            display: block;
        }

        .progress-track {
            height: 4px;
            background: var(--surface-3);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--brand);
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease;
            animation: progressPulse 1.5s ease-in-out infinite;
        }

        .progress-label {
            font-size: 11px;
            color: var(--text-3);
            margin-top: 5px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── Tab Modules ── */
        .module-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* ── Animations ── */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes itemAppear {
            from {
                opacity: 0;
                transform: scale(0.88) translateY(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes rowSlide {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(24px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes toastOut {
            to {
                opacity: 0;
                transform: translateX(24px) scale(0.95);
                height: 0;
                padding: 0;
                margin: 0;
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes progressPulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }
        }

        @keyframes folderBounce {
            0% {
                transform: scale(1) translateY(0);
            }

            30% {
                transform: scale(1.15) translateY(-6px);
            }

            60% {
                transform: scale(0.95) translateY(-2px);
            }

            100% {
                transform: scale(1) translateY(0);
            }
        }

        /* Stagger animations for items */
        .dm-item:nth-child(1) {
            animation-delay: 0.00s;
        }

        .dm-item:nth-child(2) {
            animation-delay: 0.04s;
        }

        .dm-item:nth-child(3) {
            animation-delay: 0.08s;
        }

        .dm-item:nth-child(4) {
            animation-delay: 0.12s;
        }

        .dm-item:nth-child(5) {
            animation-delay: 0.16s;
        }

        .dm-item:nth-child(6) {
            animation-delay: 0.20s;
        }

        .dm-item:nth-child(7) {
            animation-delay: 0.24s;
        }

        .dm-item:nth-child(8) {
            animation-delay: 0.28s;
        }

        .dm-item:nth-child(9) {
            animation-delay: 0.32s;
        }

        .dm-item:nth-child(10) {
            animation-delay: 0.36s;
        }

        .dm-table tbody tr:nth-child(1) {
            animation-delay: 0.00s;
        }

        .dm-table tbody tr:nth-child(2) {
            animation-delay: 0.04s;
        }

        .dm-table tbody tr:nth-child(3) {
            animation-delay: 0.08s;
        }

        .dm-table tbody tr:nth-child(4) {
            animation-delay: 0.12s;
        }

        .dm-table tbody tr:nth-child(5) {
            animation-delay: 0.16s;
        }

        /* Deleting animation */
        .dm-item.deleting {
            animation: shrinkOut 0.3s var(--transition-ease) forwards !important;
        }

        .dm-table tbody tr.deleting {
            animation: rowDelete 0.3s ease forwards !important;
        }

        @keyframes shrinkOut {
            to {
                opacity: 0;
                transform: scale(0.8);
            }
        }

        @keyframes rowDelete {
            to {
                opacity: 0;
                transform: translateX(20px);
            }
        }

        /* Creating animation */
        .dm-item.new-item {
            animation: newItemPop 0.45s var(--transition) both !important;
        }

        @keyframes newItemPop {
            from {
                opacity: 0;
                transform: scale(0.5);
            }

            70% {
                transform: scale(1.08);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
@endpush

@section('content')
    <x-acccount.loader />
    <div class="dm-page">
        {{-- Header --}}
        <div class="dm-header">
            <div>
                <div class="dm-header-title">Document Management</div>
                <div class="dm-header-sub">Organize and manage LC-related documents &amp; files</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <button class="btn btn-secondary" id="newFolderBtn">
                    <i class="fa-solid fa-folder-plus"></i> New Folder
                </button>
                <button class="btn btn-primary" id="uploadDocBtn">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                </button>
            </div>
        </div>

        {{-- Breadcrumb --}}
        <div class="dm-breadcrumb">
            <div class="breadcrumb-nav" id="breadcrumbContainer">
                <span class="breadcrumb-link" onclick="navigateToFolder(null)">
                    <i class="fa-solid fa-house" style="font-size:11px;margin-right:3px;"></i>Home
                </span>
                <span id="breadcrumbPath"></span>
            </div>
            <span class="item-count" id="itemCount">{{ count($items) }} items</span>
        </div>

        {{-- Explorer --}}
        <div class="dm-explorer" style="position:relative;">
            <div class="loader-overlay" id="loaderOverlay">
                <div class="loader-spinner"></div>
            </div>

            <div class="dm-explorer-header">
                <span class="dm-explorer-title">Files &amp; Folders</span>
                <div class="view-toggle">
                    <button class="btn btn-ghost" id="listViewBtn" title="List View">
                        <i class="fa-solid fa-list" style="font-size:13px;"></i>
                    </button>
                    <button class="btn btn-ghost active" id="gridViewBtn" title="Grid View">
                        <i class="fa-solid fa-grip" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>

            {{-- Grid View --}}
            <div id="gridContainer">
                @forelse ($items as $item)
                    @if ($item['type'] === 'folder')
                        <div class="dm-item folder" data-id="{{ $item['id'] }}" data-type="folder"
                            onclick="navigateToFolder('{{ $item['id'] }}')">
                            <div class="dm-item-card">
                                <i class="fa-solid fa-folder dm-item-icon folder-icon"></i>
                                <div class="dm-item-name">{{ $item['name'] }}</div>
                                <div class="dm-item-meta">{{ $item['count'] ?? 0 }} items</div>
                            </div>
                            <div class="dm-item-actions">
                                <button class="action-btn action-btn-delete"
                                    onclick="deleteFolder(event, '{{ $item['id'] }}')">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="dm-item document" data-id="{{ $item['id'] }}" data-type="document">
                            <div class="dm-item-card">
                                <i class="fa-solid fa-file-{{ $item['icon'] ?? 'pdf' }} dm-item-icon file-icon"></i>
                                <div class="dm-item-name">{{ $item['name'] }}</div>
                                <div class="dm-item-meta">{{ $item['size'] }}</div>
                            </div>
                            <div class="dm-item-actions">
                                <a href="{{ route('logistics.documents.download', $item['id']) }}"
                                    class="action-btn action-btn-download" onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <button class="action-btn action-btn-delete"
                                    onclick="deleteDocument(event, '{{ $item['id'] }}')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="empty-title">This folder is empty</div>
                        <div class="empty-sub">Upload a document or create a folder to get started</div>
                    </div>
                @endforelse
            </div>

            {{-- List View --}}
            <div id="listContainer">
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="listBody">
                        @forelse ($items as $item)
                            <tr data-id="{{ $item['id'] }}" data-type="{{ $item['type'] }}"
                                class="{{ $item['type'] === 'folder' ? 'cursor-pointer' : '' }}"
                                onclick="{{ $item['type'] === 'folder' ? "navigateToFolder('{$item['id']}')" : '' }}">
                                <td class="td-name">
                                    <i class="fa-solid fa-{{ $item['type'] === 'folder' ? 'folder' : 'file-' . $item['icon'] }}"
                                        style="color:{{ $item['type'] === 'folder' ? 'var(--amber)' : 'var(--brand)' }};font-size:14px;"></i>
                                    {{ $item['name'] }}
                                </td>
                                <td>{{ ucfirst($item['type']) }}</td>
                                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;">
                                    {{ $item['size'] ?? '—' }}</td>
                                <td>{{ $item['modified'] ?? '—' }}</td>
                                <td onclick="event.stopPropagation()">
                                    <div class="td-actions">
                                        @if ($item['type'] !== 'folder')
                                            <a href="{{ route('logistics.documents.download', $item['id']) }}"
                                                class="btn btn-ghost"
                                                style="color:var(--green);padding:5px 8px;font-size:13px;">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        @endif
                                        <button class="btn btn-ghost"
                                            style="color:var(--red);padding:5px 8px;font-size:13px;"
                                            onclick="{{ $item['type'] === 'folder' ? "deleteFolder(event, '{$item['id']}')" : "deleteDocument(event, '{$item['id']}')" }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    style="text-align:center;padding:48px;color:var(--text-3);font-size:13px;">
                                    No files or folders yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Toast Container --}}
    <div class="toast-container" id="toastContainer"></div>

    {{-- New Folder Modal --}}
    <div class="modal-backdrop" id="newFolderModal">
        <div class="modal">
            <div class="modal-title"><i class="fa-solid fa-folder-plus"
                    style="color:var(--brand);margin-right:10px;"></i>Create New Folder</div>
            <form id="folderForm">
                <div style="margin-bottom:16px;">
                    <label class="modal-label">Folder Name</label>
                    <input type="text" id="folderName" name="folder_name" class="modal-input"
                        placeholder="e.g., Shipment Documents" required autocomplete="off">
                    <input type="hidden" id="parentFolderId" name="parent_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="closeModal('newFolderModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createFolderBtn">
                        <i class="fa-solid fa-plus"></i> Create Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Upload Modal --}}
    <div class="modal-backdrop" id="uploadModal">
        <div class="modal">
            <div class="modal-title"><i class="fa-solid fa-cloud-arrow-up"
                    style="color:var(--brand);margin-right:10px;"></i>Upload Document</div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div style="margin-bottom:16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="modal-label">Booking ID</label>
                        <input type="text" name="booking_id" class="modal-input" placeholder="e.g., BK-12345" autocomplete="off">
                    </div>
                    <div>
                        <label class="modal-label">Document Type</label>
                        <select name="document_type" class="modal-input">
                            <option value="">-- Optional --</option>
                            <option value="Invoice">Invoice</option>
                            <option value="Packing List">Packing List</option>
                            <option value="BL Copy">BL Copy</option>
                            <option value="Certificate of Origin">Certificate of Origin</option>
                            <option value="Bank Receipt">Bank Receipt</option>
                            <option value="Transaction Record">Transaction Record</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="modal-label">Destination</label>
                    <input type="text" id="currentFolderDisplay" class="modal-input" readonly
                        style="cursor:default;font-size:13px;">
                    <input type="hidden" id="uploadFolderId" name="folder_id">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="modal-label">File</label>
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="documentFile" name="document"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" hidden>
                        <i class="fa-solid fa-cloud-arrow-up drop-zone-icon"></i>
                        <div class="drop-zone-text">Drop your file here or <span>browse</span></div>
                        <div class="drop-zone-file" id="fileName"></div>
                    </div>
                    <div style="font-size:11px;color:var(--text-3);margin-top:6px;">PDF, Word, Excel, PNG, JPG · Max 10 MB
                    </div>
                </div>
                <div class="upload-progress" id="uploadProgress">
                    <div class="progress-track">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-label" id="progressLabel">Uploading…</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal" style="max-width:360px;">
            <div style="text-align:center;margin-bottom:20px;">
                <div
                    style="width:52px;height:52px;background:rgba(239,68,68,0.1);border-radius:50%;
                display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:22px;color:var(--red);">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="modal-title" style="margin-bottom:6px;" id="deleteModalTitle">Delete Item?</div>
                <p style="font-size:13px;color:var(--text-2);" id="deleteModalDesc">This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="justify-content:center;">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn" style="background:var(--red);color:white;box-shadow:0 2px 8px rgba(239,68,68,0.3);"
                    id="confirmDeleteBtn">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentFolderId = null;
        let pendingDelete = null;

        // ── Toast ──────────────────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            const c = document.getElementById('toastContainer');
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            t.innerHTML = `<div class="toast-dot"></div>${msg}`;
            c.appendChild(t);
            setTimeout(() => {
                t.classList.add('exiting');
                setTimeout(() => t.remove(), 350);
            }, 3000);
        }

        // ── Loader ────────────────────────────────────────────────────────
        function setLoading(on) {
            document.getElementById('loaderOverlay').classList.toggle('active', on);
        }

        // ── Modal ─────────────────────────────────────────────────────────
        function openModal(id) {
            document.getElementById(id).classList.add('open');
            setTimeout(() => {
                const input = document.querySelector(`#${id} input:not([type=hidden]):not([readonly])`);
                if (input) input.focus();
            }, 120);
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        document.querySelectorAll('.modal-backdrop').forEach(b => {
            b.addEventListener('click', e => {
                if (e.target === b) closeModal(b.id);
            });
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') document.querySelectorAll('.modal-backdrop.open').forEach(b => closeModal(b
                .id));
        });

        // ── New Folder Button ──────────────────────────────────────────────
        document.getElementById('newFolderBtn').addEventListener('click', () => {
            document.getElementById('parentFolderId').value = currentFolderId || '';
            document.getElementById('folderName').value = '';
            openModal('newFolderModal');
        });

        // ── Upload Button ──────────────────────────────────────────────────
        document.getElementById('uploadDocBtn').addEventListener('click', () => {
            document.getElementById('uploadFolderId').value = currentFolderId || '';
            document.getElementById('currentFolderDisplay').value = currentFolderId ? 'Current Folder' : '/ Root';
            document.getElementById('documentFile').value = '';
            document.getElementById('fileName').textContent = '';
            document.getElementById('dropZone').classList.remove('has-file');
            document.getElementById('uploadProgress').classList.remove('active');
            openModal('uploadModal');
        });

        // ── Drag & Drop ────────────────────────────────────────────────────
        const dropZone = document.getElementById('dropZone');
        dropZone.addEventListener('click', () => document.getElementById('documentFile').click());
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) setFileUI(file);
            document.getElementById('documentFile').files = e.dataTransfer.files;
        });
        document.getElementById('documentFile').addEventListener('change', e => {
            if (e.target.files[0]) setFileUI(e.target.files[0]);
        });

        function setFileUI(file) {
            dropZone.classList.add('has-file');
            document.getElementById('fileName').textContent = file.name;
            dropZone.querySelector('.drop-zone-icon').className = 'fa-solid fa-circle-check drop-zone-icon';
        }

        // ── View Toggle ────────────────────────────────────────────────────
        document.getElementById('gridViewBtn').addEventListener('click', () => setView('grid'));
        document.getElementById('listViewBtn').addEventListener('click', () => setView('list'));

        function setView(v) {
            const isGrid = v === 'grid';
            document.getElementById('gridContainer').style.display = isGrid ? 'grid' : 'none';
            document.getElementById('listContainer').style.display = isGrid ? 'none' : 'block';
            document.getElementById('gridViewBtn').classList.toggle('active', isGrid);
            document.getElementById('listViewBtn').classList.toggle('active', !isGrid);
        }

        // ── Navigate ───────────────────────────────────────────────────────
        function navigateToFolder(folderId) {
            currentFolderId = folderId || null;
            setLoading(true);
            fetch(`{{ route('logistics.documents.getFolder') }}?folder_id=${folderId || ''}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    renderItems(data.items);
                    renderBreadcrumb(data.breadcrumb);
                    document.getElementById('itemCount').textContent = data.items.length + ' items';
                })
                .catch(() => showToast('Failed to load folder', 'error'))
                .finally(() => setLoading(false));
        }

        // ── Render Items ───────────────────────────────────────────────────
        function renderItems(items) {
            const grid = document.getElementById('gridContainer');
            const listBody = document.getElementById('listBody');

            grid.innerHTML = '';
            listBody.innerHTML = '';

            if (!items.length) {
                grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="empty-title">This folder is empty</div>
                    <div class="empty-sub">Upload a document or create a subfolder to get started</div>
                </div>`;
                listBody.innerHTML =
                    `<tr><td colspan="5" style="text-align:center;padding:48px;color:var(--text-3);font-size:13px;">No files or folders yet</td></tr>`;
                return;
            }

            items.forEach((item, i) => {
                if (item.type === 'folder') {
                    // Grid
                    const g = document.createElement('div');
                    g.className = 'dm-item folder new-item';
                    g.dataset.id = item.id;
                    g.dataset.type = 'folder';
                    g.style.animationDelay = `${i * 0.04}s`;
                    g.onclick = () => navigateToFolder(item.id);
                    g.innerHTML = `
                    <div class="dm-item-card">
                        <i class="fa-solid fa-folder dm-item-icon folder-icon"></i>
                        <div class="dm-item-name">${escHtml(item.name)}</div>
                        <div class="dm-item-meta">${item.count || 0} items</div>
                    </div>
                    <div class="dm-item-actions">
                        <button class="action-btn action-btn-delete" onclick="deleteFolder(event,'${item.id}')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>`;
                    grid.appendChild(g);

                    // List
                    const r = document.createElement('tr');
                    r.dataset.id = item.id;
                    r.dataset.type = 'folder';
                    r.style.cursor = 'pointer';
                    r.style.animationDelay = `${i * 0.04}s`;
                    r.onclick = () => navigateToFolder(item.id);
                    r.innerHTML = `
                    <td class="td-name"><i class="fa-solid fa-folder" style="color:var(--amber);font-size:14px;"></i>${escHtml(item.name)}</td>
                    <td>Folder</td><td style="font-family:'JetBrains Mono',monospace;font-size:12px;">—</td>
                    <td>${item.modified || '—'}</td>
                    <td onclick="event.stopPropagation()">
                        <div class="td-actions">
                            <button class="btn btn-ghost" style="color:var(--red);padding:5px 8px;font-size:13px;"
                                onclick="deleteFolder(event,'${item.id}')"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>`;
                    listBody.appendChild(r);
                } else {
                    // Grid
                    const g = document.createElement('div');
                    g.className = 'dm-item document new-item';
                    g.dataset.id = item.id;
                    g.dataset.type = 'document';
                    g.style.animationDelay = `${i * 0.04}s`;
                    g.innerHTML = `
                    <div class="dm-item-card">
                        <i class="fa-solid fa-file-${item.icon || 'pdf'} dm-item-icon file-icon"></i>
                        <div class="dm-item-name">${escHtml(item.name)}</div>
                        <div class="dm-item-meta">${item.size}</div>
                    </div>
                    <div class="dm-item-actions">
                        <a href="/logistics/documents/download/${item.id}" class="action-btn action-btn-download"
                            onclick="event.stopPropagation()"><i class="fa-solid fa-download"></i></a>
                        <button class="action-btn action-btn-delete" onclick="deleteDocument(event,'${item.id}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>`;
                    grid.appendChild(g);

                    // List
                    const r = document.createElement('tr');
                    r.dataset.id = item.id;
                    r.dataset.type = 'document';
                    r.style.animationDelay = `${i * 0.04}s`;
                    r.innerHTML = `
                    <td class="td-name"><i class="fa-solid fa-file-${item.icon || 'pdf'}" style="color:var(--brand);font-size:14px;"></i>${escHtml(item.name)}</td>
                    <td>Document</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px;">${item.size}</td>
                    <td>${item.modified || '—'}</td>
                    <td>
                        <div class="td-actions">
                            <a href="/logistics/documents/download/${item.id}" class="btn btn-ghost"
                                style="color:var(--green);padding:5px 8px;font-size:13px;"><i class="fa-solid fa-download"></i></a>
                            <button class="btn btn-ghost" style="color:var(--red);padding:5px 8px;font-size:13px;"
                                onclick="deleteDocument(event,'${item.id}')"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>`;
                    listBody.appendChild(r);
                }
            });
        }

        // ── Breadcrumb ─────────────────────────────────────────────────────
        function renderBreadcrumb(crumbs) {
            const el = document.getElementById('breadcrumbPath');
            el.innerHTML = '';
            if (!crumbs || !crumbs.length) return;
            crumbs.forEach(c => {
                el.innerHTML += `
                <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right" style="font-size:9px;"></i></span>
                <span class="breadcrumb-link" onclick="navigateToFolder('${c.id}')">${escHtml(c.name)}</span>`;
            });
        }

        // ── Create Folder ──────────────────────────────────────────────────
        document.getElementById('folderForm').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = document.getElementById('createFolderBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating…';

            const fd = new FormData();
            fd.append('folder_name', document.getElementById('folderName').value);
            fd.append('parent_id', document.getElementById('parentFolderId').value);

            try {
                const res = await fetch('{{ route('logistics.documents.createFolder') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('newFolderModal');
                    navigateToFolder(currentFolderId);
                    showToast('Folder created successfully');
                }
            } catch {
                showToast('Failed to create folder', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-plus"></i> Create Folder';
            }
        });

        // ── Upload ─────────────────────────────────────────────────────────
        document.getElementById('uploadForm').addEventListener('submit', async e => {
            e.preventDefault();
            const file = document.getElementById('documentFile').files[0];
            if (!file) {
                showToast('Please select a file', 'error');
                return;
            }

            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading…';

            const progress = document.getElementById('uploadProgress');
            const fill = document.getElementById('progressFill');
            const label = document.getElementById('progressLabel');
            progress.classList.add('active');

            // Simulate progress animation
            let pct = 0;
            const ticker = setInterval(() => {
                pct = Math.min(pct + Math.random() * 15, 90);
                fill.style.width = pct + '%';
                label.textContent = `Uploading… ${Math.round(pct)}%`;
            }, 200);

            const fd = new FormData(document.getElementById('uploadForm'));
            try {
                const res = await fetch('{{ route('logistics.documents.upload') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                });
                const data = await res.json();
                clearInterval(ticker);
                fill.style.width = '100%';
                label.textContent = 'Done!';
                if (data.success) {
                    setTimeout(() => {
                        closeModal('uploadModal');
                        navigateToFolder(currentFolderId);
                        showToast(`"${file.name}" uploaded successfully`);
                    }, 400);
                }
            } catch {
                clearInterval(ticker);
                showToast('Upload failed', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload';
            }
        });

        // ── Delete ─────────────────────────────────────────────────────────
        function deleteFolder(e, id) {
            e.stopPropagation();
            pendingDelete = {
                id,
                type: 'folder'
            };
            document.getElementById('deleteModalTitle').textContent = 'Delete Folder?';
            document.getElementById('deleteModalDesc').textContent =
                'This will permanently delete the folder and all its contents.';
            openModal('deleteModal');
        }

        function deleteDocument(e, id) {
            e.stopPropagation();
            pendingDelete = {
                id,
                type: 'document'
            };
            document.getElementById('deleteModalTitle').textContent = 'Delete Document?';
            document.getElementById('deleteModalDesc').textContent = 'This action cannot be undone.';
            openModal('deleteModal');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            if (!pendingDelete) return;
            const {
                id,
                type
            } = pendingDelete;
            const url = type === 'folder' ?
                `/logistics/documents/folder/${id}` :
                `/logistics/documents/delete/${id}`;

            // Animate item out
            const el = document.querySelector(`[data-id="${id}"]`);
            if (el) el.classList.add('deleting');

            closeModal('deleteModal');

            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    setTimeout(() => navigateToFolder(currentFolderId), 320);
                    showToast(type === 'folder' ? 'Folder deleted' : 'Document deleted');
                }
            } catch {
                showToast('Failed to delete', 'error');
            }
            pendingDelete = null;
        });

        // ── Utils ──────────────────────────────────────────────────────────
        function escHtml(s) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }
    </script>
@endsection
