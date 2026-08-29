{{-- resources/views/pages/products/warehouse/technicians/general-task.blade.php --}}
@extends('layouts.auth')

@section('title', 'Task Submission - ' . $assignment->product->unique_id)

@section('content')
<x-acccount.loader />
@php
    $isEditing = $isEditing ?? false;
    $existingResult = $existingResult ?? null;
    $generalData = $existingResult->additional_data ?? [];
    $generalChecklistItems = old('checklist_items', $generalData['checklist_items'] ?? []);
    $generalChecklistItems = is_array($generalChecklistItems) ? $generalChecklistItems : [];
    $defaultChecklistKeys = ['task_completed', 'tools_used', 'no_damage', 'area_cleaned', 'documentation_done'];
    $customChecklistItems = array_diff_key($generalChecklistItems, array_flip($defaultChecklistKeys));
    $currentTaskGrade = old('grade', $generalData['grade'] ?? '');
@endphp
<div class="mt-4">
    <div class="mx-auto px-3">
        <div class="overflow-hidden">
            <!-- Main Content Card -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <form id="generalTaskForm" action="{{ route('warehouse.technicians.submit.general', $assignment->id) }}" method="POST">
                    @csrf

                    <div class="p-6">
                        <!-- Header with Back Button -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $isEditing ? 'Edit Task Submission' : 'Task Submission' }}</h2>
                                <p class="text-sm text-gray-500 mt-1">{{ $assignment->task_type }} - {{ $assignment->product->unique_id }}</p>
                            </div>
                            <button type="button" onclick="window.history.back()"
                                    class="px-5 py-2 bg-[#0078C8] text-white rounded-md text-xs font-medium hover:bg-[#0063A6] transition-colors shadow-sm w-full sm:w-auto text-center">
                                ← BACK
                            </button>
                        </div>

                        <hr class="border-gray-200 mb-6">

                        <!-- Product Info Summary -->
                        <div class="bg-blue-50 p-4 rounded-lg mb-6 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-blue-600 font-semibold uppercase">Product Details</span>
                                <h3 class="text-lg font-medium text-gray-900 mt-1">{{ $assignment->product->title }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-mono">{{ $assignment->product->unique_id }}</span> •
                                    {{ $assignment->product->category->name ?? 'N/A' }} •
                                    Location: {{ $assignment->product->location ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-blue-600 font-semibold uppercase">Assigned To</span>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $assignment->technician->full_name ?? $assignment->technician->email ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $assignment->task_type }}</p>
                                @if($assignment->priority)
                                <p class="text-xs mt-1">
                                    @php
                                        $priorityColor = match($assignment->priority) {
                                            'urgent' => 'text-red-600', 'high' => 'text-orange-600',
                                            'medium' => 'text-yellow-600', default => 'text-green-600'
                                        };
                                    @endphp
                                    <span class="{{ $priorityColor }} font-medium">{{ ucfirst($assignment->priority) }} Priority</span>
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Task Instructions (if any) -->
                        @if($assignment->remarks)
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <span class="text-xs font-semibold text-gray-600 uppercase">Instructions</span>
                            <p class="text-sm text-gray-800 mt-1">{{ $assignment->remarks }}</p>
                        </div>
                        @endif

                        <!-- ── Checklist Section ── -->
                        <div class="border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Task Checklist
                            </h3>

                            <div id="checklistContainer" class="space-y-3">
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[task_completed]" value="pass"
                                           class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                           {{ isset($generalChecklistItems['task_completed']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Task completed as specified</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[tools_used]" value="pass"
                                           class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                           {{ isset($generalChecklistItems['tools_used']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Tools and equipment used appropriately</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[no_damage]" value="pass"
                                           class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                           {{ isset($generalChecklistItems['no_damage']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">No damage caused during task</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[area_cleaned]" value="pass"
                                           class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                           {{ isset($generalChecklistItems['area_cleaned']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Work area cleaned after task</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[documentation_done]" value="pass"
                                           class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                           {{ isset($generalChecklistItems['documentation_done']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Documentation completed</label>
                                </div>
                                @foreach($customChecklistItems as $key => $value)
                                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                        <input type="checkbox" name="checklist_items[{{ $key }}]" value="pass" checked
                                            class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
                                        <label class="ml-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Add Custom Checklist Item -->
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Add Custom Checklist Item</label>
                                <div class="flex gap-2">
                                    <input type="text" id="customChecklistItem" placeholder="Enter custom checklist point..."
                                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]">
                                    <button type="button" onclick="addCustomChecklistItem()"
                                            class="px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                                        ADD
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Technician Notes -->
                        <div class="mb-6">
                            <label for="technician_notes" class="block text-sm font-medium text-gray-700 mb-2">Technician Notes</label>
                            <textarea id="technician_notes" name="technician_notes" rows="4"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]"
                                      placeholder="Enter your detailed notes about the task...">{{ old('technician_notes', $existingResult->technician_notes ?? '') }}</textarea>
                        </div>

                        @php
                            $cosmeticGradeMap = [
                                'C0' => ['name' => 'Not Categorized',    'desc' => 'Not categorized.'],
                                'C1' => ['name' => 'Damaged',             'desc' => 'For materials recovery.'],
                                'C2' => ['name' => 'Used Poor',           'desc' => 'Heavy use and age. Cosmetic blemishes are significant such as multiple heavy dents, may require repair. Damages to latches, hinges, keyboards etc. are prevalent. Critical parts are missing.'],
                                'C3' => ['name' => 'Used Fair',           'desc' => 'Moderate use and age. Cosmetic blemishes consistent with wear including multiple dents, discoloration and light to heavy scratches. Damage to latches, hinges, keyboards etc. Some parts, panels, bezels or covers may be missing.'],
                                'C4' => ['name' => 'Used Good',           'desc' => 'Cosmetic blemishes include scratches and/or surface imperfections from consistent use, but equipment is in good condition overall. Minor wear of labeling may be visible. Small dents and areas of discoloration may be present. No damage to latches or hinges, nor any missing parts, panels, bezels or covers.'],
                                'C5' => ['name' => 'Used Very Good',      'desc' => 'Cosmetic blemishes include minor scratches and/or surface imperfections from consistent use, equipment is in good condition. Minor wear of labeling may be visible. Minor dents and areas of discoloration from removal of tags. No missing parts.'],
                                'C6' => ['name' => 'Used Excellent',      'desc' => 'Overall excellent cosmetic condition. May have light scratches or other minor blemishes from normal wear. Minor cosmetic blemishes have no impact on functionality or performance. Fully cleaned with all non-original labels removed. No missing parts.'],
                                'C7' => ['name' => 'Certified Pre-Owned', 'desc' => 'Seller or Manufacturer Refurbished. Fully restored to like-new condition with warranty. Fully cleaned and cosmetically repaired with no blemishes. No missing parts.'],
                                'C8' => ['name' => 'Unused',              'desc' => 'New, out of box, unused equipment removed from original packaging with no signs of wear or alteration.'],
                                'C9' => ['name' => 'New Open Box',        'desc' => 'New, unused equipment in original packaging where factory seals have been broken. No signs of wear or alteration.'],
                            ];
                            $functionalGradeMap = [
                                'F1' => ['name' => 'Collectible / Specialty', 'desc' => 'Collectibles are rare, vintage, and no longer manufactured or supported by the OEM. Specialty equipment are rare and specialized equipment not generally available in retail. May have broken or missing parts.'],
                                'F2' => ['name' => 'Verified Specialty',       'desc' => 'Verified removed from operation with no known defects in functionality. No physical damage or defects. No corrosion. No missing parts. Part numbers and serial numbers verified accurate.'],
                                'F3' => ['name' => 'Key Functions Working',    'desc' => 'A subset of the primary functions expected by an ordinary user are verified working through manual or software tests. Software may not be loaded. Hardware for key functions may have been removed after testing (e.g. Hard Drive). Secondary functions may not be tested. All missing components will be listed.'],
                                'F4' => ['name' => 'Hardware Functional',      'desc' => 'All hardware is tested and verified working through manual or software tests. No missing or damaged components or parts. Software not loaded or configured. No hardware defects.'],
                                'F5' => ['name' => 'Refurbished',              'desc' => 'All functions tested and verified working through software tests. Loaded and configured with legally licensed software for full operations. Software test results are available. No hardware or software defects.'],
                                'F6' => ['name' => 'Like New',                 'desc' => "All functions tested and verified working through software tests. Repaired with OEM original parts. Loaded and configured with original manufacturer's legally licensed software. Meets OEM specifications for full original functionality. Software test results are available. Zero defects."],
                            ];
                            $currentCosmeticGrade = old('cosmetic_grade', $generalData['cosmetic_grade'] ?? ($assignment->product->visualInspections->first()->grade ?? ''));
                            $currentFunctionalGrade = old('functional_grade', $generalData['functional_grade'] ?? ($assignment->product->functionalTestings->first()->grade ?? ''));
                        @endphp

                        <!-- Cosmetic Grade Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Assign Cosmetic Grade</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                                @foreach($cosmeticGradeMap as $code => $info)
                                <label class="relative group cursor-pointer block">
                                    <input type="radio" name="cosmetic_grade" value="{{ $code }}"
                                        class="sr-only peer"
                                        {{ $currentCosmeticGrade == $code ? 'checked' : '' }}>
                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center transition-all duration-150 peer-checked:border-[#0078C8] peer-checked:bg-blue-50 hover:border-[#0078C8] hover:shadow-sm">
                                        <div class="text-base font-bold text-gray-800">{{ $code }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5 leading-tight">{{ $info['name'] }}</div>
                                    </div>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 bg-gray-900 text-white text-xs rounded-lg p-3 opacity-0 group-hover:opacity-100 pointer-events-none z-50 transition-opacity duration-200 shadow-xl">
                                        <div class="font-semibold mb-1">{{ $code }} - {{ $info['name'] }}</div>
                                        <div class="text-gray-300 leading-relaxed">{{ $info['desc'] }}</div>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Functional Grade Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Assign Functional Grade</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                                @foreach($functionalGradeMap as $code => $info)
                                <label class="relative group cursor-pointer block">
                                    <input type="radio" name="functional_grade" value="{{ $code }}"
                                        class="sr-only peer"
                                        {{ $currentFunctionalGrade == $code ? 'checked' : '' }}>
                                    <div class="border-2 border-gray-200 rounded-lg p-3 text-center transition-all duration-150 peer-checked:border-[#0078C8] peer-checked:bg-blue-50 hover:border-[#0078C8] hover:shadow-sm">
                                        <div class="text-base font-bold text-gray-800">{{ $code }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5 leading-tight">{{ $info['name'] }}</div>
                                    </div>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 bg-gray-900 text-white text-xs rounded-lg p-3 opacity-0 group-hover:opacity-100 pointer-events-none z-50 transition-opacity duration-200 shadow-xl">
                                        <div class="font-semibold mb-1">{{ $code }} - {{ $info['name'] }}</div>
                                        <div class="text-gray-300 leading-relaxed">{{ $info['desc'] }}</div>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Grade Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Assign Grade</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach(['A' => 'Excellent', 'B' => 'Good', 'C' => 'Fair', 'F' => 'Failed'] as $grade => $label)
                                <div class="grade-option border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] hover:shadow-md transition-all"
                                     data-grade="{{ $grade }}" onclick="selectGrade('{{ $grade }}', this)">
                                    <div class="text-xl font-semibold text-gray-900">{{ $grade }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                                </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="grade" id="selectedGrade" value="{{ $currentTaskGrade }}">
                        </div>

                        <!-- Remarks -->
                        <div class="mb-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Additional Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]"
                                      placeholder="Any additional remarks...">{{ old('remarks', $existingResult->remarks ?? '') }}</textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" onclick="window.history.back()"
                                    class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                CANCEL
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-md">
                                {{ $isEditing ? 'UPDATE RESULTS' : 'SUBMIT RESULTS' }}
                            </button>
                        </div>
                    </div>
                </form>
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
    // Restore grade selection on validation failure
    const oldGrade = '{{ $currentTaskGrade }}';
    if (oldGrade) {
        document.querySelectorAll('.grade-option').forEach(opt => {
            if (opt.dataset.grade === oldGrade) selectGrade(oldGrade, opt);
        });
    }

    function selectGrade(grade, element) {
        document.querySelectorAll('.grade-option').forEach(opt => {
            opt.classList.remove('bg-[#0078C8]', 'text-white', 'border-[#0078C8]', 'shadow-md');
            const titleDiv = opt.querySelector('div:first-child');
            const descDiv  = opt.querySelector('div:last-child');
            if (titleDiv) { titleDiv.classList.remove('text-white'); titleDiv.classList.add('text-gray-900'); }
            if (descDiv)  { descDiv.classList.remove('text-white');  descDiv.classList.add('text-gray-500'); }
        });

        element.classList.add('bg-[#0078C8]', 'text-white', 'border-[#0078C8]', 'shadow-md');
        const titleDiv = element.querySelector('div:first-child');
        const descDiv  = element.querySelector('div:last-child');
        if (titleDiv) { titleDiv.classList.remove('text-gray-900'); titleDiv.classList.add('text-white'); }
        if (descDiv)  { descDiv.classList.remove('text-gray-500');  descDiv.classList.add('text-white'); }

        document.getElementById('selectedGrade').value = grade;
    }

    function addCustomChecklistItem() {
        const input    = document.getElementById('customChecklistItem');
        const itemText = input.value.trim();

        if (!itemText) { showToast('Please enter a checklist item', 'error'); return; }

        const container = document.getElementById('checklistContainer');
        const itemId    = 'custom_' + Date.now();

        const div = document.createElement('div');
        div.className = 'flex items-center p-3 bg-gray-50 rounded-lg';
        div.innerHTML = `
            <input type="checkbox" name="checklist_items[${itemId}]" value="pass"
                   class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
            <label class="ml-3 text-sm text-gray-700">${itemText}</label>
            <button type="button" onclick="this.closest('.flex').remove()" class="ml-auto text-red-500 hover:text-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        container.appendChild(div);
        input.value = '';
        showToast('Checklist item added', 'success');
    }

    function showToast(message, type = 'success') {
        const toast   = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');

        const icon = type === 'success'
            ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>';

        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">${icon}</div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
        setTimeout(hideToast, 3000);
    }

    function hideToast() { document.getElementById('toast').classList.add('hidden'); }
</script>
@endpush
