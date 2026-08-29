{{-- resources/views/pages/products/warehouse/products/edit.blade.php --}}
@extends('layouts.auth')

@section('title', 'Update Product')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Main Content Card -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <form id="updateProductForm" action="#" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-6">
                            <!-- Header with Back Button -->
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-lg font-medium text-gray-900">Update Product</h2>
                                <button type="button" onclick="window.history.back()"
                                        class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                    BACK
                                </button>
                            </div>

                            <hr class="border-gray-200 mb-6">

                            <!-- Product ID -->
                            <div class="mb-4">
                                <label class="block text-xs text-gray-500 mb-1">PRODUCT ID</label>
                                <div class="text-sm text-gray-900" id="productIdValue">product-123</div>
                                <input type="hidden" name="product_id" id="productIdInput">
                            </div>

                            <!-- Title -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-900 mb-2">Title</label>
                                <input type="text" id="title" name="title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                       placeholder="Enter Title">
                            </div>

                            <!-- Specification (Key-Value pairs) -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-900 mb-2">Specification</label>

                                <!-- Existing Specifications -->
                                <div id="specificationsList" class="space-y-2 mb-3">
                                    <!-- Specifications will be added here dynamically -->
                                </div>

                                <!-- Add New Specification -->
                                <div class="flex gap-2">
                                    <input type="text" id="specKeyInput"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                           placeholder="Key">
                                    <input type="text" id="specValueInput"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                           placeholder="Value">
                                    <button type="button" onclick="addSpecification()"
                                            class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                        ADD
                                    </button>
                                    <button type="button" onclick="deleteSpecification()"
                                            class="px-3 py-1.5 bg-red-500 text-white rounded text-xs font-medium hover:bg-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <!-- Hidden input to store all specifications as JSON -->
                                <input type="hidden" name="specifications" id="specificationsInput">
                            </div>

                            <!-- Product Details -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-900 mb-2">Product Details</label>
                                <textarea id="productDetails" name="product_details" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                          placeholder="Add product details"></textarea>
                                <div class="flex justify-end mt-2">
                                    <button type="button" onclick="updateProductDetails()"
                                            class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                        UPDATE
                                    </button>
                                </div>
                            </div>

                            <!-- Visual Inspection Section -->
                            <div class="border border-gray-200 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-medium text-gray-900">Visual Inspection</h3>
                                    <button type="button" onclick="toggleSection('visualInspectionContent')"
                                            class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Visual Inspection Content -->
                                <div id="visualInspectionContent" class="space-y-4">
                                    <!-- Default checkboxes -->
                                    {{-- <div class="space-y-3 mb-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="visualPowerOn" name="visual_checklist[]" value="Power on Successfully"
                                                   class="h-4 w-4 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
                                            <label for="visualPowerOn" class="ml-2 text-sm text-gray-700">Power on Successfully</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="visualKeyWear" name="visual_checklist[]" value="Key Wear"
                                                   class="h-4 w-4 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
                                            <label for="visualKeyWear" class="ml-2 text-sm text-gray-700">Key Wear</label>
                                        </div>
                                    </div> --}}

                                    <!-- Dynamic Checklist Items Container -->
                                    <div id="visualDynamicChecklist" class="space-y-3 mb-4">
                                        <!-- Dynamic items will be added here -->
                                    </div>

                                    <!-- Add New Item Section -->
                                    <div class="mt-6 p-4 border border-gray-300 rounded-lg bg-gray-50">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Inspection Point</h4>
                                        <div class="flex gap-2">
                                            <input type="text" id="visualNewChecklistItem"
                                                   placeholder="Enter new inspection point..."
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            <button type="button" onclick="addVisualChecklistItem()"
                                                    class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                ADD
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Press Enter or click ADD to create a new checkbox</p>
                                    </div>

                                    <!-- Inspection Notes -->
                                    <div class="mb-4">
                                        <label for="visualInspectionNotes" class="block text-sm font-medium text-gray-700 mb-2">Inspection Notes</label>
                                        <textarea id="visualInspectionNotes" name="visual_inspection_notes" rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                                  placeholder="Enter inspection notes..."></textarea>
                                    </div>

                                    <!-- Assign Grading -->
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Assign Grading</h4>
                                        <div class="grid grid-cols-4 gap-3">
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] visual-grade-option"
                                                 data-grade="A" onclick="selectVisualGrade('A', this)">
                                                <div class="text-xl font-semibold text-gray-900">A</div>
                                                <div class="text-xs text-gray-500 mt-1">Like New</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] visual-grade-option"
                                                 data-grade="B" onclick="selectVisualGrade('B', this)">
                                                <div class="text-xl font-semibold text-gray-900">B</div>
                                                <div class="text-xs text-gray-500 mt-1">Minor Wear</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] visual-grade-option"
                                                 data-grade="C" onclick="selectVisualGrade('C', this)">
                                                <div class="text-xl font-semibold text-gray-900">C</div>
                                                <div class="text-xs text-gray-500 mt-1">Major Use</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] visual-grade-option"
                                                 data-grade="F" onclick="selectVisualGrade('F', this)">
                                                <div class="text-xl font-semibold text-gray-900">F</div>
                                                <div class="text-xs text-gray-500 mt-1">Faulty</div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="visual_grade" id="visualGrade" value="">
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="addVisualInspection()"
                                                class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                            ADD
                                        </button>
                                        <button type="button" onclick="updateVisualInspection()"
                                                class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                            UPDATE
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Functional Testing Section -->
                            <div class="border border-gray-200 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-medium text-gray-900">Functional Testing</h3>
                                    <button type="button" onclick="toggleSection('functionalTestingContent')"
                                            class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Functional Testing Content -->
                                <div id="functionalTestingContent" class="space-y-4">
                                    <!-- Dynamic Test Fields Container -->
                                    <div id="functionalDynamicTestFields" class="space-y-4 mb-6">
                                        <!-- Dynamic test fields will be added here -->
                                    </div>

                                    <!-- Add New Test Section -->
                                    <div class="mt-6 p-4 border border-gray-300 rounded-lg bg-gray-50">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Test</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Test Name</label>
                                                <input type="text" id="functionalNewTestName" placeholder="Enter test name..."
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                                                <input type="text" id="functionalNewTestNotes" placeholder="Enter test notes..."
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" onclick="addFunctionalTestField()"
                                                    class="w-[50] px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                    ADD TEST
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Press Enter in either input field to add test</p>
                                    </div>

                                    <!-- Overall Test Results Summary -->
                                    <div class="mb-4">
                                        <label for="functionalTestResults" class="block text-sm font-medium text-gray-700 mb-2">Overall Test Results Summary</label>
                                        <textarea id="functionalTestResults" name="functional_test_results" rows="2"
                                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                            placeholder="Enter overall test summary..."></textarea>
                                    </div>

                                    <!-- Assign Grading -->
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Assign Grading</h4>
                                        <div class="grid grid-cols-4 gap-3">
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] functional-grade-option"
                                                 data-grade="A" onclick="selectFunctionalGrade('A', this)">
                                                <div class="text-xl font-semibold text-gray-900">A</div>
                                                <div class="text-xs text-gray-500 mt-1">Like New</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] functional-grade-option"
                                                 data-grade="B" onclick="selectFunctionalGrade('B', this)">
                                                <div class="text-xl font-semibold text-gray-900">B</div>
                                                <div class="text-xs text-gray-500 mt-1">Minor Wear</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] functional-grade-option"
                                                 data-grade="C" onclick="selectFunctionalGrade('C', this)">
                                                <div class="text-xl font-semibold text-gray-900">C</div>
                                                <div class="text-xs text-gray-500 mt-1">Major Use</div>
                                            </div>
                                            <div class="border border-gray-300 rounded-lg p-3 text-center cursor-pointer hover:border-[#0078C8] functional-grade-option"
                                                 data-grade="F" onclick="selectFunctionalGrade('F', this)">
                                                <div class="text-xl font-semibold text-gray-900">F</div>
                                                <div class="text-xs text-gray-500 mt-1">Faulty</div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="functional_grade" id="functionalGrade" value="">
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="addFunctionalTesting()"
                                                class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                            ADD
                                        </button>
                                        <button type="button" onclick="updateFunctionalTesting()"
                                                class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                            UPDATE
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                                <div class="flex gap-2">
                                    <button type="button" onclick="handleAction('SCRAP')"
                                            class="px-4 py-2 bg-red-600 text-white rounded text-sm font-medium hover:bg-red-700">
                                        SCRAP
                                    </button>
                                    <button type="button" onclick="handleAction('HOLD')"
                                            class="px-4 py-2 bg-yellow-500 text-white rounded text-sm font-medium hover:bg-yellow-600">
                                        HOLD
                                    </button>
                                    <button type="button" onclick="handleAction('PASS')"
                                            class="px-4 py-2 bg-green-600 text-white rounded text-sm font-medium hover:bg-green-700">
                                        PASS
                                    </button>
                                </div>

                                <!-- Main Update Button -->
                                <button type="submit"
                                        class="px-6 py-2 bg-[#0078C8] text-white rounded text-sm font-medium hover:bg-blue-700">
                                    UPDATE
                                </button>
                            </div>

                            <!-- Assigned Personnel -->
                            <div class="border border-gray-200 rounded-lg p-4 mb-6 mt-6">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Assigned Personnel</h4>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white text-xs font-medium">
                                                AK
                                            </div>
                                            <span class="text-sm text-gray-900" id="assignedPersonnel">Alex Kaveria</span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="openChangeAssigneeModal()"
                                            class="px-4 py-1.5 bg-[#0078C8] text-white rounded text-xs font-medium hover:bg-blue-700">
                                        CHANGE ASSIGNEE
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Assignee Modal -->
    <div id="changeAssigneeModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[90%] md:w-[400px] shadow-lg rounded-lg bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Change Assignee</h3>
                    <button onclick="closeChangeAssigneeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <label for="assigneeSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Personnel</label>
                    <select id="assigneeSelect" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                        <option value="">Select personnel...</option>
                        <option value="1" data-initials="AK">Alex Kaveria (Senior Technician)</option>
                        <option value="2" data-initials="SJ">Sarah Johnson (Technician)</option>
                        <option value="3" data-initials="MW">Mike Wilson (Quality Inspector)</option>
                        <option value="4" data-initials="ED">Emma Davis (Testing Specialist)</option>
                        <option value="5" data-initials="JS">John Smith (Lab Manager)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-200">
                    <button onclick="closeChangeAssigneeModal()" class="px-4 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                        CANCEL
                    </button>
                    <button onclick="updateAssignee()" class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-medium hover:bg-blue-700">
                        CHANGE ASSIGNEE
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
<script>
    // Get product ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id') || 'product-123';
    document.getElementById('productIdValue').textContent = productId;
    document.getElementById('productIdInput').value = productId;

    // Store specifications as key-value pairs
    let specifications = [];

    // Toast function
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
        setTimeout(hideToast, 3000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    // Toggle section visibility
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        section.classList.toggle('hidden');
    }

    // Specification functions (Key-Value)
    function addSpecification() {
        const keyInput = document.getElementById('specKeyInput');
        const valueInput = document.getElementById('specValueInput');
        const key = keyInput.value.trim();
        const value = valueInput.value.trim();

        if (!key || !value) {
            showToast('Please enter both key and value', 'error');
            return;
        }

        // Add to specifications array
        specifications.push({ key, value });

        // Update display
        renderSpecifications();

        // Clear inputs
        keyInput.value = '';
        valueInput.value = '';

        // Update hidden input
        updateSpecificationsInput();

        showToast('Specification added successfully', 'success');
    }

    function deleteSpecification() {
        const keyInput = document.getElementById('specKeyInput');
        const valueInput = document.getElementById('specValueInput');
        keyInput.value = '';
        valueInput.value = '';
        showToast('Inputs cleared', 'success');
    }

    function renderSpecifications() {
        const container = document.getElementById('specificationsList');
        container.innerHTML = '';

        specifications.forEach((spec, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between bg-gray-50 p-2 rounded';
            div.innerHTML = `
                <div class="flex-1">
                    <span class="font-medium text-sm text-gray-700">${spec.key}:</span>
                    <span class="text-sm text-gray-600 ml-2">${spec.value}</span>
                </div>
                <button type="button" onclick="removeSpecification(${index})" class="text-red-500 hover:text-red-700 ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
        });
    }

    function removeSpecification(index) {
        specifications.splice(index, 1);
        renderSpecifications();
        updateSpecificationsInput();
        showToast('Specification removed', 'success');
    }

    function updateSpecificationsInput() {
        document.getElementById('specificationsInput').value = JSON.stringify(specifications);
    }

    function updateProductDetails() {
        const details = document.getElementById('productDetails').value;
        if (details.trim()) {
            showToast('Product details updated successfully', 'success');
        } else {
            showToast('Please enter product details', 'error');
        }
    }

    // Visual Inspection Functions
    let visualChecklistCounter = 0;

    function addVisualChecklistItem() {
        const input = document.getElementById('visualNewChecklistItem');
        const itemText = input.value.trim();

        if (!itemText) {
            showToast('Please enter an inspection point', 'error');
            return;
        }

        // Create a unique ID for the checkbox
        const itemId = 'visual-checklist-item-' + visualChecklistCounter++;

        // Create the checkbox element
        const checklistItem = document.createElement('div');
        checklistItem.className = 'flex items-center justify-between group';
        checklistItem.innerHTML = `
            <div class="flex items-center flex-1">
                <input type="checkbox" id="${itemId}" name="visual_checklist[]" value="${itemText}"
                       class="h-4 w-4 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300">
                <label for="${itemId}" class="ml-2 text-sm text-gray-700">${itemText}</label>
            </div>
            <button type="button" onclick="removeVisualChecklistItem(this)"
                    class="ml-2 text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        // Add to the dynamic checklist container
        document.getElementById('visualDynamicChecklist').appendChild(checklistItem);

        // Clear the input
        input.value = '';
        input.focus();

        // Show success message
        showToast('Visual checklist item added successfully', 'success');
    }

    function removeVisualChecklistItem(button) {
        const checklistItem = button.closest('.flex.items-center.justify-between');
        checklistItem.remove();
        showToast('Visual checklist item removed', 'success');
    }

    // Visual grade selection
    function selectVisualGrade(grade, element) {
        // Remove active class from all visual grade options
        document.querySelectorAll('.visual-grade-option').forEach(option => {
            option.classList.remove('bg-[#0078C8]', 'text-white', 'border-[#0078C8]');
            option.querySelector('div:first-child').classList.remove('text-white');
            option.querySelector('div:first-child').classList.add('text-gray-900');
            option.querySelector('div:last-child').classList.remove('text-white');
            option.querySelector('div:last-child').classList.add('text-gray-500');
        });

        // Add active class to selected option
        element.classList.add('bg-[#0078C8]', 'text-white', 'border-[#0078C8]');
        element.querySelector('div:first-child').classList.remove('text-gray-900');
        element.querySelector('div:first-child').classList.add('text-white');
        element.querySelector('div:last-child').classList.remove('text-gray-500');
        element.querySelector('div:last-child').classList.add('text-white');

        // Update hidden input
        document.getElementById('visualGrade').value = grade;
    }

    function addVisualInspection() {
        const notes = document.getElementById('visualInspectionNotes').value;
        const grade = document.getElementById('visualGrade').value;

        if (!grade) {
            showToast('Please assign a grade for visual inspection', 'error');
            return;
        }

        // Collect all checklist items
        const checklistItems = [];
        const checkboxes = document.querySelectorAll('input[name="visual_checklist[]"]:checked');
        checkboxes.forEach(checkbox => {
            checklistItems.push(checkbox.value);
        });

        showToast('Visual inspection added successfully', 'success');
        return { checklistItems, notes, grade };
    }

    function updateVisualInspection() {
        const result = addVisualInspection();
        if (result) {
            showToast('Visual inspection updated successfully', 'success');
        }
    }

    // Functional Testing Functions
    let functionalTestFieldCounter = 0;

    function addFunctionalTestField() {
        const testNameInput = document.getElementById('functionalNewTestName');
        const testNotesInput = document.getElementById('functionalNewTestNotes');

        const testName = testNameInput.value.trim();
        const testNotes = testNotesInput.value.trim();

        if (!testName) {
            showToast('Please enter a test name', 'error');
            testNameInput.focus();
            return;
        }

        if (!testNotes) {
            showToast('Please enter test notes', 'error');
            testNotesInput.focus();
            return;
        }

        // Create a unique ID for the test field
        const testId = 'functional-test-field-' + functionalTestFieldCounter++;
        const sanitizedTestName = testName.toLowerCase().replace(/[^a-z0-9]/g, '_');

        // Create the test field element
        const testField = document.createElement('div');
        testField.className = 'p-4 border border-gray-200 rounded-lg bg-gray-50 group';
        testField.innerHTML = `
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-700">${testName}</h4>
                    <p class="text-xs text-gray-500 mt-1">${testNotes}</p>
                </div>
                <button type="button" onclick="removeFunctionalTestField(this)"
                        class="text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Result</label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="functional_test_result_${sanitizedTestName}" value="pass"
                               class="h-4 w-4 text-[#0078C8]">
                        <span class="ml-2 text-sm text-gray-700">Pass</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="functional_test_result_${sanitizedTestName}" value="fail"
                               class="h-4 w-4 text-[#0078C8]">
                        <span class="ml-2 text-sm text-gray-700">Fail</span>
                    </label>
                </div>
            </div>
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][name]" value="${testName}">
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][notes]" value="${testNotes}">
            <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][result]" value="" id="functional_test_result_${sanitizedTestName}_hidden">
        `;

        // Add to the dynamic test fields container
        document.getElementById('functionalDynamicTestFields').appendChild(testField);

        // Update hidden input when radio changes
        const radioButtons = testField.querySelectorAll(`input[name="functional_test_result_${sanitizedTestName}"]`);
        const hiddenInput = document.getElementById(`functional_test_result_${sanitizedTestName}_hidden`);

        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                hiddenInput.value = this.value;
            });
        });

        // Clear the inputs
        testNameInput.value = '';
        testNotesInput.value = '';
        testNameInput.focus();

        // Show success message
        showToast('Functional test added successfully', 'success');
    }

    function removeFunctionalTestField(button) {
        const testField = button.closest('.p-4.border.border-gray-200.rounded-lg');
        testField.remove();
        showToast('Functional test removed', 'success');
    }

    // Functional grade selection
    function selectFunctionalGrade(grade, element) {
        // Remove active class from all functional grade options
        document.querySelectorAll('.functional-grade-option').forEach(option => {
            option.classList.remove('bg-[#0078C8]', 'text-white', 'border-[#0078C8]');
            option.querySelector('div:first-child').classList.remove('text-white');
            option.querySelector('div:first-child').classList.add('text-gray-900');
            option.querySelector('div:last-child').classList.remove('text-white');
            option.querySelector('div:last-child').classList.add('text-gray-500');
        });

        // Add active class to selected option
        element.classList.add('bg-[#0078C8]', 'text-white', 'border-[#0078C8]');
        element.querySelector('div:first-child').classList.remove('text-gray-900');
        element.querySelector('div:first-child').classList.add('text-white');
        element.querySelector('div:last-child').classList.remove('text-gray-500');
        element.querySelector('div:last-child').classList.add('text-white');

        // Update hidden input
        document.getElementById('functionalGrade').value = grade;
    }

    function addFunctionalTesting() {
        const testResults = document.getElementById('functionalTestResults').value;
        const grade = document.getElementById('functionalGrade').value;

        if (!grade) {
            showToast('Please assign a grade for functional testing', 'error');
            return;
        }

        // Collect all custom tests
        const customTests = {};
        const testFields = document.querySelectorAll('#functionalDynamicTestFields > div');
        testFields.forEach(field => {
            const testName = field.querySelector('h4').textContent;
            const testNotes = field.querySelector('p.text-gray-500').textContent;
            const sanitizedName = testName.toLowerCase().replace(/[^a-z0-9]/g, '_');
            const selectedRadio = field.querySelector(`input[name="functional_test_result_${sanitizedName}"]:checked`);

            customTests[sanitizedName] = {
                name: testName,
                notes: testNotes,
                result: selectedRadio ? selectedRadio.value : ''
            };
        });

        showToast('Functional testing added successfully', 'success');
        return { customTests, testResults, grade };
    }

    function updateFunctionalTesting() {
        const result = addFunctionalTesting();
        if (result) {
            showToast('Functional testing updated successfully', 'success');
        }
    }

    // Handle Enter key press in input fields
    document.getElementById('visualNewChecklistItem').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addVisualChecklistItem();
        }
    });

    document.getElementById('functionalNewTestName').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addFunctionalTestField();
        }
    });

    document.getElementById('functionalNewTestNotes').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addFunctionalTestField();
        }
    });

    // Change Assignee Modal
    function openChangeAssigneeModal() {
        document.getElementById('changeAssigneeModal').classList.remove('hidden');
    }

    function closeChangeAssigneeModal() {
        document.getElementById('changeAssigneeModal').classList.add('hidden');
    }

    function updateAssignee() {
        const select = document.getElementById('assigneeSelect');
        const selectedValue = select.value;

        if (!selectedValue) {
            showToast('Please select a personnel', 'error');
            return;
        }

        // Get selected text
        const selectedOption = select.options[select.selectedIndex];
        const selectedText = selectedOption.text.split(' (')[0]; // Get name only
        const initials = selectedOption.dataset.initials;

        // Update display
        document.getElementById('assignedPersonnel').textContent = selectedText;

        // Update initials in avatar
        const avatar = document.querySelector('.w-8.h-8.rounded-full');
        if (avatar && initials) {
            avatar.textContent = initials;
        }

        showToast('Assignee updated successfully', 'success');
        closeChangeAssigneeModal();
    }

    // Action handlers
    function handleAction(action) {
        const actions = {
            'SCRAP': 'Scrap action initiated',
            'HOLD': 'Product put on hold',
            'PASS': 'Product passed inspection'
        };

        showToast(actions[action] || `${action} action performed`, 'success');
    }

    // Form submission
    document.getElementById('updateProductForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate required fields
        const title = document.getElementById('title').value.trim();
        if (!title) {
            showToast('Please enter a title', 'error');
            return;
        }

        // Simulated success
        showToast('Product updated successfully!', 'success');
    });

    // Initialize with some sample specifications
    document.addEventListener('DOMContentLoaded', function() {
        specifications = [
            { key: 'Color', value: 'Black' },
            { key: 'Weight', value: '1.5kg' },
            { key: 'Dimensions', value: '15x10x5 cm' }
        ];
        renderSpecifications();
        updateSpecificationsInput();
    });

    @if(session('success'))
        showToast('{{ session("success") }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session("error") }}', 'error');
    @endif
</script>
@endpush
