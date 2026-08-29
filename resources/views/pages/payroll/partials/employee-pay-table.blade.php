<div class="bg-white rounded-md border border-black/10 mt-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all-employees"
                            class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                    </th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable
                            column="full_name" label="Employee" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="email"
                            label="Employee Email" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider"><x-sortable column="salary"
                            label="Basic Salary" /></th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Monthly Tax</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Pay Items</th>
                    <th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider text-right">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($employees as $emp)
                    @php
                        $hasTemplate = $emp->payStructures && $emp->payStructures->count() > 0;
                        $selectedPayslip =
                            isset($selectedPayRun) && $selectedPayRun && $emp->relationLoaded('payslips')
                                ? $emp->payslips->first()
                                : null;
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-4">
                            <input type="checkbox" name="employee_checkbox" value="{{ $emp->id }}"
                                data-salary="{{ $emp->salary ?? 0 }}" data-name="{{ $emp->full_name }}"
                                data-payslip-id="{{ $selectedPayslip?->id }}"
                                class="employee-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8] cursor-pointer">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">
                            <a href="{{ route('hrm.employee.show', $emp->id) }}" target="_blank"
                                class="font-semibold hover:text-[#0078C8] hover:underline">
                                {{ $emp->full_name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $emp->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($emp->salary)
                                <span class="font-semibold text-gray-800">{{ currency()?->symbol }}
                                    {{ number_format($emp->salary, 2) }}</span>
                            @else
                                <span class="text-gray-400 italic text-xs">Not set</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $taxAmount = 0;
                                if ($emp->payStructures) {
                                    $taxStructure = $emp->payStructures->first(function ($s) {
                                        return $s->payItem && stripos($s->payItem->name, 'income tax') !== false;
                                    });
                                    if ($taxStructure) {
                                        $taxAmount = $taxStructure->amount;
                                    }
                                }
                            @endphp
                            @if ($taxAmount > 0)
                                <span class="font-semibold text-red-600">{{ currency()?->symbol }}
                                    {{ number_format($taxAmount, 2) }}</span>
                            @else
                                <span class="text-gray-400 italic text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($hasTemplate)
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-green-100 text-green-700">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $emp->payStructures->count() }} Items
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-500">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Not Configured
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('payroll.employee-pay.edit', $emp->id) }}"
                                class="text-[#0078C8] hover:underline">Setup Pay Template</a> | 
                            @if ($selectedPayRun && $selectedPayslip && in_array($selectedPayslip->payRun?->status, ['posted', 'paid'], true))
                                <a href="{{ route('payroll.payslips.pdf', [$selectedPayRun->id, $selectedPayslip->id]) }}"
                                    target="_blank"
                                    class="text-[#0078C8] hover:underline">
                                    <span>PDF Download</span>
                                </a>
                            @elseif($selectedPayRun)
                                <span class="text-gray-400 italic text-xs">No payslip</span>
                            @else
                                <span class="text-gray-400 italic text-xs">Select pay run</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-data-table-paginatior :paginator="$employees" />
    </div>
</div>
