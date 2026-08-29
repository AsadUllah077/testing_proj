<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Product Details - {{ $product->unique_id }} | SSC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0078C8', // Primary
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    },
                    boxShadow: {
                        'premium': '0 20px 50px -12px rgba(0, 120, 200, 0.15)',
                        'inner-glass': 'inset 0 0 0 1px rgba(255, 255, 255, 0.2)',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .glass-header { background: linear-gradient(135deg, rgba(0, 120, 200, 0.95) 0%, rgba(0, 90, 156, 1) 100%); }
        .status-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .vertical-text { writing-mode: vertical-rl; }
    </style>
</head>

<body class="bg-[#fcfdfe] min-h-screen text-slate-900 selection:bg-brand-200/50">
    <div class="relative min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 lg:p-12 overflow-hidden">
        <!-- Abstract Background Orbs -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-float"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-float" style="animation-delay: 2s"></div>

        <div class="relative w-full max-w-2xl">
            <!-- Main Content Card -->
            <div class="bg-white rounded-[2.5rem] shadow-premium overflow-hidden border border-slate-100 transition-all duration-500 hover:shadow-2xl">

                <!-- Dynamic Header Section -->
                <div class="glass-header p-6 sm:p-10 text-white relative">
                    <!-- Subtle Patterns -->
                    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#fff 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>

                    <div class="relative z-10 flex flex-col gap-8">
                        <!-- Branding & Badge -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                            <div class="flex items-center gap-3 bg-white/10 backdrop-blur-xl px-4 py-2 rounded-2xl border border-white/20 shadow-inner-glass group hover:bg-white/20 transition-all cursor-default">
                                <img src="{{ asset('assets/images/updated-logo.webp') }}" alt="SSC Logo" class="h-6 w-auto brightness-0 invert transition-transform group-hover:scale-110">
                                <span class="text-[10px] sm:text-xs font-black uppercase tracking-[0.2em] leading-none mt-0.5">
                                    Safe Solutions Consultants
                                </span>
                            </div>

                            <div class="flex flex-col items-start sm:items-end group">
                                <span class="text-[9px] font-bold text-white/60 uppercase tracking-widest mb-1">Authenticated ID</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-base sm:text-xl font-bold tracking-tight bg-white/10 px-3 py-1 rounded-lg border border-white/10 group-hover:bg-white/20 transition-colors">
                                        {{ $product->unique_id }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Title -->
                        <div>
                            <p class="text-xs font-bold text-white/70 uppercase tracking-[0.3em] mb-3">Inventory Record</p>
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.1] text-balance">
                                {{ $product->title ?: 'Unspecified Asset' }}
                            </h1>
                        </div>
                    </div>
                </div>

                <!-- Body Section -->
                <div class="p-6 sm:p-10 space-y-10">

                    <!-- Quick Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="group bg-slate-50/80 rounded-3xl p-5 border border-slate-100/50 hover:bg-white hover:shadow-xl hover:shadow-brand-500/5 transition-all duration-300">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Status</span>
                                <div class="w-8 h-8 rounded-2xl bg-white shadow-sm flex items-center justify-center">
                                    <div class="w-3 h-3 rounded-full {{ $product->status === 'Active' ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse' }}"></div>
                                </div>
                            </div>
                            <div class="text-lg font-bold text-slate-800">{{ $product->status }}</div>
                        </div>

                        <div class="group bg-slate-50/80 rounded-3xl p-5 border border-slate-100/50 hover:bg-white hover:shadow-xl hover:shadow-brand-500/5 transition-all duration-300">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Classification</span>
                                <div class="w-8 h-8 rounded-2xl bg-white shadow-sm flex items-center justify-center text-brand-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                </div>
                            </div>
                            <div class="text-lg font-bold text-slate-800">{{ $product->category->name ?? 'Uncategorized' }}</div>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-px bg-slate-100 flex-1"></div>
                            <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.4em]">Asset Overview</h3>
                            <div class="h-px bg-slate-100 flex-1"></div>
                        </div>
                        <div class="bg-slate-50/50 rounded-[2rem] p-6 lg:p-8 border border-slate-100 text-center sm:text-left">
                            <p class="text-slate-600 leading-relaxed text-sm sm:text-base font-medium italic">
                                "{{ $product->product_details ?: 'No additional descriptive data is recorded for this specific asset identifier.' }}"
                            </p>
                        </div>
                    </div>

                    <!-- Enhanced Specifications Grid -->
                    <div class="space-y-6">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                            Technical Specifications
                        </h3>

                        @if ($product->specifications && count($product->specifications) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($product->specifications as $spec)
                                    @php
                                        $specKey = is_array($spec) ? $spec['key'] ?? '' : $spec;
                                        $specValue = is_array($spec) ? $spec['value'] ?? '' : '';
                                    @endphp
                                    @if ($specKey)
                                        <div class="group flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm hover:border-brand-200 hover:shadow-md transition-all duration-300">
                                            <span class="text-xs text-slate-500 font-semibold capitalize">{{ str_replace('_', ' ', $specKey) }}</span>
                                            <span class="text-xs font-black text-slate-900 group-hover:text-brand-700 transition-colors">{{ $specValue }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center p-8 bg-slate-50/50 rounded-[2rem] border border-dashed border-slate-200">
                                <svg class="w-10 h-10 text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                <p class="text-xs text-slate-400 font-medium italic">Standardized specifications pending configuration.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Modern Fingerprint / ID Bar -->
                    <div class="pt-10 flex flex-col md:flex-row items-center justify-between gap-8 border-t border-slate-100">
                        <div class="flex items-center gap-5 w-full md:w-auto">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 bg-brand-700 rounded-3xl flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-xl shadow-brand-700/20 rotate-3 hover:rotate-0 transition-transform">
                                {{ substr($product->unique_id, 0, 1) }}
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Identity Token</span>
                                <p class="font-mono text-sm sm:text-base font-bold text-slate-900 leading-none">#{{ $product->unique_id }}</p>
                            </div>
                        </div>

                        <div class="group relative bg-slate-950 px-6 py-4 rounded-[1.5rem] shadow-2xl flex items-center gap-6 w-full md:w-auto justify-center cursor-default overflow-hidden">
                            <div class="absolute inset-0 bg-brand-600/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="bg-white px-3 py-1.5 rounded-lg shadow-sm">
                                <span class="font-mono text-xs font-bold tracking-widest text-slate-950">{{ $product->unique_id }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.5em] md:vertical-text md:rotate-180 transition-all group-hover:text-white">
                                SSC VALIDATOR
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Site Link -->
                <div class="mt-6 text-center">
                    <a href="{{ env('VISIT_SITE_URL', '#') }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-brand-700 text-white text-sm font-semibold rounded-2xl hover:bg-brand-800 transition-all shadow-lg shadow-brand-700/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Visit Site
                    </a>
                </div>

            <!-- Enhanced Footer Branding -->
            <div class="mt-12 text-center group cursor-default">
                <div class="inline-flex items-center gap-2 mb-2">
                    <div class="w-1 h-1 rounded-full bg-brand-600"></div>
                    <div class="w-12 h-[1px] bg-slate-200"></div>
                    <div class="w-1 h-1 rounded-full bg-brand-600"></div>
                </div>
                <p class="text-[11px] text-slate-400 font-medium tracking-wide uppercase">
                    Security verified by
                    <span class="text-brand-700 font-black group-hover:tracking-[0.1em] transition-all duration-300">
                        Safe Solutions Consultants
                    </span>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
