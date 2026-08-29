<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/flatpickr.min.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary-color: #0078C8;
            --primary-dark: #006bb0;
            --primary-light: #e3f2fd;
            --primary-lighter: #e8f4fb;
            --primary-rgb: 0, 120, 200;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f4fb 100%);
        }

        *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        *::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        *::-webkit-scrollbar-thumb {
            background: #0078C8;
            border-radius: 4px;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: #006bb0;
        }

        /* === PRIMARY COLOR UTILITY CLASSES === */
        .btn-primary {
            background-color: #0078C8;
            color: white;
        }

        .btn-primary:hover {
            background-color: #006bb0;
        }

        .text-primary {
            color: #0078C8;
        }

        .border-primary {
            border-color: #0078C8;
        }

        .bg-primary {
            background-color: #0078C8;
        }

        .bg-primary-light {
            background-color: #e3f2fd;
            color: #0078C8;
        }

        /* === SELECT2 CUSTOM BRAND STYLES === */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0078C8 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #0078C8 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option--highlighted {
            background-color: #0078C8 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #0078C8 !important;
            box-shadow: 0 0 0 2px rgba(0, 120, 200, 0.25) !important;
        }

        .select2-container--default .select2-dropdown {
            border: 1px solid #0078C8 !important;
            box-shadow: 0px 3px 8px rgba(0, 120, 200, 0.15);
        }

        .select2-container .select2-selection--single {
            height: 40px !important;
            padding: 8px 12px !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--default .select2-selection__rendered {
            line-height: 28px !important;
            font-size: 15px;
        }

        .select2-container--default .select2-selection__arrow {
            height: 40px !important;
        }

        /* === GRADIENT HEADER STYLES === */
        .ai-gradient-header {
            background: linear-gradient(135deg, #0078C8 0%, #0066a1 100%);
        }

        /* === CARD STYLES === */
        .ai-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .ai-card:hover {
            box-shadow: 0 4px 16px rgba(0, 120, 200, 0.12);
        }

        /* === BUTTON STYLES === */
        .ai-btn-primary {
            background-color: #0078C8;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .ai-btn-primary:hover {
            background-color: #006bb0;
            box-shadow: 0 4px 12px rgba(0, 120, 200, 0.3);
        }

        .ai-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .ai-btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .ai-btn-secondary:hover {
            background-color: #e8e8e8;
            border-color: #0078C8;
            color: #0078C8;
        }

        /* === INPUT STYLES === */
        .ai-input {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .ai-input:focus {
            outline: none;
            border-color: #0078C8;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(0, 120, 200, 0.1);
        }

        /* === TABLE STYLES === */
        .ai-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ai-table thead {
            background-color: #f5f5f5;
            border-bottom: 2px solid #0078C8;
        }

        .ai-table thead th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ai-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s ease;
        }

        .ai-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .ai-table tbody td {
            padding: 12px;
            font-size: 14px;
            color: #555;
        }

        /* === BADGE STYLES === */
        .ai-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .ai-badge-primary {
            background-color: #e3f2fd;
            color: #0078C8;
        }

        .ai-badge-success {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .ai-badge-warning {
            background-color: #ffe0b2;
            color: #f57c00;
        }

        .ai-badge-danger {
            background-color: #ffcdd2;
            color: #c62828;
        }

        /* === LOADING SPINNER === */
        .ai-spinner {
            border: 3px solid #f0f0f0;
            border-top: 3px solid #0078C8;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="flex min-h-screen">

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <x-header />
            <!-- AI Content -->
            <main class="flex-1 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
