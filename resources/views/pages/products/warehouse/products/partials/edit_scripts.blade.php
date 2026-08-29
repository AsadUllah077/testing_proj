<script>
(function() {
    const productData = @json($product);
    let specifications = (Array.isArray(productData.specifications) ? productData.specifications : [])
        .map(specification => {
            if (specification && typeof specification === 'object') {
                return {
                    key: String(specification.key ?? specification.name ?? ''),
                    value: String(specification.value ?? '')
                };
            }

            return {
                key: String(specification ?? ''),
                value: ''
            };
        });

    const showToast = function (message, type = 'success') {
        if (typeof window.showToast === 'function' && window.showToast !== showToast) {
            window.showToast(message, type);
            return;
        }
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        if (!toast || !content) return;
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
    };

    const hideToast = function () {
        const toast = document.getElementById('toast');
        if (toast) toast.classList.add('hidden');
    };
    window.hideToast = hideToast;

    const toggleSection = function (sectionId) {
        const section = document.getElementById(sectionId);
        if (section) section.classList.toggle('hidden');
    };
    window.toggleSection = toggleSection;

    // UPDATED: Specification functions - Now with separate boxes for key and value
    const addSpecification = function () {
        const keyInput = document.getElementById('specKeyInput');
        const valueInput = document.getElementById('specValueInput');
        const key = keyInput.value.trim();
        const value = valueInput.value.trim();

        if (!key || !value) {
            showToast('Please enter both key and value', 'error');
            return;
        }

        specifications.push({
            key,
            value
        });
        renderSpecifications();
        updateSpecificationsInput();

        // Clear both inputs after adding
        keyInput.value = '';
        valueInput.value = '';
        keyInput.focus();
        showToast('Specification added successfully', 'success');
    };
    window.addSpecification = addSpecification;

    const escapeAttribute = function (value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');
    };

    const updateSpecification = function (index, field, value) {
        if (!specifications[index] || !['key', 'value'].includes(field)) return;
        specifications[index][field] = value;
        updateSpecificationsInput();
    };
    window.updateSpecification = updateSpecification;

    const renderSpecifications = function () {
        const container = document.getElementById('specificationsList');
        if (!container) return;

        container.innerHTML = '';
        specifications.forEach((spec, index) => {
            const div = document.createElement('div');
            div.className = 'grid grid-cols-1 md:grid-cols-2 gap-3 items-center';
            div.innerHTML = `
            <div class="relative group">
                <input type="text"
                       value="${escapeAttribute(spec.key)}"
                       oninput="updateSpecification(${index}, 'key', this.value)"
                       aria-label="Specification ${index + 1} key"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                       placeholder="Specification key">
            </div>
            <div class="relative group flex gap-2">
                <input type="text"
                       value="${escapeAttribute(spec.value)}"
                       oninput="updateSpecification(${index}, 'value', this.value)"
                       aria-label="Specification ${index + 1} value"
                       class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                       placeholder="Specification value">
                <button type="button" onclick="removeSpecification(${index})"
                        class="px-3 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors shadow-sm"
                        title="Remove specification" aria-label="Remove specification ${index + 1}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `;
            container.appendChild(div);
        });
    };

    const removeSpecification = function (index) {
        specifications.splice(index, 1);
        renderSpecifications();
        updateSpecificationsInput();
        showToast('Specification removed successfully', 'success');
    };
    window.removeSpecification = removeSpecification;

    const updateSpecificationsInput = function () {
        const input = document.getElementById('specificationsInput');
        if (input) input.value = JSON.stringify(specifications);
    };

    let visualChecklistCounter = 0;

    const loadVisualChecklist = function () {
        const visualInspection = productData.visual_inspections?.[0];
        if (!visualInspection?.checklist_items) return;

        const items = Array.isArray(visualInspection.checklist_items)
            ? visualInspection.checklist_items
            : Object.keys(visualInspection.checklist_items);

        items.forEach(item => addExistingVisualChecklistItem(item, true));
    };

    const addExistingVisualChecklistItem = function (itemText, isChecked = true) {
        const container = document.getElementById('visualDynamicChecklist');
        if (!container) return;
        const itemId = 'visual-checklist-item-' + visualChecklistCounter++;
        const displayText = typeof itemText === 'object' ? (itemText.value || itemText.name || JSON.stringify(
            itemText)) : itemText;
        const itemValue = typeof itemText === 'object' ? (itemText.value || itemText.name || itemText) : itemText;
        const checklistItem = document.createElement('div');
        checklistItem.className =
            'flex items-center justify-between group bg-white p-2 rounded-lg border border-gray-200 hover:border-[#0078C8] transition-colors';
        checklistItem.innerHTML = `
        <div class="flex items-center flex-1">
            <input type="checkbox" id="${itemId}" name="visual_checklist[]" value="${itemValue}"
                   class="h-5 w-5 text-[#0078C8] rounded focus:ring-[#0078C8] border-gray-300 cursor-pointer"
                   ${isChecked ? 'checked' : ''}>
            <label for="${itemId}" class="ml-3 text-sm text-gray-700 cursor-pointer font-medium">${displayText}</label>
        </div>
        <button type="button" onclick="removeVisualChecklistItem(this)"
                class="ml-2 text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-all p-1 rounded-full hover:bg-red-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    `;
        container.appendChild(checklistItem);
    };

    const addVisualChecklistItem = function () {
        const input = document.getElementById('visualNewChecklistItem');
        if (!input) return;
        const itemText = input.value.trim();
        if (!itemText) {
            showToast('Please enter an inspection point', 'error');
            return;
        }
        addExistingVisualChecklistItem(itemText, true);
        input.value = '';
        input.focus();
        showToast('Visual checklist item added successfully', 'success');
    };
    window.addVisualChecklistItem = addVisualChecklistItem;

    const removeVisualChecklistItem = function (button) {
        const checklistItem = button.closest('.flex.items-center.justify-between');
        if (checklistItem) {
            checklistItem.remove();
            showToast('Visual checklist item removed', 'success');
        }
    };
    window.removeVisualChecklistItem = removeVisualChecklistItem;

    let functionalTestFieldCounter = 0;

    const loadFunctionalTests = function () {
        const functionalTesting = productData.functional_testings?.[0];
        if (functionalTesting?.custom_tests) {
            Object.values(functionalTesting.custom_tests).forEach(test => {
                addExistingFunctionalTestField(test.name, test.notes);
            });
        }
    };

    const addExistingFunctionalTestField = function (testName, testNotes) {
        const container = document.getElementById('functionalDynamicTestFields');
        if (!container) return;
        const sanitizedTestName = testName.toLowerCase().replace(/[^a-z0-9]/g, '_');
        functionalTestFieldCounter++;
        const testField = document.createElement('div');
        testField.className =
            'p-4 border border-gray-200 rounded-lg bg-white group hover:border-[#0078C8] transition-colors';
        testField.innerHTML = `
        <div class="flex justify-between items-start">
            <div>
                <h4 class="text-sm font-semibold text-gray-800">${testName}</h4>
                <p class="text-xs text-gray-500 mt-1">${testNotes || 'No notes'}</p>
            </div>
            <button type="button" onclick="removeFunctionalTestField(this)"
                    class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
        <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][name]" value="${testName}">
        <input type="hidden" name="functional_custom_tests[${sanitizedTestName}][notes]" value="${testNotes || ''}">
    `;
        container.appendChild(testField);
    };

    const addFunctionalTestField = function () {
        const testNameInput = document.getElementById('functionalNewTestName');
        const testNotesInput = document.getElementById('functionalNewTestNotes');
        if (!testNameInput || !testNotesInput) return;
        const testName = testNameInput.value.trim();
        const testNotes = testNotesInput.value.trim();
        if (!testName) {
            showToast('Please enter a test name', 'error');
            testNameInput.focus();
            return;
        }
        addExistingFunctionalTestField(testName, testNotes);
        testNameInput.value = '';
        testNotesInput.value = '';
        testNameInput.focus();
        showToast('Functional test added successfully', 'success');
    };
    window.addFunctionalTestField = addFunctionalTestField;

    const removeFunctionalTestField = function (button) {
        const testField = button.closest('.p-4.border.border-gray-200.rounded-lg');
        if (testField) {
            testField.remove();
            showToast('Functional test removed', 'success');
        }
    };
    window.removeFunctionalTestField = removeFunctionalTestField;

    // Wrap DOMContentLoaded function so it runs immediately if document is already loaded (for AJAX)
    function initEditPage() {
        renderSpecifications();
        updateSpecificationsInput();
        loadVisualChecklist();
        loadFunctionalTests();

        const visualInput = document.getElementById('visualNewChecklistItem');
        if (visualInput) {
            visualInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addVisualChecklistItem();
                }
            });
        }

        const testNameInput = document.getElementById('functionalNewTestName');
        const testNotesInput = document.getElementById('functionalNewTestNotes');
        if (testNameInput) {
            testNameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('functionalNewTestNotes')?.focus();
                }
            });
        }
        if (testNotesInput) {
            testNotesInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addFunctionalTestField();
                }
            });
        }

        const specKeyInput = document.getElementById('specKeyInput');
        if (specKeyInput) {
            specKeyInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('specValueInput')?.focus();
                }
            });
        }

        // Add Enter key support for specification value input
        const specValueInput = document.getElementById('specValueInput');
        if (specValueInput) {
            specValueInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addSpecification();
                }
            });
        }

        const editForm = document.getElementById('formSubmit');
        if (editForm) {
            editForm.addEventListener('submit', updateSpecificationsInput);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditPage);
    } else {
        initEditPage();
    }

    const openChangeAssigneeModal = function () {
        const modal = document.getElementById('changeAssigneeModal');
        if (modal) modal.classList.remove('hidden');
    };
    window.openChangeAssigneeModal = openChangeAssigneeModal;

    const closeChangeAssigneeModal = function () {
        const modal = document.getElementById('changeAssigneeModal');
        if (modal) modal.classList.add('hidden');
    };
    window.closeChangeAssigneeModal = closeChangeAssigneeModal;

    const updateAssignee = function () {
        const select = document.getElementById('assigneeSelect');
        if (!select) return;
        const selectedValue = select.value;
        if (!selectedValue) {
            showToast('Please select a personnel', 'error');
            return;
        }
        const selectedOption = select.options[select.selectedIndex];
        const selectedText = selectedOption.text.split(' (')[0];
        const initials = selectedOption.dataset.initials;
        const assignedPersonnel = document.getElementById('assignedPersonnel');
        const assignedToInput = document.getElementById('assignedToInput');
        if (assignedPersonnel) assignedPersonnel.textContent = selectedText;
        if (assignedToInput) assignedToInput.value = selectedValue;
        const avatar = document.querySelector('.w-10.h-10.rounded-full');
        if (avatar && initials) avatar.textContent = initials;
        showToast('Assignee updated successfully', 'success');
        closeChangeAssigneeModal();
    };
    window.updateAssignee = updateAssignee;

    const handleAction = function (action, productId) {
        const token = document.querySelector('input[name="_token"]');
        if (!token) {
            showToast('CSRF token not found', 'error');
            return;
        }
        fetch(`/warehouse/products/${productId}/action`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token.value
                },
                body: JSON.stringify({
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`${action} action performed successfully`, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || `Failed to perform ${action} action`, 'error');
                }
            })
            .catch(() => showToast(`Error performing ${action} action`, 'error'));
    };
    window.handleAction = handleAction;

    @if (session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif

    @if (session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
})();
</script>
