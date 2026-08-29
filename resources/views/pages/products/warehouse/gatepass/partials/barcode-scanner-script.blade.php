// -------------------------------------------------------
// Barcode scanner product selection
// -------------------------------------------------------
const gatePassBarcodeInput = document.getElementById('gatePassBarcodeInput');
const addScannedProductBtn = document.getElementById('addScannedProductBtn');
const gatePassBarcodeStatus = document.getElementById('gatePassBarcodeStatus');
const gatePassBarcodeExcludeId = @json(isset($gatePass) ? $gatePass->id : null);

addScannedProductBtn.addEventListener('click', addScannedProduct);
gatePassBarcodeInput.addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addScannedProduct();
    }
});

function normalizeGatePassBarcode(rawValue) {
    let value = String(rawValue || '').trim();

    if (!value) return '';

    try {
        const scannedUrl = new URL(value);
        const segments = scannedUrl.pathname.split('/').filter(Boolean);
        value = segments.length > 0 ? segments[segments.length - 1] : '';
    } catch (error) {
        value = value.split(/[?#]/, 1)[0];
        const segments = value.split('/').filter(Boolean);
        value = segments.length > 0 ? segments[segments.length - 1] : value;
    }

    try {
        return decodeURIComponent(value).trim();
    } catch (error) {
        return value.trim();
    }
}

function showGatePassBarcodeStatus(type, message) {
    const styles = {
        success: 'border border-emerald-200 bg-emerald-50 text-emerald-700',
        error: 'border border-red-200 bg-red-50 text-red-700',
        info: 'border border-blue-200 bg-blue-50 text-blue-700',
    };

    gatePassBarcodeStatus.className = `mt-2 rounded px-3 py-2 text-xs ${styles[type] || styles.info}`;
    gatePassBarcodeStatus.textContent = message;
    gatePassBarcodeStatus.classList.remove('hidden');
}

async function addScannedProduct() {
    const uniqueId = normalizeGatePassBarcode(gatePassBarcodeInput.value);

    if (uniqueId.length < 2) {
        showGatePassBarcodeStatus('error', 'Scan a valid barcode or enter a product ID.');
        gatePassBarcodeInput.focus();
        return;
    }

    const alreadySelected = selectedProducts.find(
        product => String(product.unique_id || '').toLowerCase() === uniqueId.toLowerCase()
    );

    if (alreadySelected) {
        showGatePassBarcodeStatus('info', `${uniqueId} is already in the selected products list.`);
        gatePassBarcodeInput.select();
        return;
    }

    addScannedProductBtn.disabled = true;
    addScannedProductBtn.textContent = 'Checking...';
    showGatePassBarcodeStatus('info', `Checking ${uniqueId}...`);

    try {
        const searchUrl = new URL(@json(route('warehouse.gate-pass.search.products')), window.location.origin);
        searchUrl.searchParams.set('search', uniqueId);
        if (gatePassBarcodeExcludeId) {
            searchUrl.searchParams.set('exclude_gate_pass_id', gatePassBarcodeExcludeId);
        }

        const response = await fetch(searchUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? @json(csrf_token()),
            },
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to check this barcode.');
        }

        const product = (data.products || []).find(
            item => String(item.unique_id || '').toLowerCase() === uniqueId.toLowerCase()
        );

        if (!product) {
            showGatePassBarcodeStatus(
                'error',
                `No available product found for ${uniqueId}. It may already be used or included in another active gate pass.`
            );
            gatePassBarcodeInput.select();
            return;
        }

        addProduct({
            ...product,
            quantity: product.manual_status == 1 ? 1 : undefined,
        });
        showGatePassBarcodeStatus('success', `${product.unique_id} added successfully.`);
        gatePassBarcodeInput.value = '';
    } catch (error) {
        console.error('Barcode product lookup failed:', error);
        showGatePassBarcodeStatus('error', error.message || 'Failed to add the scanned product. Please try again.');
        gatePassBarcodeInput.select();
    } finally {
        addScannedProductBtn.disabled = false;
        addScannedProductBtn.innerHTML = `
            <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Scanned Product
        `;
        gatePassBarcodeInput.focus();
    }
}
