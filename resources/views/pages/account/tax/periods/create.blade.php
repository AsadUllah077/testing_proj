@extends('layouts.auth')

@section('title', 'Create Tax Period')

@section('content')
    <div class="mb-8">
        <div class="mx-auto px-3">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Create Tax Period</h2>
                    <p class="text-xs text-gray-500 mt-1">Define a new period for tax accumulation.</p>
                </div>
                <a href="{{ route('account.tax-periods.index') }}"
                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-xs font-normal rounded hover:bg-[#0078C8] hover:text-white transition-all duration-200">
                    BACK TO LIST
                </a>
            </div>

            <!-- Error Messages -->
            @if (session('error'))
                <div class="max-w-2xl mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-red-500 text-sm font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Form Card -->
            <div class="max-w-2xl bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <form action="{{ route('account.tax-periods.store') }}" method="POST">
                    @csrf
                    <div class="p-8 space-y-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Period
                                Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                placeholder="e.g. June 2026 Tax" required>
                            <p class="text-[10px] text-gray-400 mt-1">A descriptive name to identify this period in reports.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Type
                                <span class="text-red-500">*</span></label>
                            <select name="type"
                                class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                            </select>
                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Start
                                    Date <span class="text-red-500">*</span></label>
                                <input type="text" name="start_date"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                    placeholder="Select start date" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">End
                                    Date <span class="text-red-500">*</span></label>
                                <input type="text" name="end_date"
                                    class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all"
                                    placeholder="Select end date" required>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                        <a href="{{ route('account.tax-periods.index') }}"
                            class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded shadow-sm hover:bg-[#006bb0] focus:ring-2 focus:ring-offset-2 focus:ring-[#0078C8] transition-all">
                            Create Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@php
    $currentPeriod = App\Models\AccountingPeriod::findPeriodForDate(now());
@endphp
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startInput = document.querySelector('[name="start_date"]');
            const endInput = document.querySelector('[name="end_date"]');

            const defaultStart = "{{ $currentPeriod?->start_date?->format('Y-m-d') }}";
            const defaultEnd = "{{ $currentPeriod?->end_date?->format('Y-m-d') }}";

            if (!startInput.value && defaultStart) {
                startInput.value = defaultStart;
                if (startInput._flatpickr) {
                    startInput._flatpickr.setDate(defaultStart);
                }
            }

            if (!endInput.value && defaultEnd) {
                endInput.value = defaultEnd;
                if (endInput._flatpickr) {
                    endInput._flatpickr.setDate(defaultEnd);
                }
            }
        });
    </script>
@endpush
