@extends('layouts.auth')
@section('title', 'Audit Action History')

@section('content')
    <div class="p-4">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-5 bg-white p-5 rounded-[5px] border border-gray-200">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Audit Action History</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Log of all manual interventions, freezes, and reversals performed by the audit team
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="date border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="date border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">

                    <select name="action"
                        class="border border-gray-300 rounded-[3px] text-sm px-3 py-2 focus:outline-none focus:border-[#0078C8]">
                        <option value="">All Actions</option>
                        <option value="freeze" {{ request('action') == 'freeze' ? 'selected' : '' }}>Freeze</option>
                        <option value="reverse" {{ request('action') == 'reverse' ? 'selected' : '' }}>Reverse</option>
                        <option value="monitor" {{ request('action') == 'monitor' ? 'selected' : '' }}>Monitor</option>
                        <option value="dismiss" {{ request('action') == 'dismiss' ? 'selected' : '' }}>Dismiss</option>
                    </select>

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
                        Filter
                    </button>
                </form>

                <a href="{{ route('account.anomaly-detection.history') }}"
                    class="px-4 py-2 text-sm font-medium rounded-[3px] border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Reset
                </a>

                <a href="{{ route('account.anomaly-detection') }}"
                    class="px-4 py-2 bg-white text-[#0078C8] border border-[#0078C8] text-sm font-medium rounded-[3px] hover:bg-blue-50">
                    Back to Scan
                </a>
            </div>
        </div>

        {{-- History Table --}}
        <div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-16">S.No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-40">Date & Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-20">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Transaction</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-32">Performed By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notes / Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($actions as $action)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $actions->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $action->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($action->action) {
                                        'freeze' => 'bg-blue-100 text-blue-700',
                                        'reverse' => 'bg-red-100 text-red-700',
                                        'monitor' => 'bg-gray-100 text-gray-700',
                                        'dismiss' => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 {{ $badge }} rounded text-[10px] font-bold uppercase">
                                    {{ $action->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($action->transaction)
                                    <div class="text-xs font-medium text-gray-800">
                                        {{ $action->transaction->unique_no ?? 'No Ref' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $action->transaction->account->name ?? 'No Account' }} |
                                        {{ number_format($action->transaction->amount, 2) }}
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Transaction Removed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $action->user->first_name ?? 'System' }} {{ $action->user->last_name ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $action->notes ?: '-' }}
                                @if ($action->meta && isset($action->meta['unique_no']))
                                    <div class="text-[9px] text-gray-400 mt-0.5">
                                        Group Action (Ref: {{ $action->meta['unique_no'] }})
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">
                                No audit actions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($actions->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <x-data-table-paginatior :paginator="$actions" />
                </div>
            @endif
        </div>
    </div>
@endsection
