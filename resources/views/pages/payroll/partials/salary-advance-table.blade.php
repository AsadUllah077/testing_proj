<div class="bg-white rounded-md border border-black/10 mt-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="amount"
                            label="Amount" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="remaining_amount"
                            label="Remaining" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Recovery</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable
                            column="advance_date" label="Date" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($advances as $advance)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $advance->employee->full_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($advance->amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($advance->remaining_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>{{ $advance->recovery_method === 'percentage' ? number_format($advance->recovery_percentage, 2) . '% of gross' : number_format($advance->installment_amount, 2) . ' / run' }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $advance->recovery_frequency === 'monthly' ? 'Monthly' : 'Every pay run' }} ·
                                {{ (int) $advance->installments_recovered }}{{ $advance->installments_total ? ' / ' . $advance->installments_total : '' }} recovered
                                from {{ optional($advance->recovery_start_date)->format('Y-m-d') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $advance->advance_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($advance->payment_reference)
                                <a href="{{ route('account.bank-reconciliation.show-transaction', $advance->payment_reference) }}"
                                    target="_blank" class="text-[#0078C8] hover:underline">
                                    {{ $advance->payment_reference }}
                                </a>
                            @else
                                <span class="text-gray-400">Not issued</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $advance->reason ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                @if ($advance->status === 'pending') bg-yellow-100 text-yellow-700
                                @elseif ($advance->status === 'approved') bg-blue-100 text-blue-700
                                @elseif ($advance->status === 'paid') bg-green-100 text-green-700
                                @elseif ($advance->status === 'rejected') bg-red-100 text-red-700
                                @endif">
                                {{ ucfirst($advance->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                            @if ($advance->status === 'pending')
                                <button onclick="approveItem({{ $advance->id }})"
                                    class="text-green-600 hover:underline">Approve</button>
                                <button onclick="rejectItem({{ $advance->id }})"
                                    class="text-red-500 hover:underline">Reject</button>
                            @endif
                            @if ($advance->status === 'approved' && !$advance->payment_reference)
                                <button onclick="openIssueModal({{ $advance->id }}, @js($advance->employee->full_name ?? 'Employee'), '{{ number_format($advance->remaining_amount, 2, '.', '') }}')"
                                    class="text-green-600 hover:underline">Issue Payment</button>
                            @endif
                            @if ($advance->payment_reference && $advance->remaining_amount > 0)
                                <button onclick="togglePause({{ $advance->id }})" class="text-amber-600 hover:underline">{{ $advance->recovery_paused_at ? 'Resume' : 'Pause' }}</button>
                                <button onclick="openScheduleModal({{ json_encode($advance) }})" class="text-[#0078C8] hover:underline">Reschedule</button>
                                <button onclick="openSettlementModal({{ $advance->id }}, @js($advance->employee->full_name ?? 'Employee'), '{{ number_format($advance->remaining_amount, 2, '.', '') }}')" class="text-green-600 hover:underline">Settle</button>
                            @endif
                            @if (in_array($advance->status, ['pending', 'approved']))
                                @if (!$advance->payment_reference)
                                    <button onclick="editItem({{ json_encode($advance) }})"
                                        class="text-[#0078C8] hover:underline">Edit</button>
                                    <button onclick="deleteItem({{ $advance->id }})"
                                        class="text-red-500 hover:underline">Delete</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-data-table-paginatior :paginator="$advances" />
    </div>
</div>
