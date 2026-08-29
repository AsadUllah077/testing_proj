@extends('layouts.auth')

@section('title', 'Extract Part - ' . $product->title)

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header -->
                <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-5 mb-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-black">{{ $product->title }}</h2>
                            <div class="flex items-center gap-4 mt-1 flex-wrap">
                                <span class="text-sm text-gray-500 font-mono">{{ $product->unique_id }}</span>
                                @if($product->location)
                                    <span class="text-sm text-gray-500">📍 {{ $product->location }}</span>
                                @endif
                                @if($product->category)
                                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">{{ $product->category->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                            <a href="{{ route('warehouse.products.history', $product->id) }}"
                               class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 w-full sm:w-auto text-center">
                                VIEW HISTORY
                            </a>
                            <a href="{{ route('warehouse.products.index') }}"
                               class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] w-full sm:w-auto text-center">
                                BACK
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Current Specifications (read-only reference) -->
                @if(!empty($product->specifications))
                <div class="bg-white border border-gray-200 rounded-md shadow-sm p-5 mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Current Specifications</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($product->specifications as $spec)
                        <div class="bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                            <div class="text-xs text-gray-500">{{ is_array($spec) ? ($spec['key'] ?? '') : $spec }}</div>
                            <div class="text-sm font-medium text-gray-800">{{ is_array($spec) ? ($spec['value'] ?? '') : '' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Main Extract Form -->
                <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 4v8m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                        Add Parts to Extract
                    </h3>

                    <!-- Parts List -->
                    <div id="partsList" class="space-y-3 mb-4"></div>

                    <!-- Add Part Row -->
                    <div class="flex gap-3 items-center">
                        <div class="flex-1">
                            <input type="text" id="newPartName" placeholder="Part name (e.g., RAM, SSD, GPU...)"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        </div>
                        <div class="w-40">
                            <input type="text" id="newPartValue" placeholder="Value/Spec"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        </div>
                        <button type="button" onclick="addPart()"
                                class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] whitespace-nowrap">
                            ADD
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Tab to value field, then Enter or click ADD to save</p>
                </div>

                <!-- Remaining Product Disposal -->
                <div class="bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Remaining Product Disposal
                    </h3>

                    <!-- Toggle: Scrap or Keep -->
                    <div class="flex items-center gap-6 mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="remainingAction" value="scrap" id="actionScrap"
                                   class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 cursor-pointer" checked>
                            <span class="text-sm font-medium text-gray-700">Move Remaining to Scrap</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="remainingAction" value="keep" id="actionKeep"
                                   class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 cursor-pointer">
                            <span class="text-sm font-medium text-gray-700">Keep Product (Update Specs Only)</span>
                        </label>
                    </div>

                    <!-- Scrap Form -->
                    <div id="scrapForm" class="space-y-4">

                        @if($scrapCategories->isEmpty())
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-xs text-yellow-700 font-medium">
                                ⚠ No scrap categories found. Please add categories in Scrap Master Data first.
                            </p>
                        </div>
                        @endif

                        @if($scrapTypes->isEmpty())
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-xs text-yellow-700 font-medium">
                                ⚠ No scrap types found. Please add types in Scrap Master Data first.
                            </p>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Item Description</label>
                                <input type="text" id="itemDescription"
                                       placeholder="Describe remaining items..."
                                       class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                            </div>

                            <!-- Material Category — independent -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Material Category <span class="text-red-500">*</span>
                                </label>
                                <select id="materialCategory"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                    <option value="">Select category</option>
                                    @foreach($scrapCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Material Type — independent, shows all types -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Material Type <span class="text-red-500">*</span>
                                </label>
                                <select id="materialType"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                    <option value="">Select type</option>
                                    @foreach($scrapTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" id="weight" step="0.01" min="0" placeholder="0.00"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Disposal Reason / Note</label>
                                <input type="text" id="disposalReason"
                                       placeholder="Reason for disposal..."
                                       class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Keep Form -->
                    <div id="keepForm" class="hidden">
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700 mb-3">
                                <strong>Note:</strong> The product will remain active. Only the specifications will be updated to reflect the extracted parts.
                            </p>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keep Notes</label>
                            <textarea id="keepNotes" rows="2"
                                      placeholder="Add any notes about keeping this product..."
                                      class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="bg-white border border-gray-200 rounded-md shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div id="summaryText" class="text-sm text-gray-500">No parts added yet</div>
                        <div class="flex flex-col-reverse sm:flex-row gap-3 w-full sm:w-auto">
                            <a href="{{ route('warehouse.products.index') }}"
                               class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 w-full sm:w-auto text-center">
                                CANCEL
                            </a>
                            <button type="button" onclick="submitExtraction()" id="finalizeBtn"
                                    class="px-6 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] flex items-center justify-center gap-2 w-full sm:w-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span id="finalizeBtnText">FINALIZE EXTRACTION & MOVE TO SCRAP</span>
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
    const productId = {{ $product->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Parts being extracted
    let parts = [];

    // ── Render parts list ─────────────────────────────────────────────────
    function renderParts() {
        const container   = document.getElementById('partsList');
        const summaryText = document.getElementById('summaryText');
        container.innerHTML = '';

        if (parts.length === 0) {
            summaryText.textContent = 'No parts added yet';
            updateFinalizeBtn();
            return;
        }

        parts.forEach((part, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg';
            div.innerHTML = `
                <div class="flex-1">
                    <input type="text" value="${escapeHtml(part.name)}"
                           onchange="updatePart(${index}, 'name', this.value)"
                           class="w-full bg-white border border-gray-200 rounded px-2 py-1 text-sm font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0078C8]"
                           placeholder="Part name">
                </div>
                <div class="w-44">
                    <input type="text" value="${escapeHtml(part.value)}"
                           onchange="updatePart(${index}, 'value', this.value)"
                           class="w-full bg-white border border-gray-200 rounded px-2 py-1 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#0078C8]"
                           placeholder="Value/Spec">
                </div>
                <button type="button" onclick="removePart(${index})"
                        class="text-red-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50 transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            `;
            container.appendChild(div);
        });

        summaryText.textContent = `${parts.length} part(s) ready to extract`;
        updateFinalizeBtn();
    }

    function updateFinalizeBtn() {
        const action  = document.querySelector('input[name="remainingAction"]:checked')?.value;
        const btnText = document.getElementById('finalizeBtnText');
        if (!btnText) return;
        btnText.textContent = action === 'keep'
            ? 'FINALIZE EXTRACTION & UPDATE SPECS'
            : 'FINALIZE EXTRACTION & MOVE TO SCRAP';
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    function addPart() {
        const nameInput  = document.getElementById('newPartName');
        const valueInput = document.getElementById('newPartValue');
        const name  = nameInput.value.trim();
        const value = valueInput.value.trim();

        if (!name) {
            showToast('Please enter a part name', 'error');
            nameInput.focus();
            return;
        }

        parts.push({ name, value });
        renderParts();
        nameInput.value  = '';
        valueInput.value = '';
        nameInput.focus();
        showToast(`"${name}" added`, 'success');
    }

    function removePart(index) {
        const name = parts[index]?.name || 'Part';
        parts.splice(index, 1);
        renderParts();
        showToast(`"${name}" removed`, 'success');
    }

    function updatePart(index, field, value) {
        if (parts[index]) parts[index][field] = value;
    }

    // ── Toggle scrap / keep forms ─────────────────────────────────────────
    function toggleDisposalForm() {
        const action    = document.querySelector('input[name="remainingAction"]:checked')?.value;
        const scrapForm = document.getElementById('scrapForm');
        const keepForm  = document.getElementById('keepForm');

        if (action === 'keep') {
            scrapForm.classList.add('hidden');
            keepForm.classList.remove('hidden');
        } else {
            scrapForm.classList.remove('hidden');
            keepForm.classList.add('hidden');
        }
        updateFinalizeBtn();
    }

    // ── Submit extraction ─────────────────────────────────────────────────
    function submitExtraction() {
        if (parts.length === 0) {
            showToast('Please add at least one part to extract', 'error');
            return;
        }

        const remainingAction = document.querySelector('input[name="remainingAction"]:checked')?.value;

        let scrapData = null;
        if (remainingAction === 'scrap') {
            const catSelect  = document.getElementById('materialCategory');
            const typeSelect = document.getElementById('materialType');

            if (!catSelect.value) {
                showToast('Please select a material category', 'error');
                catSelect.focus();
                return;
            }
            if (!typeSelect.value) {
                showToast('Please select a material type', 'error');
                typeSelect.focus();
                return;
            }

            scrapData = {
                item_description:     document.getElementById('itemDescription').value.trim(),
                material_category_id: catSelect.value,
                material_category:    catSelect.options[catSelect.selectedIndex]?.text || '',
                material_type_id:     typeSelect.value,
                material_type:        typeSelect.options[typeSelect.selectedIndex]?.text || '',
                weight:               document.getElementById('weight').value,
                disposal_reason:      document.getElementById('disposalReason').value.trim(),
            };
        }

        const keepNotes = document.getElementById('keepNotes')?.value?.trim() || '';

        const payload = {
            parts,
            remaining_action: remainingAction,
            scrap_data:       scrapData,
            keep_notes:       keepNotes,
        };

        const btn     = document.getElementById('finalizeBtn');
        const btnText = document.getElementById('finalizeBtnText');
        btn.disabled  = true;
        btnText.textContent = 'PROCESSING...';

        fetch(`/warehouse/products/${productId}/extract-part`, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Extraction completed!', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect_url || '{{ route('warehouse.products.index') }}';
                }, 1500);
            } else {
                if (data.errors) {
                    const messages = Object.values(data.errors).flat().join(' ');
                    showToast(messages, 'error');
                } else {
                    showToast(data.message || 'Failed to process extraction', 'error');
                }
                btn.disabled    = false;
                btnText.textContent = remainingAction === 'keep'
                    ? 'FINALIZE EXTRACTION & UPDATE SPECS'
                    : 'FINALIZE EXTRACTION & MOVE TO SCRAP';
            }
        })
        .catch(err => {
            showToast('Network error: ' + err.message, 'error');
            btn.disabled    = false;
            btnText.textContent = remainingAction === 'keep'
                ? 'FINALIZE EXTRACTION & UPDATE SPECS'
                : 'FINALIZE EXTRACTION & MOVE TO SCRAP';
        });
    }

    // ── Toast ─────────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast   = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        const icon = type === 'success'
            ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">${icon}</div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700 ml-2" onclick="hideToast()">✕</button>
            </div>`;
        setTimeout(hideToast, 3500);
    }

    function hideToast() {
        document.getElementById('toast')?.classList.add('hidden');
    }

    // ── Init ──────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Radio button listeners
        document.querySelectorAll('input[name="remainingAction"]').forEach(r => {
            r.addEventListener('change', toggleDisposalForm);
        });

        // Enter key navigation between part inputs
        document.getElementById('newPartName')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('newPartValue')?.focus(); }
        });
        document.getElementById('newPartValue')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addPart(); }
        });

        renderParts();
        updateFinalizeBtn();
    });

    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif
    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
</script>
@endpush
