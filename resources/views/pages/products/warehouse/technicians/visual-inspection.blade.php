{{-- resources/views/pages/products/warehouse/technicians/visual-inspection.blade.php --}}
@extends('layouts.auth')

@section('title', 'Visual Inspection - ' . $assignment->product->unique_id)

@section('content')
<x-acccount.loader />
@php
    $isEditing = $isEditing ?? false;
    $existingResult = $existingResult ?? null;
    $visualChecklistItems = old('checklist_items', $existingResult->checklist_items ?? []);
    $visualChecklistItems = is_array($visualChecklistItems) ? $visualChecklistItems : [];
    $defaultChecklistKeys = ['physical_damage', 'cleanliness', 'ports_connectors', 'screen_display', 'keyboard_buttons'];
    $customChecklistItems = array_diff_key($visualChecklistItems, array_flip($defaultChecklistKeys));
    $currentCosmeticGrade = old('cosmetic_grade', $existingResult->grade ?? ($assignment->product->visualInspections->first()->grade ?? ''));
    $currentFunctionalGrade = old('functional_grade', $assignment->product->functionalTestings->first()->grade ?? '');
@endphp
<div class="mt-4">
    <div class="mx-auto px-3">
        <div class="overflow-hidden">
            <!-- Main Content Card -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <form id="visualInspectionForm" action="{{ route('warehouse.technicians.submit.visual', $assignment->id) }}" method="POST">
                    @csrf

                    <div class="p-6">
                        <!-- Header with Back Button -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $isEditing ? 'Edit Visual Inspection' : 'Visual Inspection' }}</h2>
                                <p class="text-sm text-gray-500 mt-1">Product: {{ $assignment->product->unique_id }} - {{ $assignment->product->title }}</p>
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
                            </div>
                        </div>

                        <!-- Checklist Section -->
                        <div class="border border-gray-200 rounded-lg p-5 mb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Inspection Checklist
                            </h3>

                            <div id="checklistContainer" class="space-y-3">
                                <!-- Default checklist items -->
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[physical_damage]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                        {{ isset($visualChecklistItems['physical_damage']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">No physical damage (cracks, dents, scratches)</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[cleanliness]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                        {{ isset($visualChecklistItems['cleanliness']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Clean, no dust or debris</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[ports_connectors]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                        {{ isset($visualChecklistItems['ports_connectors']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">All ports and connectors intact</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[screen_display]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                        {{ isset($visualChecklistItems['screen_display']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Screen/display free from cracks or dead pixels</label>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <input type="checkbox" name="checklist_items[keyboard_buttons]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300"
                                        {{ isset($visualChecklistItems['keyboard_buttons']) ? 'checked' : '' }}>
                                    <label class="ml-3 text-sm text-gray-700">Keyboard/buttons functional</label>
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
                                    <input type="text" id="customChecklistItem" placeholder="Enter custom inspection point..."
                                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]">
                                    <button type="button" onclick="addCustomChecklistItem()"
                                            class="px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                                        ADD
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Inspection Notes -->
                        <div class="mb-6">
                            <label for="inspection_notes" class="block text-sm font-medium text-gray-700 mb-2">Inspection Notes</label>
                            <textarea id="inspection_notes" name="inspection_notes" rows="3"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8]"
                                      placeholder="Enter detailed inspection notes...">{{ old('inspection_notes', $existingResult->inspection_notes ?? '') }}</textarea>
                        </div>

                        <!-- Cosmetic Grade Selection -->
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
                        @endphp

                        <!-- Cosmetic Grade -->
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
                                        <div class="font-semibold mb-1">{{ $code }} – {{ $info['name'] }}</div>
                                        <div class="text-gray-300 leading-relaxed">{{ $info['desc'] }}</div>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Functional Grade -->
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
                                        <div class="font-semibold mb-1">{{ $code }} – {{ $info['name'] }}</div>
                                        <div class="text-gray-300 leading-relaxed">{{ $info['desc'] }}</div>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
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
    // Grade selection via radio inputs (peer-checked handles visual state)

    // Add custom checklist item
    function addCustomChecklistItem() {
        const input = document.getElementById('customChecklistItem');
        const itemText = input.value.trim();

        if (!itemText) {
            showToast('Please enter a checklist item', 'error');
            return;
        }

        const container = document.getElementById('checklistContainer');
        const itemId = 'custom_' + Date.now();

        const div = document.createElement('div');
        div.className = 'flex items-center p-3 bg-gray-50 rounded-lg';
        div.innerHTML = `
            <input type="checkbox" name="checklist_items[${itemId}]" value="pass" class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
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

    // Toast functions
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
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

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    // @if(session('success'))
    //     showToast('{{ session('success') }}', 'success');
    // @endif

    // @if(session('error'))
    //     showToast('{{ session('error') }}', 'error');
    // @endif

    // @if($errors->any())
    //     showToast('{{ $errors->first() }}', 'error');
    // @endif
</script>
@endpush
