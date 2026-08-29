@extends('layouts.auth')

@section('title', 'New Pay Run')

@section('content')
    <div class="mb-8 font-sans mt-6">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">New Pay Run</h1>
                        <a href="{{ route('payroll.pay-runs.index') }}"
                            class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] hover:underline">←
                            Back to Pay Runs</a>
                    </div>
                    <form id="payRunForm">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Period Start</label>
                                <input type="date" name="period_start" required
                                    class="date w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] p-3 border">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Period End</label>
                                <input type="date" name="period_end" required
                                    class="date w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] p-3 border">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pay Frequency</label>
                                <select name="frequency" required
                                    class="select2 w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] p-3 border">
                                    <option value="monthly">Monthly</option>
                                    <option value="fortnightly">Fortnightly</option>
                                    <option value="weekly">Weekly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Date</label>
                                <input type="date" name="payment_date" required
                                    class="date w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] p-3 border">
                            </div>
                        </div>

                        <div class="mt-10 border-t pt-8">
                            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                                <h4 class="text-sm font-bold text-gray-700 uppercase">Select Employees (<span id="visibleEmployeeCount">{{ $employees->count() }}</span>/<span id="totalEmployeeCount">{{ $employees->count() }}</span>)</h4>
                                <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                    <input type="checkbox" id="toggleOvertime" class="rounded border-gray-300">
                                    Show Overtime Hours
                                </label>
                            </div>
                            <div class="mb-3">
                                <input type="search" id="employeeSearch" placeholder="Search employees by name or email..."
                                    class="w-full border-gray-300 rounded focus:ring-[#0078C8] focus:border-[#0078C8] p-3 border text-sm">
                            </div>
                            <div class="bg-gray-50 border rounded p-4 max-h-96 overflow-y-auto space-y-1">
                                @foreach ($employees as $emp)
                                    <div class="employee-row flex items-center p-2 hover:bg-white rounded transition"
                                        data-search="{{ strtolower($emp->full_name . ' ' . $emp->email) }}">
                                        <label class="flex items-center space-x-3 flex-1 cursor-pointer">
                                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" checked
                                                class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                                            <span class="text-sm text-gray-700">{{ $emp->full_name }}
                                                <span class="text-xs text-gray-400">({{ $emp->email }})</span>
                                            </span>
                                        </label>
                                        <div class="overtime-field hidden ml-4 flex items-center gap-2">
                                            <label class="text-xs text-amber-700 font-medium whitespace-nowrap">OT Hours:</label>
                                            <input type="number" name="overtime_hours[{{ $emp->id }}]"
                                                min="0" step="0.5" placeholder="0"
                                                class="w-20 border border-amber-300 rounded py-1 px-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                                        </div>
                                    </div>
                                @endforeach
                                <div id="noEmployeesFound" class="hidden py-8 text-center text-sm text-gray-500">
                                    No employees match your search.
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end">
                            <button type="submit"
                                class="px-10 py-3 bg-[#0078C8] text-white rounded font-bold shadow hover:bg-[#006bb0] transition">
                                GENERATE PAY RUN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggleOvertime').addEventListener('change', function () {
            document.querySelectorAll('.overtime-field').forEach(el => {
                el.classList.toggle('hidden', !this.checked);
            });
        });

        const employeeSearch = document.getElementById('employeeSearch');
        const employeeRows = Array.from(document.querySelectorAll('.employee-row'));
        const visibleEmployeeCount = document.getElementById('visibleEmployeeCount');
        const noEmployeesFound = document.getElementById('noEmployeesFound');

        employeeSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            employeeRows.forEach(row => {
                const isVisible = row.dataset.search.includes(query);
                row.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    visibleCount++;
                }
            });

            visibleEmployeeCount.innerText = visibleCount;
            noEmployeesFound.classList.toggle('hidden', visibleCount > 0);
        });

        document.getElementById('payRunForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = 'GENERATING...';

            const form = e.target;
            const employeeIds = Array.from(form.querySelectorAll('input[name="employee_ids[]"]:checked'))
                .map(input => input.value);
            const overtimeHours = {};

            employeeIds.forEach(employeeId => {
                const input = form.querySelector(`input[name="overtime_hours[${employeeId}]"]`);
                if (input && input.value !== '' && parseFloat(input.value) > 0) {
                    overtimeHours[employeeId] = input.value;
                }
            });

            const res = await fetch('{{ route('payroll.pay-runs.store') }}', {
                method: 'POST',
                body: JSON.stringify({
                    period_start: form.period_start.value,
                    period_end: form.period_end.value,
                    payment_date: form.payment_date.value,
                    frequency: form.frequency.value,
                    employee_ids: employeeIds,
                    overtime_hours: overtimeHours,
                }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerText = 'GENERATE PAY RUN';
            }
        }
    </script>
@endpush
