<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') – Employee Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-950">

    {{-- Top Bar --}}
    <header class="bg-slate-950 border-b border-slate-800 shadow-2xl sticky top-0 z-30">
        <div class="mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-[#0078C8] font-bold text-lg">Employee Portal</span>
                <span class="text-gray-500">|</span>
                <a href="{{ route('employee.payslips.index') }}"
                    class="text-sm text-gray-300 hover:text-[#0078C8] transition 
               {{ request()->routeIs('employee.payslips.index') ? 'text-[#0078C8] font-semibold' : '' }}">
                    My Payslips
                </a>
            </div>

            @php $emp = auth('employee')->user(); @endphp
            @if ($emp)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-300">{{ $emp->full_name }}</span>
                    <form method="POST" action="{{ route('employee.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-red-400 transition">
                            Sign Out
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <main class="mx-auto px-4 py-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
