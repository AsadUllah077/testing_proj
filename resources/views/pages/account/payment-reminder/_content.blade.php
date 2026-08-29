    @php
        $activeReminderFilters = array_filter([
            'customer_id' => $filters['customer_id'] ?? request('customer_id'),
            'invoice_no' => $filters['invoice_no'] ?? request('invoice_no'),
            'from_date' => $filters['from_date'] ?? request('from_date'),
            'to_date' => $filters['to_date'] ?? request('to_date'),
            'min_amount' => $filters['min_amount'] ?? request('min_amount'),
            'max_amount' => $filters['max_amount'] ?? request('max_amount'),
        ], fn($value) => $value !== null && $value !== '');
    @endphp

    {{-- Summary & Scheduler Info --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
        <div class="bg-white border border-gray-200 rounded-[5px] p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Total Overdue</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ numberFormat($totalOverdue) }}</p>
            <p class="text-xs text-gray-400 mt-1">Across {{ $overdueCount }} invoice(s)</p>
        </div>
        <div class="md:col-span-2 bg-blue-50 border border-blue-200 rounded-[5px] p-5">
            <p class="text-xs font-semibold text-blue-600 uppercase mb-2">Automated Scheduler Configuration</p>
            <div class="grid grid-cols-3 gap-3 text-sm">
                <div>
                    <p class="text-xs text-blue-500">Reminder Thresholds</p>
                    <p class="font-semibold text-gray-800">{{ $schedulerConfig['thresholds'] }} days overdue</p>
                </div>
                <div>
                    <p class="text-xs text-blue-500">Schedule</p>
                    <p class="font-semibold text-gray-800">{{ $schedulerConfig['time'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-blue-500">Command</p>
                    <code
                        class="text-xs bg-blue-100 px-2 py-1 rounded text-blue-800">invoices:send-overdue-reminders</code>
                </div>
            </div>
        </div>
    </div>

    {{-- Buckets --}}
    @php
        $bucketConfig = [
            '1–30 days' => ['color' => 'amber', 'min' => 1, 'max' => 30],
            '31–60 days' => ['color' => 'orange', 'min' => 31, 'max' => 60],
            '61–90 days' => ['color' => 'red', 'min' => 61, 'max' => 90],
            '90+ days' => ['color' => 'rose', 'min' => 91, 'max' => 9999],
        ];
        $colorMap = [
            'amber' => [
                'bg' => 'bg-amber-50',
                'border' => 'border-amber-300',
                'badge' => 'bg-amber-100 text-amber-700',
                'btn' => 'bg-amber-500 hover:bg-amber-600',
            ],
            'orange' => [
                'bg' => 'bg-orange-50',
                'border' => 'border-orange-300',
                'badge' => 'bg-orange-100 text-orange-700',
                'btn' => 'bg-orange-500 hover:bg-orange-600',
            ],
            'red' => [
                'bg' => 'bg-red-50',
                'border' => 'border-red-300',
                'badge' => 'bg-red-100 text-red-700',
                'btn' => 'bg-red-500 hover:bg-red-600',
            ],
            'rose' => [
                'bg' => 'bg-rose-50',
                'border' => 'border-rose-400',
                'badge' => 'bg-rose-100 text-rose-800',
                'btn' => 'bg-rose-600 hover:bg-rose-700',
            ],
        ];
    @endphp

    @foreach ($buckets as $label => $bucket)
        @php
            $invoices = $bucket['paginator'];
            $pageParam = $bucket['page_param'];
            $cfg = $bucketConfig[$label];
            $cl = $colorMap[$cfg['color']];
            $total = $invoices->sum('remaining_amount');
            $totalCount = $invoices->total();
        @endphp
        <div class="mb-5 bg-white rounded-[5px] border border-gray-200 overflow-hidden">
            {{-- Bucket Header --}}
            <div class="flex justify-between items-center p-4 {{ $cl['bg'] }} border-b {{ $cl['border'] }}">
                <div class="flex items-center gap-3">
                    <span
                        class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $cl['badge'] }}">{{ $label }}</span>
                    <span class="text-sm font-semibold text-gray-700">
                        {{ $totalCount }} invoice{{ $totalCount != 1 ? 's' : '' }}
                    </span>
                    @if ($total > 0)
                        <span class="text-sm font-bold text-gray-800">— Page Total: {{ numberFormat($total) }}</span>
                    @endif
                </div>
                @if ($totalCount > 0)
                    <form method="POST" action="{{ route('account.payment-reminders.send-bucket') }}">
                        @csrf
                        <input type="hidden" name="min_days" value="{{ $cfg['min'] }}">
                        <input type="hidden" name="max_days" value="{{ $cfg['max'] }}">
                        @foreach ($activeReminderFilters as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit"
                            onclick="return confirm('Send reminders to all {{ $totalCount }} customers in this bucket?')"
                            class="text-xs text-white px-3 py-1.5 rounded font-semibold {{ $cl['btn'] }}">
                            Send All ({{ $totalCount }})
                        </button>
                    </form>
                @endif
            </div>

            @if ($invoices->isEmpty())
                <div class="px-4 py-6 text-center text-gray-400 text-sm">No overdue invoices in this range.</div>
            @else
                <div class="overflow-y-auto max-h-[80vh]">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Invoice #
                                </th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Customer
                                </th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Segment</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Invoice Date
                                </th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Due Date
                                </th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Days
                                    Overdue</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Outstanding
                                </th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('account.sale-invoice.show', $invoice->id) }}"
                                            class="text-[#0078C8] font-semibold hover:underline">
                                            {{ $invoice->invoice_no ?: $invoice->unique_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $invoice->customer->name }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $invoice->customer->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @php $seg = ($segments ?? [])[$invoice->customer_id] ?? null; @endphp
                                        @if ($seg)
                                            @php
                                                $segColors = [
                                                    'reliable'          => 'bg-green-100 text-green-700',
                                                    'occasionally_late' => 'bg-yellow-100 text-yellow-800',
                                                    'chronic'           => 'bg-red-100 text-red-700',
                                                    'new'               => 'bg-gray-100 text-gray-600',
                                                ];
                                                $segLabels = [
                                                    'reliable'          => 'Reliable',
                                                    'occasionally_late' => 'Occ. Late',
                                                    'chronic'           => 'Chronic',
                                                    'new'               => 'New',
                                                ];
                                            @endphp
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $segColors[$seg['segment']] ?? 'bg-gray-100 text-gray-600' }}"
                                                title="Avg {{ $seg['avg_days'] ?? '?' }}d to pay | {{ $seg['payment_count'] }} payments{{ $seg['escalation'] ? ' | ' . $seg['escalation'] : '' }}">
                                                {{ $segLabels[$seg['segment']] ?? $seg['segment'] }}
                                            </span>
                                            @if ($seg['escalation'])
                                                <span class="block text-[9px] text-red-500 mt-0.5 font-medium" title="{{ $seg['escalation'] }}">Escalate</span>
                                            @endif
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ dateFormat($invoice->date) }}</td>
                                    <td class="px-4 py-3 text-red-600 font-semibold">
                                        {{ $invoice->due_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $cl['badge'] }}">
                                            {{ $invoice->days_overdue }}d
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-red-600">
                                        {{ numberFormat($invoice->remaining_amount) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($invoice->customer->email)
                                            <form method="POST"
                                                action="{{ route('account.payment-reminders.send-one', $invoice) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="text-xs {{ $cl['btn'] }} text-white px-2.5 py-1 rounded font-semibold">
                                                    Send Reminder
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No email</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination for this bucket --}}
                @if ($invoices->hasPages())
                    <div
                        class="px-6 py-3 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-3">
                        <span class="text-sm text-gray-500">
                            Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of
                            {{ $invoices->total() }} invoices
                            (Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }})
                        </span>
                        <div class="flex items-center gap-1">
                            @if ($invoices->onFirstPage())
                                <span class="px-3 py-1 text-xs border border-gray-200 rounded text-gray-300">←
                                    Prev</span>
                            @else
                                <a href="{{ $invoices->previousPageUrl() }}"
                                    class="px-3 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50">←
                                    Prev</a>
                            @endif

                            @foreach ($invoices->getUrlRange(max(1, $invoices->currentPage() - 2), min($invoices->lastPage(), $invoices->currentPage() + 2)) as $page => $url)
                                @if ($page == $invoices->currentPage())
                                    <span
                                        class="px-3 py-1 text-xs border border-[#0078C8] bg-[#0078C8] rounded text-white font-semibold">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}"
                                        class="px-3 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($invoices->hasMorePages())
                                <a href="{{ $invoices->nextPageUrl() }}"
                                    class="px-3 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50">Next
                                    →</a>
                            @else
                                <span class="px-3 py-1 text-xs border border-gray-200 rounded text-gray-300">Next
                                    →</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endforeach
