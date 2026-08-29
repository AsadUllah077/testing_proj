@extends('layouts.auth')
@section('title', 'Anomaly Detection')

@section('content')
    <div class="p-4">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5 rounded-[5px] border border-gray-200">

            <!-- Left side -->
            <div>
                <h1 class="text-lg font-bold text-gray-800">Transaction Anomaly Detection</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Rule-based scan for unusual transaction patterns, control gaps, and potential fraud signals
                </p>
            </div>

            <!-- Right side -->
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="severity" value="{{ request('severity') }}">
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="date border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="date border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">

                    <select name="per_page"
                        class="select2 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                        @foreach (config('default.per_page') as $value)
                            <option value="{{ $value }}" {{ request('per_page') == $value ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-[3px] hover:bg-[#006bb0]">
                        Scan
                    </button>
                </form>

                <a href="{{ route('account.anomaly-detection.history') }}"
                    class="px-4 py-2 text-sm font-medium rounded-[3px] border border-blue-300 text-blue-700 hover:bg-blue-50">
                    View History
                </a>

                <a href="{{ route('account.anomaly-detection') }}"
                    class="px-4 py-2 text-sm font-medium rounded-[3px] border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Reset
                </a>

                <a href="javascript:void(0)" onclick="window.history.back()"
                    class="px-4 py-2 text-sm font-medium rounded-[3px] border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Back
                </a>
            </div>

        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <a href="{{ route('account.anomaly-detection') }}"
                class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Total Anomalies</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $summary['total'] }}</p>
            </a>
            <a href="{{ route('account.anomaly-detection', ['severity' => 'critical']) }}"
                class="bg-white border border-red-200 rounded-[5px] p-4 {{ $summary['critical'] > 0 ? 'bg-red-50' : '' }}">
                <p class="text-xs text-red-600 uppercase font-semibold tracking-wide">Critical</p>
                <p class="text-2xl font-bold text-red-700 mt-1">{{ $summary['critical'] }}</p>
                <p class="text-xs text-red-400 mt-0.5">Requires immediate review</p>
            </a>
            <a href="{{ route('account.anomaly-detection', ['severity' => 'warning']) }}"
                class="bg-white border border-yellow-200 rounded-[5px] p-4 {{ $summary['warning'] > 0 ? 'bg-yellow-50' : '' }}">
                <p class="text-xs text-yellow-700 uppercase font-semibold tracking-wide">Warnings</p>
                <p class="text-2xl font-bold text-yellow-700 mt-1">{{ $summary['warning'] }}</p>
                <p class="text-xs text-yellow-500 mt-0.5">Suspicious; needs review</p>
            </a>
            <a href="{{ route('account.anomaly-detection', ['severity' => 'info']) }}"
                class="bg-white border border-gray-200 rounded-[5px] p-4">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Info</p>
                <p class="text-2xl font-bold text-gray-600 mt-1">{{ $summary['info'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Patterns worth noting</p>
            </a>
        </div>

        {{-- Anomalies Table --}}
        @if (count($anomalies) > 0)
            <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Detected Anomalies</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-24">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-24">Severity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-36">Rule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-28">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase w-32">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-36">Account</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-48">Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($anomalies as $a)
                            @php
                                $isFrozen = ($a['audit']['action'] ?? '') === 'freeze';
                                $isReversed = ($a['audit']['action'] ?? '') === 'reverse';
                                $isActioned = $isFrozen || $isReversed;
                            @endphp
                            <tr id="txn-row-{{ $a['transaction_id'] }}"
                                class="hover:bg-gray-50 {{ $a['severity'] === 'critical' ? 'bg-red-50/40' : ($a['severity'] === 'warning' ? 'bg-yellow-50/40' : '') }} {{ $isActioned ? 'opacity-50 grayscale' : '' }}">
                                <td class="px-4 py-3">{{ $anomalies->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    @if ($a['severity'] === 'critical')
                                        <span
                                            class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-bold uppercase">Critical</span>
                                    @elseif($a['severity'] === 'warning')
                                        <span
                                            class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-bold uppercase">Warning</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-bold uppercase">Info</span>
                                    @endif

                                    @if ($a['audit'])
                                        <div class="mt-1">
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded-full uppercase font-bold">
                                                {{ $a['audit']['action'] }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium text-gray-700">{{ $a['title'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $a['description'] }}</p>
                                    @if ($a['unique_no'])
                                        <span class="text-xs text-gray-400">Ref: {{ $a['unique_no'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ $a['date'] ? \Carbon\Carbon::parse($a['date'])->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    {{ isset($a['amount']) ? number_format($a['amount'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $a['account'] ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($a['transaction_id'])
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($isActioned)
                                                <button onclick="executeAuditAction({{ $a['transaction_id'] }}, 'dismiss')"
                                                    class="p-1.5 bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200"
                                                    title="{{ $isReversed ? 'Restore Transaction Group' : 'Unfreeze Transaction Group' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            @else
                                                <button onclick="executeAuditAction({{ $a['transaction_id'] }}, 'freeze')"
                                                    class="p-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                                                    title="Freeze Transaction Group">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </button>

                                                <button onclick="executeAuditAction({{ $a['transaction_id'] }}, 'reverse')"
                                                    class="p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200"
                                                    title="Reverse Transaction Group">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>

                                                <button onclick="executeAuditAction({{ $a['transaction_id'] }}, 'monitor')"
                                                    class="p-1.5 bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                                                    title="Mark Group for Monitoring">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[10px] text-gray-400">BATCH RULE</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <x-data-table-paginatior :paginator="$anomalies" />
                </div>
            </div>
        @else
            <div class="bg-white border border-gray-200 rounded-[5px] p-12 text-center">
                <div class="bg-emerald-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-lg font-semibold text-gray-800">No Anomalies Detected</p>
                <p class="text-sm text-gray-500 mt-1">All transactions in the scanned period appear normal.</p>
            </div>
        @endif
    </div>

    <script>
        function executeAuditAction(transactionId, action) {
            const labels = {
                freeze: 'freeze this transaction group',
                reverse: 'reverse this transaction group',
                monitor: 'mark this transaction group for monitoring',
                dismiss: 'restore or dismiss this transaction group action',
            };
            const notes = prompt(`Are you sure you want to ${labels[action] || action}? Enter notes (optional):`);
            if (notes === null) return; // Cancelled

            fetch("{{ route('account.anomaly-detection.execute') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId,
                        action: action,
                        notes: notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An unexpected error occurred.");
                });
        }
    </script>
@endsection
