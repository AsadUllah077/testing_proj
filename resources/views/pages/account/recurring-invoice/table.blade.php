<div class="bg-white border border-gray-200 rounded-[5px] overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Title /
                    Customer</th>
                <th class="text-left py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Frequency
                </th>
                <th class="text-left py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Next Run</th>
                <th class="text-right py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                <th class="text-center py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Generated
                </th>
                <th class="text-center py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="text-right py-3 pr-4 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recurringInvoices as $ri)
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3.5">
                        <p class="text-sm font-semibold text-gray-800">{{ $ri->title }}</p>
                        <p class="text-xs text-gray-400">{{ $ri->customer->name }}</p>
                    </td>
                    <td class="py-3.5 text-sm text-gray-600">{{ $ri->frequency_label }}</td>
                    <td class="py-3.5">
                        @php $isOverdue = $ri->status === 'active' && $ri->next_run_date->isPast(); @endphp
                        <span class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ $ri->next_run_date->format('d M Y') }}
                        </span>
                        @if ($isOverdue)
                            <span class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">overdue</span>
                        @endif
                    </td>
                    <td class="py-3.5 text-sm font-semibold text-gray-800 text-right">
                        {{ numberFormat($ri->total) }}</td>
                    <td class="py-3.5 text-center text-sm text-gray-600">
                        {{ $ri->invoices_generated }}
                        @if ($ri->max_occurrences)
                            <span class="text-gray-400"> / {{ $ri->max_occurrences }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 text-center">
                        @php
                            $colors = [
                                'active' => 'emerald',
                                'paused' => 'amber',
                                'completed' => 'blue',
                                'cancelled' => 'red',
                            ];
                            $c = $colors[$ri->status] ?? 'gray';
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                bg-{{ $c }}-100 text-{{ $c }}-700">
                            {{ ucfirst($ri->status) }}
                        </span>
                    </td>
                    <td class="py-3.5 pr-4 text-right">
                        <div class="flex justify-end items-center gap-2">
                            {{-- Toggle active/pause --}}
                            @if (in_array($ri->status, ['active', 'paused']))
                                <form method="POST"
                                    action="{{ route('account.recurring-invoice.toggle-status', $ri) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="text-xs font-medium {{ $ri->status === 'active' ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                        {{ $ri->status === 'active' ? 'Pause' : 'Resume' }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('account.recurring-invoice.edit', $ri) }}"
                                class="text-xs font-medium text-[#0078C8] hover:underline">Edit</a>

                            <form method="POST" action="{{ route('account.recurring-invoice.destroy', $ri) }}"
                                onsubmit="return confirm('Delete this recurring schedule?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                        No recurring invoices found.
                        <a href="{{ route('account.recurring-invoice.create') }}"
                            class="text-[#0078C8] hover:underline ml-1">Create one →</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-data-table-paginatior :paginator="$recurringInvoices" />
</div>
