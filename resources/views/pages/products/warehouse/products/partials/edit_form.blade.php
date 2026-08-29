<form id="formSubmit" action_url="{{ route('warehouse.products.update', $product->id) }}"
    back_url="{{ request('redirect_to', route('warehouse.products.index')) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="p-6">

        <hr class="border-gray-200 mb-6">

        <!-- Product ID - Full Width -->
        <div class="mb-6">
            <label
                class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PRODUCT
                ID</label>
            <div class="text-sm font-medium text-gray-900 bg-gray-50 px-4 py-2.5 rounded-md border border-gray-200"
                id="productIdValue">{{ $product->unique_id ?? 'product-' . $product->id }}</div>
            <input type="hidden" name="product_id" id="productIdInput" value="{{ $product->id }}">
            <input type="hidden" name="unique_id" value="{{ $product->unique_id ?? '' }}">
        </div>

        <!-- Two Column Layout for Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Category Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category <span
                        class="text-red-500">*</span></label>
                <select name="category_id"
                    class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ ($product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Title <span
                        class="text-red-500">*</span></label>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $product->title) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                    placeholder="Enter product title">
            </div>

            <!-- DPN/SN Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">DPN/SN Number</label>
                <input type="text" id="dpn_sn_number" name="dpn_sn_number"
                    value="{{ old('dpn_sn_number', $product->dpn_sn_number) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                    placeholder="Enter DPN/SN number">
            </div>
        </div>

        <!-- Two Column Layout for Location and Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Location -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <select name="location"
                    class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="">Select Location</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}"
                            {{ ($product->warehouse_location_id ?? '') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="Active"
                        {{ ($product->status ?? 'Active') == 'Active' ? 'selected' : '' }}>Ready for
                        Sale</option>
                    <option value="Inactive"
                        {{ ($product->status ?? '') == 'Inactive' ? 'selected' : '' }}>Not Ready for
                        Sale</option>
                </select>
            </div>
        </div>

        @if($product->manual_status == 1)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Count</label>
                <input type="number" name="count" value="{{ old('count', $product->count ?? 0) }}" min="0"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                    placeholder="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <input type="text" name="problem" value="{{ old('problem', $product->problem ?? '') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                    placeholder="Enter notes description">
            </div>
        </div>
        @endif

        @if($product->manual_status != 1)
        <!-- Two Column Layout for Disposition and Work Flow -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Disposition -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Disposition</label>
                <select name="disposition_id"
                    class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="">Select Disposition</option>
                    @foreach ($dispositions as $disposition)
                        <option value="{{ $disposition->id }}"
                            {{ ($product->disposition_id ?? '') == $disposition->id ? 'selected' : '' }}>
                            {{ $disposition->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Work Flow Stage -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Work Flow Stage</label>
                <select name="work_flow_stage_id"
                    class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="">Select Work Flow Stage</option>
                    @foreach ($workflowStages as $stage)
                        <option value="{{ $stage->id }}"
                            {{ ($product->work_flow_stage_id ?? '') == $stage->id ? 'selected' : '' }}>
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Product Details - Full Width -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product Details</label>
            <textarea id="productDetails" name="product_details" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                placeholder="Add detailed product description...">{{ old('product_details', $product->product_details) }}</textarea>
        </div>

        <!-- Specification Section - Now with separate boxes for key and value -->
        <div class="mb-6 bg-gray-50 p-5 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-3">Specifications</label>

            <div id="specificationsList" class="space-y-2 mb-4"></div>

            <!-- Two identical input boxes for key and value -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                <div>
                    <input type="text" id="specKeyInput"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                        placeholder="Enter specification key (e.g., Color, Size, Weight)">
                </div>
                <div class="flex gap-2">
                    <input type="text" id="specValueInput"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                        placeholder="Enter specification value (e.g., Red, Large, 2kg)">
                    <button type="button" onclick="addSpecification()"
                        class="px-6 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-sm whitespace-nowrap">
                        ADD
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Edit existing values directly, or press Enter in the new value field to add another specification.</p>
            <input type="hidden" name="specifications" id="specificationsInput"
                value="{{ old('specifications', json_encode($product->specifications ?? [])) }}">
        </div>

        <!-- Visual Inspection Section -->
        <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                    Visual Inspection
                </h3>
                <button type="button" onclick="toggleSection('visualInspectionContent')"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <div id="visualInspectionContent" class="space-y-5">
                <div id="visualDynamicChecklist" class="space-y-2 mb-4 bg-gray-50 p-4 rounded-lg">
                </div>

                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Inspection Point</h4>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" id="visualNewChecklistItem"
                            placeholder="Enter new inspection point..."
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        <button type="button" onclick="addVisualChecklistItem()"
                            class="px-6 py-2.5 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                            ADD POINT
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Press Enter or click ADD POINT to create a
                        new checkbox</p>
                </div>

                <div>
                    <label for="visualInspectionNotes"
                        class="block text-sm font-medium text-gray-700 mb-2">Inspection Notes</label>
                    <textarea id="visualInspectionNotes" name="visual_inspection_notes" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                        placeholder="Enter any additional inspection notes...">{{ old('visual_inspection_notes', $product->visualInspections->first()->inspection_notes ?? '') }}</textarea>
                </div>

            </div>
        </div>

        <!-- Functional Testing Section -->
        <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#0078C8]" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    Functional Testing
                </h3>
                <button type="button" onclick="toggleSection('functionalTestingContent')"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <div id="functionalTestingContent" class="space-y-5">
                <div id="functionalDynamicTestFields" class="space-y-4 mb-4"></div>

                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Test</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Test
                                Name</label>
                            <input type="text" id="functionalNewTestName"
                                placeholder="Enter test name..."
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                            <input type="text" id="functionalNewTestNotes"
                                placeholder="Enter test notes..."
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="addFunctionalTestField()"
                                class="w-full px-6 py-2.5 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
                                ADD TEST
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Press Enter in either input field to add test
                    </p>
                </div>

                <div>
                    <label for="functionalTestResults"
                        class="block text-sm font-medium text-gray-700 mb-2">Overall Test Results
                        Summary</label>
                    <textarea id="functionalTestResults" name="functional_test_results" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                        placeholder="Enter overall test summary...">{{ old('functional_test_results', $product->functionalTestings->first()->overall_results_summary ?? '') }}</textarea>
                </div>

            </div>
        </div>

        <!-- Assigned Personnel -->
        <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Assigned Personnel</h4>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-sm font-medium shadow-sm">
                            {{ $product->assignedTo ? strtoupper(substr($product->assignedTo->full_name, 0, 2)) : 'NA' }}
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900" id="assignedPersonnel">
                                {{ $product->assignedTo->full_name ?? 'Unassigned' }}
                            </span>
                            <p class="text-xs text-gray-500">Current assignee</p>
                        </div>
                        <input type="hidden" name="assigned_to" id="assignedToInput"
                            value="{{ $product->assigned_to ?? '' }}">
                    </div>
                </div>
                <button type="button" onclick="openChangeAssigneeModal()"
                    class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-xs font-medium hover:bg-[#0063A6] transition-colors shadow-sm">
                    CHANGE ASSIGNEE
                </button>
            </div>
        </div>
        @endif

        {{-- ── Cosmetic Grade (shown for ALL products) ── --}}
        @php
            $cosmeticGrade = $product->visualInspections->first()->grade ?? '';
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
        @endphp
        <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Cosmetic Grade</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                @foreach($cosmeticGradeMap as $code => $info)
                <label class="relative group cursor-pointer block">
                    <input type="radio" name="visual_grade" value="{{ $code }}"
                        class="sr-only peer"
                        {{ $cosmeticGrade === $code ? 'checked' : '' }}>
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

        {{-- ── Functional Grade (shown for ALL products) ── --}}
        @php
            $functionalCategoryGrade = $product->functionalTestings->first()->grade ?? '';
            $functionalGradeMap = [
                'F1' => ['name' => 'Collectible / Specialty', 'desc' => 'Collectibles are rare, vintage, and no longer manufactured or supported by the OEM. Specialty equipment are rare and specialized equipment not generally available in retail. May have broken or missing parts.'],
                'F2' => ['name' => 'Verified Specialty',       'desc' => 'Verified removed from operation with no known defects in functionality. No physical damage or defects. No corrosion. No missing parts. Part numbers and serial numbers verified accurate.'],
                'F3' => ['name' => 'Key Functions Working',    'desc' => 'A subset of the primary functions expected by an ordinary user are verified working through manual or software tests. Software may not be loaded. Hardware for key functions may have been removed after testing (e.g. Hard Drive). Secondary functions may not be tested. All missing components will be listed.'],
                'F4' => ['name' => 'Hardware Functional',      'desc' => 'All hardware is tested and verified working through manual or software tests. No missing or damaged components or parts. Software not loaded or configured. No hardware defects.'],
                'F5' => ['name' => 'Refurbished',              'desc' => 'All functions tested and verified working through software tests. Loaded and configured with legally licensed software for full operations. Software test results are available. No hardware or software defects.'],
                'F6' => ['name' => 'Like New',                 'desc' => 'All functions tested and verified working through software tests. Repaired with OEM original parts. Loaded and configured with original manufacturer\'s legally licensed software. Meets OEM specifications for full original functionality. Software test results are available. Zero defects.'],
            ];
        @endphp
        <div class="border border-gray-200 rounded-lg p-5 mb-6 bg-white shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Functional Grade</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                @foreach($functionalGradeMap as $code => $info)
                <label class="relative group cursor-pointer block">
                    <input type="radio" name="functional_grade" value="{{ $code }}"
                        class="sr-only peer"
                        {{ $functionalCategoryGrade === $code ? 'checked' : '' }}>
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

        <!-- Action Buttons -->
        @php
            $currentDisposition = strtolower($product->disposition->name ?? '');
            $isScrap = $currentDisposition === 'scrap';
            $isHold  = $currentDisposition === 'hold';
            $isPass  = $currentDisposition === 'pass';
        @endphp
        <div
            class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-200 gap-4">
            <div>

            </div>
            <div class="flex gap-3">
                @if(request()->ajax())
                    <button type="button" onclick="hideEditProductModal()"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                        CANCEL
                    </button>
                @else
                    <a href="{{ route('warehouse.products.index') }}"
                        class="px-5 py-2.5 border border-[#0078C8] text-[#0078C8] rounded-lg text-sm font-medium hover:bg-[#e6f4ff] transition-colors">
                        CANCEL
                    </a>
                @endif
                <button type="submit" id="submitBtn"
                    class="px-8 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    UPDATE PRODUCT
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Change Assignee Modal -->
<div id="changeAssigneeModal"
    class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 backdrop-blur-sm">
    <div class="relative top-20 mx-auto p-6 border w-[90%] md:w-[450px] shadow-xl rounded-xl bg-white">
        <div class="mt-1">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-semibold text-gray-900">Change Assignee</h3>
                <button onclick="closeChangeAssigneeModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-5">
                <label for="assigneeSelect" class="block text-sm font-medium text-gray-700 mb-2">Select
                    Personnel</label>
                <select id="assigneeSelect"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                    <option value="">Select personnel...</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}"
                            data-initials="{{ strtoupper(substr($technician->full_name, 0, 2)) }}"
                            {{ ($product->assigned_to ?? '') == $technician->id ? 'selected' : '' }}>
                            {{ $technician->full_name }} ({{ $technician->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button onclick="closeChangeAssigneeModal()"
                    class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    CANCEL
                </button>
                <button onclick="updateAssignee()"
                    class="px-5 py-2.5 bg-[#0078C8] text-white rounded-lg text-sm font-medium hover:bg-[#0063A6] transition-colors shadow-sm">
                    CHANGE ASSIGNEE
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast"
    class="fixed top-4 right-4 z-[99999] hidden min-w-[300px] max-w-[380px] bg-white border border-gray-200 shadow-lg rounded-lg p-4">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
