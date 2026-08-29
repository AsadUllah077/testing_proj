@if ($liabilities->isEmpty())
    <div class="mt-8">
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-12 text-center">
                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-receipt text-3xl text-gray-400"></i>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No liabilities found</h3>
                <p class="mt-2 text-sm text-gray-500">There are no outstanding payroll liabilities matching your
                    criteria.</p>
            </div>
        </div>
    </div>
@else
    <!-- Summary Cards -->
    <div id="liabilities-summary" class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                        <i class="fas fa-rupee-sign text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Amount</dt>
                    <dd data-summary-key="total_amount" x-data="countUp('{{ $totalAmount }}')"
                        x-on:update-value="update($event.detail.value)"
                        class="text-2xl font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                        {{ number_format($totalAmount, 2) }}
                    </dd>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                        <i class="fas fa-list text-green-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                    <dd data-summary-key="total_items" x-data="countUp('{{ $totalItems }}')"
                        x-on:update-value="update($event.detail.value)"
                        class="text-2xl font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                        {{ $totalItems }}
                    </dd>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                        <i class="fas fa-tags text-purple-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Pay Items</dt>
                    <dd data-summary-key="total_pay_items" x-data="countUp('{{ $totalPayItems }}')"
                        x-on:update-value="update($event.detail.value)"
                        class="text-2xl font-semibold text-gray-900 tabular-nums" x-text="displayValue">
                        {{ $totalPayItems }}
                    </dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Liabilities Table -->
    <div class="mt-8">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Outstanding Liabilities</h3>
                <p class="mt-1 text-sm text-gray-600">Detailed breakdown of unpaid payroll deductions</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="employee_id" label="Employee" />
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="pay_item_id" label="Pay Item" />
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="amount" label="Amount" />
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="notes" label="Pay Run" />
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="period_end" label="Pay Period" />
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <x-sortable column="statud" label="Payment Status" />
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($liabilities as $liability)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $liability->payslip->employee->full_name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $liability->payItem->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ number_format($liability->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $liability->payslip?->payRun?->notes ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $liability->payslip?->payRun?->period_start ? $liability->payslip?->payRun?->period_start->format('M j, Y') : '—' }}
                                    -
                                    {{ $liability->payslip?->payRun?->period_end ? $liability->payslip?->payRun?->period_end->format('M j, Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($liability->is_paid)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Paid
                                        </span>
                                    @elseif((float) $liability->paid_amount > 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            Partial ({{ number_format($liability->outstanding_amount, 2) }} due)
                                        </span>
                                    @elseif($liability->payslip?->payRun?->status === 'draft')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            Draft
                                        </span>
                                    @elseif($liability->payslip?->payRun?->status === 'cancelled')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Cancelled
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <x-data-table-paginatior :paginator="$liabilities" />
        </div>
    </div>
    </div>
@endif
