<div class="mb-4 rounded-md border border-cyan-200 bg-cyan-50 p-3">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <div class="mb-1 flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded bg-cyan-100 text-cyan-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8v8m3-8v8m4-8v8m3-8v8" />
                    </svg>
                </span>
                <div>
                    <label for="gatePassBarcodeInput" class="block text-xs font-semibold text-gray-800">
                        Add Product by Barcode
                    </label>
                    <p class="text-[11px] text-gray-500">
                        Scan the printed QR/barcode or enter its product ID. Scanner Enter will add it automatically.
                    </p>
                </div>
            </div>

            <input type="text" id="gatePassBarcodeInput" autocomplete="off" autocapitalize="characters"
                spellcheck="false" placeholder="Ready to scan..."
                class="w-full rounded border border-cyan-300 bg-white px-3 py-2 font-mono text-sm text-gray-800 outline-none placeholder:font-sans placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-cyan-500">
        </div>

        <button type="button" id="addScannedProductBtn"
            class="inline-flex items-center justify-center rounded bg-cyan-700 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-60">
            <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            Add Scanned Product
        </button>
    </div>

    <div id="gatePassBarcodeStatus" class="mt-2 hidden rounded px-3 py-2 text-xs" role="status"
        aria-live="polite"></div>
</div>
