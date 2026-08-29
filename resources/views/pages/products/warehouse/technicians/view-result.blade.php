{{-- resources/views/pages/products/warehouse/technicians/view-result.blade.php --}}
@extends('layouts.auth')

@section('title', 'Task Results - ' . $assignment->product->unique_id)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Main Content Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <!-- Header with Back Button -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Task Results</h2>
                                <p class="text-sm text-gray-500 mt-1">{{ $assignment->task_type }} -
                                    {{ $assignment->product->unique_id }}</p>
                            </div>
                            <a href="{{ route('warehouse.technicians.index') }}"
                                class="px-5 py-2 bg-[#0078C8] text-white rounded-md text-xs font-medium hover:bg-[#0063A6] transition-colors shadow-sm w-full sm:w-auto text-center">
                                ← BACK
                            </a>
                        </div>

                        <hr class="border-gray-200 mb-6">

                        <!-- Product Info Summary -->
                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="text-xs text-blue-600 font-semibold uppercase">Product</span>
                                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $assignment->product->title }}</p>
                                    <p class="text-xs text-gray-600">{{ $assignment->product->unique_id }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-blue-600 font-semibold uppercase">Category</span>
                                    <p class="text-sm font-medium text-gray-900 mt-1">
                                        {{ $assignment->product->category->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-600">Location: {{ $assignment->product->location ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-blue-600 font-semibold uppercase">Technician</span>
                                    <p class="text-sm font-medium text-gray-900 mt-1">
                                        {{ $assignment->technician->username }}</p>
                                    <p class="text-xs text-gray-600">{{ $assignment->technician->email }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-blue-600 font-semibold uppercase">Status</span>
                                    <p class="text-sm font-medium mt-1">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                            COMPLETED
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-600">{{ $result->submitted_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        @php
                            $cosmeticGradeNames = [
                                'C0' => 'Not Categorized', 'C1' => 'Damaged', 'C2' => 'Used Poor',
                                'C3' => 'Used Fair', 'C4' => 'Used Good', 'C5' => 'Used Very Good',
                                'C6' => 'Used Excellent', 'C7' => 'Certified Pre-Owned',
                                'C8' => 'Unused', 'C9' => 'New Open Box',
                            ];
                            $functionalGradeNames = [
                                'F1' => 'Collectible / Specialty', 'F2' => 'Verified Specialty',
                                'F3' => 'Key Functions Working', 'F4' => 'Hardware Functional',
                                'F5' => 'Refurbished', 'F6' => 'Like New',
                            ];
                            $cosmeticGrade = $visualInspection->grade
                                ?? ($result instanceof \App\Models\VisualInspectionResult ? $result->grade : null)
                                ?? ($result instanceof \App\Models\GeneralTaskResult ? ($result->additional_data['cosmetic_grade'] ?? null) : null);
                            $functionalGrade = $functionalTesting->grade
                                ?? ($result instanceof \App\Models\FunctionalTestingResult ? $result->grade : null)
                                ?? ($result instanceof \App\Models\GeneralTaskResult ? ($result->additional_data['functional_grade'] ?? null) : null);
                        @endphp

                        <!-- Grade Results -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Cosmetic Grade</h3>
                                @if($cosmeticGrade)
                                    <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full">
                                        <span class="text-lg font-bold mr-2">{{ $cosmeticGrade }}</span>
                                        <span class="text-sm">
                                            {{ $cosmeticGradeNames[$cosmeticGrade] ?? $cosmeticGrade }}
                                        </span>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic">Not Set</p>
                                @endif
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Functional Grade</h3>
                                @if($functionalGrade)
                                    <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-800 rounded-full">
                                        <span class="text-lg font-bold mr-2">{{ $functionalGrade }}</span>
                                        <span class="text-sm">
                                            {{ $functionalGradeNames[$functionalGrade] ?? $functionalGrade }}
                                        </span>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic">Not Set</p>
                                @endif
                            </div>
                        </div>

                        <!-- Results based on task type -->
                        @if (strtolower(trim($assignment->task_type)) === 'visual inspection' &&
                                $result instanceof \App\Models\VisualInspectionResult)

                            <div class="space-y-6">
                                <!-- Checklist Items -->
                                @if ($result->checklist_items)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Checklist Items</h3>
                                        <div class="space-y-2">
                                            @foreach ($result->checklist_items as $key => $value)
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    <span
                                                        class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Inspection Notes -->
                                @if ($result->inspection_notes)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Inspection Notes</h3>
                                        <p class="text-sm text-gray-600">{{ $result->inspection_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @elseif(strtolower(trim($assignment->task_type)) === 'functional testing' &&
                                $result instanceof \App\Models\FunctionalTestingResult)
                            <div class="space-y-6">
                                <!-- Test Results -->
                                @if ($result->test_results)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Test Results</h3>
                                        <div class="space-y-3">
                                            @foreach ($result->test_results as $testId => $test)
                                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                                    <div>
                                                        <span
                                                            class="text-sm font-medium text-gray-800">{{ $test['name'] ?? 'Test' }}</span>
                                                        @if (!empty($test['notes']))
                                                            <p class="text-xs text-gray-500">{{ $test['notes'] }}</p>
                                                        @endif
                                                    </div>
                                                    <span
                                                        class="px-2 py-1 text-xs font-medium rounded-full
                                            {{ ($test['result'] ?? '') == 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ ucfirst($test['result'] ?? 'unknown') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Overall Summary -->
                                @if ($result->overall_summary)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Overall Summary</h3>
                                        <p class="text-sm text-gray-600">{{ $result->overall_summary }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            @php
                                $gtGrade     = $result->additional_data['grade'] ?? null;
                                $gtChecklist = $result->additional_data['checklist_items'] ?? [];
                            @endphp
                            <div class="space-y-6">
                                <!-- Grade -->
                                @if($gtGrade)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Grade</h3>
                                        <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full">
                                            <span class="text-lg font-bold mr-2">{{ $gtGrade }}</span>
                                            <span class="text-sm">
                                                @switch($gtGrade)
                                                    @case('A') Excellent @break
                                                    @case('B') Good @break
                                                    @case('C') Fair @break
                                                    @case('F') Failed @break
                                                    @default N/A
                                                @endswitch
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Checklist Items -->
                                @if(!empty($gtChecklist))
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Task Checklist</h3>
                                        <div class="space-y-2">
                                            @foreach($gtChecklist as $key => $value)
                                                <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                                                    @if($value === 'pass')
                                                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-gray-300 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    @endif
                                                    <span class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Technician Notes -->
                                @if($result->technician_notes)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Technician Notes</h3>
                                        <p class="text-sm text-gray-600">{{ $result->technician_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Common Results -->
                        <div class="mt-6 space-y-4">
                            <!-- Result -->
                            {{-- <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Final Result</h3>
                                <div
                                    class="inline-flex items-center px-4 py-2 rounded-full
                                {{ $result->result == 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    <span class="text-sm font-medium">{{ strtoupper($result->result) }}</span>
                                </div>
                            </div> --}}

                            <!-- Remarks -->
                            @if ($result->remarks)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Remarks</h3>
                                    <p class="text-sm text-gray-600">{{ $result->remarks }}</p>
                                </div>
                            @endif

                            <!-- Submission Info -->
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Submission Information</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Submitted By:</span>
                                        <span
                                            class="ml-2 font-medium text-gray-900">{{ $result->submittedBy?->username ?? 'System' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Submitted At:</span>
                                        <span
                                            class="ml-2 font-medium text-gray-900">{{ $result->submitted_at->format('M d, Y H:i:s') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" onclick="window.print()"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                PRINT
                            </button>
                            <button type="button" onclick="window.history.back()"
                                class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-md">
                                BACK TO LIST
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{{-- Toast --}}
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[300px] max-w-[380px] bg-white border border-gray-200 shadow-lg rounded-lg p-4">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>

@endsection

@push('scripts')
<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');

        const icon = type === 'success'
            ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';

        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">${icon}</div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('toast').classList.add('hidden')">✕</button>
            </div>
        `;

        setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
</script>
@endpush
