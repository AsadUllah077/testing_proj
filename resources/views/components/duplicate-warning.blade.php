{{--
    Duplicate Detection Warning Component
    Include in @push('scripts') on bill/sale/journal-entry create pages.

    Required: pass $duplicateCheckType ('bill', 'sale', or 'journal_entry')
    and $duplicateCheckUrl (route URL).

    The JS watches for vendor/customer + amount + date changes, fires a
    debounced AJAX call, and shows a warning banner if near-duplicates found.
--}}
<div id="duplicate-warning" class="hidden mb-4 bg-amber-50 border border-amber-300 rounded-[5px] p-4">
    <div class="flex items-start gap-3">
        <div class="bg-amber-100 rounded-full p-1.5 mt-0.5 shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-amber-800">Possible Duplicate Detected</p>
            <p class="text-xs text-amber-700 mt-0.5">The following existing records closely match what you're entering. Please verify this is not a duplicate before saving.</p>
            <div id="duplicate-list" class="mt-2 space-y-1"></div>
        </div>
        <button type="button" onclick="document.getElementById('duplicate-warning').classList.add('hidden')"
            class="text-amber-400 hover:text-amber-600 text-lg leading-none">&times;</button>
    </div>
</div>

<script>
(function() {
    const CHECK_URL  = @json($duplicateCheckUrl);
    const CHECK_TYPE = @json($duplicateCheckType);
    const DEBOUNCE_MS = 800;

    const warningDiv = document.getElementById('duplicate-warning');
    const listDiv    = document.getElementById('duplicate-list');
    
    if (!warningDiv || !listDiv) {
        console.error('Duplicate warning component: required DOM elements not found.');
        return;
    }

    let debounceTimer = null;
    let isChecking = false;

    function scheduleCheck() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkDuplicates, DEBOUNCE_MS);
    }

    function checkDuplicates() {
        if (isChecking) return;

        const params = new URLSearchParams();
        const entityField = CHECK_TYPE === 'sale' ? 'customer_id' : 'vendor_id';

        if (CHECK_TYPE === 'bill' || CHECK_TYPE === 'sale') {
            // Find input by name (works for both select and hidden input)
            const entityInput = document.querySelector(`[name="${entityField}"]`);
            const amountInput = document.getElementById('grand-total');
            const dateInput   = document.querySelector('input[name="date"]');
            const invInput    = document.querySelector('input[name="invoice_no"]');

            let entityId = '';
            if (entityInput) {
                // Use value directly (works for hidden inputs and standard selects)
                entityId = entityInput.value;
            }

            params.set(entityField, entityId);
            params.set('amount', amountInput ? amountInput.innerText.replace(/,/g, '') : '0');
            params.set('date', dateInput ? dateInput.value : '');
            if (invInput) params.set('invoice_no', invInput.value);

            console.log(`Duplicate Check (${CHECK_TYPE}):`, Object.fromEntries(params));
        } else if (CHECK_TYPE === 'journal_entry') {
            let totalDebit = 0;
            document.querySelectorAll('.row-debit').forEach(el => {
                totalDebit += parseFloat(el.value) || 0;
            });
            params.set('total_debit', totalDebit);

            const narrationInput = document.querySelector('input[name="narration"], textarea[name="narration"]');
            params.set('narration', narrationInput ? narrationInput.value : '');
            
            const dateInput = document.querySelector('input[name="date"]');
            params.set('date', dateInput ? dateInput.value : '');

            console.log(`Duplicate Check (${CHECK_TYPE}):`, Object.fromEntries(params));
        }

        params.set('type', CHECK_TYPE);

        // Validation: require entity and amount > 0 (except journal entries)
        if (CHECK_TYPE !== 'journal_entry') {
            const amountValue = parseFloat(params.get('amount')) || 0;
            if (!params.get(entityField) || amountValue <= 0) {
                warningDiv.classList.add('hidden');
                return;
            }
        } else {
            const debitValue = parseFloat(params.get('total_debit')) || 0;
            if (debitValue <= 0) {
                warningDiv.classList.add('hidden');
                return;
            }
        }

        isChecking = true;
        fetch(`${CHECK_URL}?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                renderWarning(data.duplicates || []);
            })
            .catch(err => {
                console.error('Duplicate check failed:', err);
                warningDiv.classList.add('hidden');
            })
            .finally(() => { isChecking = false; });
    }

    function renderWarning(duplicates) {
        if (duplicates.length === 0) {
            warningDiv.classList.add('hidden');
            return;
        }

        listDiv.innerHTML = '';
        duplicates.forEach(d => {
            const confColors = d.confidence >= 80 ? 'bg-red-100 text-red-700' :
                             d.confidence >= 50 ? 'bg-amber-100 text-amber-800' :
                             'bg-gray-100 text-gray-600';

            const entity = d.vendor || d.customer || '';
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between bg-white border border-amber-200 rounded px-3 py-2 text-xs';
            row.innerHTML = `
                <div>
                    <span class="font-bold text-gray-800">${d.ref}</span>
                    ${entity ? `<span class="text-gray-500 ml-1">— ${entity}</span>` : ''}
                    <span class="text-gray-400 ml-2">${d.date}</span>
                    <span class="font-semibold text-gray-700 ml-2">${parseFloat(d.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-500" title="${d.reasons.join(' | ')}">${d.reasons[0]}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${confColors}">${d.confidence}%</span>
                    <a href="${d.url}" target="_blank" class="text-amber-700 hover:text-amber-900 underline font-medium">View Result</a>
                </div>
            `;
            listDiv.appendChild(row);
        });
        warningDiv.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Amount/Description change: Debounce a bit
        const textInputs = document.querySelectorAll('input[name="invoice_no"], input[name="narration"], textarea[name="narration"]');
        textInputs.forEach(el => el.addEventListener('blur', scheduleCheck));

        // Selection change
        const entityField = CHECK_TYPE === 'sale' ? 'customer_id' : 'vendor_id';
        const entityInput = document.querySelector(`[name="${entityField}"]`);
        if (entityInput) {
            entityInput.addEventListener('change', scheduleCheck);
            // If it's a select2 managed field
            if (typeof $ !== 'undefined' && $(entityInput).hasClass('select2')) {
                $(entityInput).on('select2:select change', scheduleCheck);
            }
        }

        // Date change
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput) dateInput.addEventListener('change', scheduleCheck);

        // Grand total observer
        const grandTotal = document.getElementById('grand-total');
        if (grandTotal) {
            const observer = new MutationObserver(scheduleCheck);
            observer.observe(grandTotal, { childList: true, characterData: true, subtree: true });
        }

        // Journal entry specific delegation for dynamic rows
        if (CHECK_TYPE === 'journal_entry') {
            document.addEventListener('input', (e) => {
                if (e.target.classList.contains('row-debit') || (e.target.name && e.target.name.endsWith('[debit]'))) {
                    scheduleCheck();
                }
            });
        }
    });
})();
</script>
