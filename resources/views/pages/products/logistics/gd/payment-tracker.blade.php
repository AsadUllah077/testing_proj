@extends('layouts.auth')

@section('title', 'Duty & Payment Tracker')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                <div>
                    <h1 class="text-gray-900 text-xl font-normal">Duty & Payment Tracker</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Track customs duties, LC fees, and freight invoices due</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <x-common.export-dropdown name="payment-tracker" />
                    <div></div>
                    <x-common.back-button url="{{ route('logistics.gd.index') }}" />
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                    <p class="text-2xl font-black text-red-500">PKR 2.4M</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Overdue</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                    <p class="text-2xl font-black text-amber-500">PKR 1.1M</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Due This Week</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                    <p class="text-2xl font-black text-blue-500">PKR 920K</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Scheduled</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
                    <p class="text-2xl font-black text-green-600">PKR 1.2M</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-1">Paid This Month</p>
                </div>
            </div>

            {{-- Filters + Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                    <x-common.filter-form action="{{ route('logistics.gd.payment-tracker') }}">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                            <select name="status"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Status</option>
                                <option value="overdue"   {{ request('status') == 'overdue'   ? 'selected' : '' }}>Overdue</option>
                                <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="paid"      {{ request('status') == 'paid'      ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Type</label>
                            <select name="type"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">All Types</option>
                                <option value="customs_duty" {{ request('type') == 'customs_duty' ? 'selected' : '' }}>Customs Duty</option>
                                <option value="lc_fee"       {{ request('type') == 'lc_fee'       ? 'selected' : '' }}>LC Opening Fee</option>
                                <option value="freight"      {{ request('type') == 'freight'      ? 'selected' : '' }}>Freight Invoice</option>
                            </select>
                        </div>
                        <x-common.date-filter name="due_from" id="due_from" value="{{ request('due_from') }}" label="Due From" />
                        <x-common.date-filter name="due_to"   id="due_to"   value="{{ request('due_to') }}"   label="Due To" />
                        <x-common.search-filter name="q" value="{{ request('q') }}" label="Search" id="search" />
                        <x-filter-button />
                    </x-common.filter-form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-200">
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Reference</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Type</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Party</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Amount (PKR)</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Due Date</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $rows = [
                                    [
                                        'ref'    => 'GD-2026-4419',
                                        'type'   => 'Customs Duty + ST',
                                        'party'  => 'FBR / Customs',
                                        'amount' => 'PKR 2,400,000',
                                        'due'    => 'Apr 12, 2026',
                                        'status' => 'Overdue',
                                        'action' => 'Pay Now',
                                    ],
                                    [
                                        'ref'    => 'LC-2026-0041 · HBL',
                                        'type'   => 'LC Opening Fee',
                                        'party'  => 'HBL Bank',
                                        'amount' => 'PKR 185,000',
                                        'due'    => 'Apr 15',
                                        'status' => 'Pending',
                                        'action' => 'Pay',
                                    ],
                                    [
                                        'ref'    => 'SH-398 · Maersk',
                                        'type'   => 'Freight Invoice',
                                        'party'  => 'Maersk Pakistan',
                                        'amount' => 'PKR 920,000',
                                        'due'    => 'Apr 20',
                                        'status' => 'Scheduled',
                                        'action' => 'View',
                                    ],
                                    [
                                        'ref'    => 'GD-2026-4417',
                                        'type'   => 'Customs Duty + ST',
                                        'party'  => 'FBR / Customs',
                                        'amount' => 'PKR 1,200,000',
                                        'due'    => 'Apr 07',
                                        'status' => 'Paid',
                                        'action' => 'Receipt',
                                    ],
                                    [
                                        'ref'    => 'LC-2026-0039 · MCB',
                                        'type'   => 'LC Amendment Fee',
                                        'party'  => 'MCB Bank',
                                        'amount' => 'PKR 45,000',
                                        'due'    => 'Apr 28',
                                        'status' => 'Pending',
                                        'action' => 'Pay',
                                    ],
                                    [
                                        'ref'    => 'SH-395 · DHL',
                                        'type'   => 'Freight Invoice',
                                        'party'  => 'DHL Worldwide',
                                        'amount' => 'PKR 310,000',
                                        'due'    => 'Apr 22',
                                        'status' => 'Scheduled',
                                        'action' => 'View',
                                    ],
                                ];

                                $statusConfig = [
                                    'Overdue'   => 'bg-red-50 text-red-600 border-red-100',
                                    'Pending'   => 'bg-amber-50 text-amber-600 border-amber-100',
                                    'Scheduled' => 'bg-blue-50 text-blue-600 border-blue-100',
                                    'Paid'      => 'bg-green-50 text-green-600 border-green-100',
                                ];

                                $typeColor = [
                                    'Customs Duty + ST'  => 'text-amber-600',
                                    'LC Opening Fee'     => 'text-[#0078C8]',
                                    'LC Amendment Fee'   => 'text-[#0078C8]',
                                    'Freight Invoice'    => 'text-amber-500',
                                ];

                                $actionConfig = [
                                    'Pay Now' => 'bg-red-500 hover:bg-red-600 text-white border-red-500',
                                    'Pay'     => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200',
                                    'View'    => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200',
                                    'Receipt' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200',
                                ];
                            @endphp

                            @foreach ($rows as $row)
                                @php
                                    $sc  = $statusConfig[$row['status']] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                                    $tc  = $typeColor[$row['type']] ?? 'text-gray-700';
                                    $ac  = $actionConfig[$row['action']] ?? 'bg-white text-gray-700 border-gray-200';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">{{ $row['ref'] }}</td>
                                    <td class="px-5 py-4 text-sm {{ $tc }}">{{ $row['type'] }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $row['party'] }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-gray-800">{{ $row['amount'] }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $row['due'] }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <button class="px-3.5 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $ac }}">
                                            {{ $row['action'] }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500">Showing <span class="font-semibold text-gray-700">6</span> records</p>
                </div>

            </div>
        </div>
    </div>
@endsection
