@extends('layouts.auth')

@section('title', 'Scan Station — Location A')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-yellow-50 via-amber-50 to-yellow-100 flex flex-col items-center justify-start pt-6 pb-10 px-4">

    {{-- Header Badge --}}
    <div class="w-full max-w-2xl mb-6">
        @if($locationMissing)
        <div class="mb-3 flex items-center gap-2 bg-red-100 border border-red-300 text-red-800 rounded-xl px-4 py-3 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            <span><strong>Warning:</strong> Warehouse location <em>Photography</em> was not found for your company. Please run <code class="bg-red-200 px-1 rounded">php artisan db:seed --class=WarehouseLocationSeeder</code> to create it.</span>
        </div>
        @endif
        <div class="flex items-center justify-between bg-yellow-400 rounded-2xl px-6 py-4 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-yellow-600 flex items-center justify-center shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h2.25m0 0V6.75m0-2.25h.75M3.75 4.5v.75m0 0h.75m12.75-.75h2.25m-2.25 0V6.75m0-2.25h.75M20.25 4.5v.75m0 0h-.75M3.75 19.5h2.25m0 0v-2.25m0 2.25h.75M3.75 19.5v-.75m0 0h.75m12.75.75h2.25m-2.25 0v-2.25m0 2.25h.75M20.25 19.5v-.75m0 0h-.75M9 8.25h6M9 12h6m-6 3.75h6" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-yellow-950 tracking-tight">{{ $locationName }} — Scan Station A</h1>
                    <p class="text-yellow-800 text-sm font-medium">Color assigned: <span class="font-bold">Yellow</span> · Scan the product barcode below</p>
                </div>
            </div>
            <div class="hidden sm:flex flex-col items-end">
                <span id="clock" class="text-yellow-900 text-2xl font-mono font-bold"></span>
                <span class="text-yellow-700 text-xs">{{ now()->format('D, d M Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Scan Input Card --}}
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-yellow-200 overflow-hidden mb-6">

        {{-- Top accent stripe --}}
        <div class="h-2 bg-gradient-to-r from-yellow-300 via-yellow-400 to-amber-400"></div>

        <div class="p-8">
            <label for="barcodeInput" class="block text-center text-gray-500 text-sm font-medium mb-3 uppercase tracking-widest">
                Scan or type barcode &amp; press <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded text-xs font-mono">Enter</kbd>
            </label>

            <div class="relative">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h2.25m0 0V6.75m0-2.25h.75M3.75 4.5v.75m0 0h.75m12.75-.75h2.25" />
                    </svg>
                </div>
                <input
                    id="barcodeInput"
                    type="text"
                    autocomplete="off"
                    autofocus
                    placeholder="Barcode will appear here…"
                    class="w-full pl-11 pr-4 py-4 text-xl font-mono border-2 border-yellow-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-yellow-200 focus:border-yellow-500 bg-yellow-50 text-gray-800 placeholder-gray-400 transition-all"
                />
            </div>

            {{-- Feedback --}}
            <div id="feedbackBox" class="mt-5 hidden rounded-xl px-5 py-4 flex items-start gap-3 transition-all">
                <span id="feedbackIcon" class="text-2xl mt-0.5"></span>
                <div>
                    <p id="feedbackMsg" class="font-semibold text-base"></p>
                    <p id="feedbackSub" class="text-sm mt-0.5 opacity-80"></p>
                </div>
            </div>

            {{-- Loading indicator --}}
            <div id="loadingBar" class="mt-4 hidden">
                <div class="h-1 bg-yellow-200 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-500 rounded-full animate-pulse w-3/4"></div>
                </div>
                <p class="text-center text-xs text-gray-400 mt-1">Processing scan…</p>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="w-full max-w-2xl grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-yellow-100 shadow-sm px-4 py-3 text-center">
            <p class="text-2xl font-bold text-yellow-600" id="sessionTotal">0</p>
            <p class="text-xs text-gray-500 mt-0.5">Scans this session</p>
        </div>
        <div class="bg-white rounded-xl border border-yellow-100 shadow-sm px-4 py-3 text-center">
            <p class="text-2xl font-bold text-green-600" id="sessionSuccess">0</p>
            <p class="text-xs text-gray-500 mt-0.5">Products matched</p>
        </div>
        <div class="bg-white rounded-xl border border-yellow-100 shadow-sm px-4 py-3 text-center">
            <p class="text-2xl font-bold text-orange-500" id="sessionWarning">0</p>
            <p class="text-xs text-gray-500 mt-0.5">Not matched</p>
        </div>
    </div>

    {{-- Recent Scans List --}}
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow border border-yellow-100 overflow-hidden">
        <div class="px-5 py-3 bg-yellow-50 border-b border-yellow-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Recent Scans <span class="text-yellow-600">(this session)</span></h2>
            <a href="{{ route('warehouse.scan.logs') }}" class="text-xs text-yellow-700 hover:underline font-medium">View All Logs →</a>
        </div>
        <div id="recentScans" class="divide-y divide-gray-50">
            <div class="px-5 py-6 text-center text-gray-400 text-sm" id="emptyState">
                No scans yet — start scanning to see results here.
            </div>
        </div>
    </div>

    {{-- Nav back --}}
    <div class="mt-6">
        <a href="{{ route('warehouse.scan.location-b') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 text-white text-sm font-semibold rounded-xl hover:bg-green-600 transition shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
            Switch to Location B (Green)
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Clock ──────────────────────────────────────────────
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent =
            now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ── Stats ──────────────────────────────────────────────
    let sessionTotal = 0, sessionSuccess = 0, sessionWarning = 0;

    function updateStats(type) {
        sessionTotal++;
        if (type === 'success') sessionSuccess++;
        else sessionWarning++;
        document.getElementById('sessionTotal').textContent   = sessionTotal;
        document.getElementById('sessionSuccess').textContent = sessionSuccess;
        document.getElementById('sessionWarning').textContent = sessionWarning;
    }

    // ── Feedback ───────────────────────────────────────────
    function showFeedback(type, msg, sub) {
        const box  = document.getElementById('feedbackBox');
        const icon = document.getElementById('feedbackIcon');
        const msgEl = document.getElementById('feedbackMsg');
        const subEl = document.getElementById('feedbackSub');

        box.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-orange-50', 'text-orange-800', 'bg-red-50', 'text-red-800');
        if (type === 'success') {
            box.classList.add('bg-green-50', 'text-green-800');
            icon.textContent = '✅';
        } else if (type === 'warning') {
            box.classList.add('bg-orange-50', 'text-orange-800');
            icon.textContent = '⚠️';
        } else {
            box.classList.add('bg-red-50', 'text-red-800');
            icon.textContent = '❌';
        }

        msgEl.textContent = msg;
        subEl.textContent = sub || '';
    }

    // ── Add to recent list ─────────────────────────────────
    function addRecentScan(data) {
        const container = document.getElementById('recentScans');
        const empty     = document.getElementById('emptyState');
        if (empty) empty.remove();

        const colorClass  = data.type === 'success' ? 'bg-green-50 border-l-4 border-green-400' : 'bg-orange-50 border-l-4 border-orange-300';
        const productHtml = data.product
            ? `<a href="${data.product.url}" target="_blank" class="text-blue-600 hover:underline font-medium">${data.product.title}</a> <span class="text-gray-400">(${data.product.unique_id})</span>`
            : `<span class="text-gray-400 italic">No product match</span>`;

        const row = document.createElement('div');
        row.className = `px-5 py-3 flex items-center justify-between gap-4 ${colorClass}`;
        row.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold text-gray-700">${data.barcode}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-200 text-yellow-900">{{ $locationName }} · Yellow</span>
                </div>
                <div class="text-xs mt-0.5">${productHtml}</div>
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap">${data.scanned_at}</span>
        `;

        container.insertBefore(row, container.firstChild);

        // Keep max 10 rows
        const rows = container.querySelectorAll('div[class*="px-5 py-3"]');
        if (rows.length > 10) rows[rows.length - 1].remove();
    }

    // ── Main scan handler ──────────────────────────────────
    const input = document.getElementById('barcodeInput');

    input.addEventListener('keydown', async function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        const barcode = this.value.trim();
        if (!barcode) return;

        // Show loading
        document.getElementById('loadingBar').classList.remove('hidden');
        document.getElementById('feedbackBox').classList.add('hidden');
        this.disabled = true;

        try {
            const response = await fetch('{{ route("warehouse.scan.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ barcode, location: 'A' }),
            });

            const data = await response.json();

            showFeedback(data.type, data.message, data.product ? `Product: ${data.product.unique_id}` : 'Saved to scan log.');
            addRecentScan(data);
            updateStats(data.type);

            // Play beep
            playBeep(data.type === 'success');

        } catch (err) {
            showFeedback('error', 'Network error. Please try again.', err.message);
        } finally {
            document.getElementById('loadingBar').classList.add('hidden');
            this.disabled = false;
            this.value = '';
            this.focus();
        }
    });

    // Keep focus on input always
    document.addEventListener('click', () => input.focus());
    document.addEventListener('keydown', (e) => {
        if (document.activeElement !== input && !e.ctrlKey && !e.metaKey) {
            input.focus();
        }
    });

    // ── Audio beep ─────────────────────────────────────────
    function playBeep(success = true) {
        try {
            const ctx  = new (window.AudioContext || window.webkitAudioContext)();
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = success ? 880 : 440;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch (_) {}
    }
</script>
@endpush
