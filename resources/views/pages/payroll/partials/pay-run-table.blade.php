<div class="bg-white rounded-lg border border-black/10 overflow-hidden shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-4 font-semibold text-gray-600 uppercase tracking-wider"><x-sortable column="unique_no"
                        label="Reference" /></th>
                <th class="px-6 py-4 font-semibold text-gray-600 uppercase tracking-wider"><x-sortable
                        column="period_start" label="Period" /></th>
                <th class="px-6 py-4 font-semibold text-gray-600 uppercase tracking-wider"><x-sortable
                        column="payment_date" label="Payment Date" /></th>
                <th class="px-6 py-4 font-semibold text-gray-600 uppercase tracking-wider text-center"><x-sortable
                        column="status" label="Status" /></th>
                <th class="px-6 py-4 font-semibold text-gray-600 uppercase tracking-wider text-right">Actions
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($payRuns as $run)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        {{ $run->unique_no ?? 'Draft' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ $run->period_start->format('M d, Y') }} — {{ $run->period_end->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ $run->payment_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-left">
                        @php
                            $statusClasses = [
                                'draft' => 'bg-gray-100 text-gray-600',
                                'posted' => 'bg-blue-100 text-blue-700',
                                'paid' => 'bg-green-100 text-green-700',
                            ];
                        @endphp
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $statusClasses[$run->status] ?? 'bg-gray-100' }}">
                            {{ $run->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right flex justify-end items-center space-x-3">
                        <a href="{{ route('payroll.pay-runs.show', $run->id) }}"
                            class="text-[#0078C8] hover:underline font-medium">View Details</a>
                       @if ($run->status === 'posted' || $run->status === 'paid')
                            <a href="{{ route('payroll.pay-runs.view-transaction', $run->unique_no) }}"
                            class="text-[#0078C8] hover:underline font-medium">View Transactions</a>
                       @endif
                        <button onclick="confirmDelete({{ $run->id }})"
                            class="text-red-600 hover:text-red-800 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">No pay runs found.
                        Start by creating a new one.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{-- <!-- Pagination Links -->
    <div id="pagination-wrapper"
        class="flex justify-between items-center pt-4 border-t border-gray-200 mb-3 mt-3 py-3 px-3">
        <div class="text-sm text-gray-600">
            Showing {{ $payRuns->firstItem() }} to {{ $payRuns->lastItem() }}
            of {{ $payRuns->total() }} pay runs
        </div>
        <div class="flex items-center space-x-2">
            {{ $payRuns->appends(request()->except('page'))->links() }}
        </div>
    </div> --}}
    <x-data-table-paginatior :paginator="$payRuns" />
</div>
