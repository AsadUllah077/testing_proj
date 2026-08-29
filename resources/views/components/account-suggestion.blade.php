{{--
    Account Suggestion Component
    Include this in @push('scripts') on any page with account_id Select2 dropdowns.
    The JS auto-discovers description inputs and account selects within table rows,
    and fires suggestions on description blur and vendor/customer change.

    Usage: @include('components.account-suggestion')
--}}
<script>
(function () {
    const SUGGEST_URL = '{{ route("account.suggest-account") }}';
    const DEBOUNCE_MS = 400;
    let debounceTimers = {};

    /**
     * Attach suggestion behaviour to a single table row.
     * Call this after each addNewRow() in the page's own JS.
     */
    window.attachAccountSuggestion = function(row) {
        const descInput = row.querySelector('input[name$="[description]"]');
        const acctSelect = row.querySelector('select[name$="[account_id]"]');
        if (!descInput || !acctSelect) return;

        // Create suggestion container (if not already present)
        let container = row.querySelector('.acct-suggest-pills');
        if (!container) {
            container = document.createElement('div');
            container.className = 'acct-suggest-pills flex flex-wrap gap-1 mt-1';
            acctSelect.closest('td').appendChild(container);
        }

        descInput.addEventListener('blur', function () {
            const rowId = row.dataset.rowIndex || Math.random();
            clearTimeout(debounceTimers[rowId]);
            debounceTimers[rowId] = setTimeout(() => fetchSuggestions(row, container, acctSelect), DEBOUNCE_MS);
        });
    };

    /**
     * Re-trigger suggestions for all visible rows (call on vendor/customer change).
     */
    window.refreshAccountSuggestions = function() {
        document.querySelectorAll('#invoice-table-body tr, #journal-table-body tr').forEach(row => {
            const container = row.querySelector('.acct-suggest-pills');
            const acctSelect = row.querySelector('select[name$="[account_id]"]');
            if (container && acctSelect) {
                fetchSuggestions(row, container, acctSelect);
            }
        });
    };

    function fetchSuggestions(row, container, acctSelect) {
        const descInput = row.querySelector('input[name$="[description]"]');
        const description = descInput ? descInput.value.trim() : '';

        // Get vendor_id or customer_id from page-level selects (if they exist)
        const vendorSelect = document.querySelector('select[name="vendor_id"]');
        const customerSelect = document.querySelector('select[name="customer_id"]');
        const vendorId = vendorSelect ? ($(vendorSelect).val() || '') : '';
        const customerId = customerSelect ? ($(customerSelect).val() || '') : '';

        if (!description && !vendorId && !customerId) {
            container.innerHTML = '';
            return;
        }

        const params = new URLSearchParams();
        if (description) params.set('description', description);
        if (vendorId) params.set('vendor_id', vendorId);
        if (customerId) params.set('customer_id', customerId);

        fetch(`${SUGGEST_URL}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => renderPills(data.suggestions || [], container, acctSelect))
        .catch(() => { container.innerHTML = ''; });
    }

    function renderPills(suggestions, container, acctSelect) {
        container.innerHTML = '';
        if (!suggestions.length) return;

        const label = document.createElement('span');
        label.className = 'text-[10px] text-gray-400 font-bold uppercase w-full';
        label.textContent = 'Suggested:';
        container.appendChild(label);

        suggestions.forEach(s => {
            const pill = document.createElement('button');
            pill.type = 'button';

            const colors = {
                high:   'bg-green-100 text-green-800 border-green-200 hover:bg-green-200',
                medium: 'bg-blue-100 text-blue-800 border-blue-200 hover:bg-blue-200',
                low:    'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200',
            };

            pill.className = `text-[11px] font-medium px-2 py-0.5 rounded-full border cursor-pointer transition-colors ${colors[s.confidence] || colors.low}`;
            pill.textContent = `${s.account_number} - ${s.account_name}`;
            pill.title = s.reason;

            pill.addEventListener('click', function () {
                $(acctSelect).val(s.account_id).trigger('change');

                // Highlight briefly
                acctSelect.closest('td').classList.add('ring-2', 'ring-blue-300');
                setTimeout(() => acctSelect.closest('td').classList.remove('ring-2', 'ring-blue-300'), 2000);

                // Clear pills after selection
                container.innerHTML = '';
            });

            container.appendChild(pill);
        });
    }

    // Auto-attach to existing rows on page load
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#invoice-table-body tr, #journal-table-body tr').forEach(row => {
            window.attachAccountSuggestion(row);
        });

        // Listen for vendor/customer select changes to refresh suggestions
        const vendorSelect = document.querySelector('select[name="vendor_id"]');
        const customerSelect = document.querySelector('select[name="customer_id"]');

        if (vendorSelect) {
            $(vendorSelect).on('select2:select select2:unselect change', function () {
                window.refreshAccountSuggestions();
            });
        }
        if (customerSelect) {
            $(customerSelect).on('select2:select select2:unselect change', function () {
                window.refreshAccountSuggestions();
            });
        }
    });
})();
</script>
