window.ReportFilter = {
    url: '',
    summarySelector: null,
    tableSelector: null,
    paginationSelector: null,
    filterContainer: null,

    debounceDelay: 300,
    debounceTimer: null,

    controller: null,
    cache: new Map(),

    /* ===============================
     * Init
     * =============================== */
    init(options) {
        Object.assign(this, options);
        this.bindEvents();
    },

    /* ===============================
     * Event Binding
     * =============================== */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('#search-btn')) this.debouncedFilter();
            if (e.target.closest('#reset-btn')) this.reset();

            // AJAX Pagination
            const pageLink = e.target.closest('a[href*="page="]');
            if (
                pageLink &&
                this.paginationSelector &&
                pageLink.closest(this.paginationSelector)
            ) {
                e.preventDefault();
                this.filter(new URL(pageLink.href).searchParams.toString());
            }

            // Export
            const exportBtn = e.target.closest('.export-btn');
            if (exportBtn) this.export(exportBtn);
        });

        // Auto-filter on change
        // this.filterContainer.addEventListener('change', (e) => {
        //     if (e.target.matches('[data-filter]')) {
        //         this.debouncedFilter();
        //     }
        // });

        // Pagination Select/Input Change
        document.addEventListener('change', (e) => {
            const select = e.target.closest('.pagination-select');
            if (select) {
                const page = select.value;
                const pageName = select.dataset.pageName || 'page';
                const params = this.buildQueryParams();
                params.set(pageName, page);
                this.filter(params.toString());
            }

            const input = e.target.closest('.pagination-input');
            if (input) {
                this.handlePageInput(input);
            }
        });

        document.addEventListener('keyup', (e) => {
            const input = e.target.closest('.pagination-input');
            if (input && e.key === 'Enter') {
                setTimeout(() => {
                    this.handlePageInput(input);
                }, 2000);
            }
        });
    },

    /**
     * Handle numeric page input
     */
    handlePageInput(input) {
        let page = parseInt(input.value);
        const max = parseInt(input.max);
        const min = parseInt(input.min) || 1;

        if (isNaN(page)) return;

        // Capping
        if (page > max) page = max;
        if (page < min) page = min;
        input.value = page;

        const params = this.buildQueryParams();
        params.set(input.dataset.pageName || 'page', page);
        this.filter(params.toString());
    },

    /* ===============================
     * Debounce
     * =============================== */
    debouncedFilter() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.filter(), this.debounceDelay);
    },

    /* ===============================
     * Loaders
     * =============================== */
    showLoader() {
        document.getElementById('page-loader')?.classList.remove('hidden');

        const btn = document.getElementById('search-btn');
        btn?.setAttribute('disabled', true);
        document.getElementById('search-text')?.classList.add('hidden');
        document.getElementById('search-loader')?.classList.remove('hidden');
    },

    hideLoader() {
        document.getElementById('page-loader')?.classList.add('hidden');

        const btn = document.getElementById('search-btn');
        btn?.removeAttribute('disabled');
        document.getElementById('search-text')?.classList.remove('hidden');
        document.getElementById('search-loader')?.classList.add('hidden');
    },

    showExportLoader() {
        document.getElementById('page-loader')?.classList.remove('hidden');
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
        });
    },

    hideExportLoader() {
        document.getElementById('page-loader')?.classList.add('hidden');
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
        });
    },

    /* ===============================
     * Query Builder (Single Source)
     * =============================== */
    buildQueryParams() {
        const params = new URLSearchParams();

        // this.filterContainer.querySelectorAll('[data-filter]').forEach(el => {
        //     if (
        //         (el.type === 'checkbox' && el.checked) ||
        //         (el.type !== 'checkbox' && el.value)
        //     ) {
        //         params.append(el.name || el.id, el.value);
        //     }
        // });

        // Include all form elements that have name attribute
        this.filterContainer.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
            if (
                (el.type === 'checkbox' && el.checked) ||
                (el.type !== 'checkbox' && el.value)
            ) {
                params.append(el.name, el.value);
            }
        });

        return params;
    },

    /* ===============================
     * AJAX Filter
     * =============================== */
    async filter(extraQuery = '') {
        const query = extraQuery || this.buildQueryParams().toString();

        // Sync URL with query parameters
        const newUrl = window.location.pathname + (query ? '?' + query : '');
        history.replaceState(null, '', newUrl);

        // Show loader for all filter operations
        this.showLoader();

        // Cache hit - add minimum delay so loader is visible
        if (this.cache.has(query)) {
            setTimeout(() => {
                this.render(this.cache.get(query));
                this.hideLoader();
            }, 300);
            return;
        }

        // Abort previous request
        this.controller?.abort();
        this.controller = new AbortController();

        try {
            const res = await fetch(`${this.url}?${query}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controller.signal
            });

            if (!res.ok) throw new Error('Request failed');

            const data = await res.json();
            this.cache.set(query, data);
            this.render(data);

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('ReportFilter:', err);
            }
        } finally {
            this.hideLoader();
        }
    },

    /* ===============================
     * Render
     * =============================== */
    render(data) {
        requestAnimationFrame(() => {
            // Summary Update
            if (this.summarySelector && data.summary) {
                const summaryEl = document.querySelector(this.summarySelector);
                if (summaryEl) {
                    Object.entries(data.summary).forEach(([key, value]) => {
                        const el = summaryEl.querySelector(`[data-summary-key="${key}"]`);
                        if (el) {
                            el.dispatchEvent(new CustomEvent('update-value', {
                                detail: { value: value }
                            }));
                        }
                        if (key === 'status' && el) {
                            el.innerHTML = value + (value === 'Balanced' ? ' ✅' : ' ❌');
                        }
                    });
                }
            }

            // Pagination Update
            if (this.paginationSelector) {
                const pagEl = document.querySelector(this.paginationSelector);
                if (pagEl) {
                    pagEl.innerHTML = (data.pagination !== undefined && data.pagination !== null) ? data.pagination : '';
                }
            }

            // Table Update
            if (this.tableSelector) {
                const tableEl = document.querySelector(this.tableSelector);
                if (tableEl && data.html !== undefined) {
                    tableEl.innerHTML = data.html;
                    // Dispatch custom event to notify table has been updated
                    document.dispatchEvent(new CustomEvent('tableUpdated'));
                }
            }
        });
    },

    /* ===============================
     * Reset Filters
     * =============================== */
    reset() {
        // this.filterContainer.querySelectorAll('[data-filter]').forEach(el => {
        //     el.type === 'checkbox' ? el.checked = false : el.value = '';

        //     if (el.classList.contains('select2')) {
        //         $(el).val(null).trigger('change');
        //     }
        // });

        // this.cache.clear();
        // this.filter();

        // Redirect to clean URL without any query parameters
        window.location.href = this.url;
    },

    /* ===============================
     * Export (No Pagination)
     * =============================== */
    export(btn) {
        const exportUrl = btn.dataset.exportUrl;
        const exportType = btn.dataset.exportType;
        if (!exportUrl || !exportType) return;

        document.getElementById('exportDropdown')?.classList.add('hidden');

        this.showExportLoader();

        const params = this.buildQueryParams();
        params.delete('per_page'); // never paginate exports
        params.append('format', exportType); // add export format

        window.open(`${exportUrl}?${params.toString()}`, '_blank');

        // Browser handles download → hide loader safely
        setTimeout(() => this.hideExportLoader(), 1500);
    }
};

/**
 * Alpine CountUp Component
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('countUp', (initialValue) => ({
        currentValue: 0,
        targetValue: 0,
        displayValue: '0.00',
        duration: 1000,
        startTime: null,

        init() {
            const raw = initialValue.toString().replace(/,/g, '');
            this.targetValue = parseFloat(raw) || 0;
            this.currentValue = 0;
            this.animate();
        },

        update(newValue) {
            const raw = newValue.toString().replace(/,/g, '');
            const parsed = parseFloat(raw) || 0;
            if (parsed === this.targetValue) return;

            this.currentValue = this.targetValue;
            this.targetValue = parsed;
            this.startTime = null;
            this.animate();
        },

        animate() {
            const self = this;
            const startValue = this.currentValue;
            const change = this.targetValue - startValue;

            function step(timestamp) {
                if (!self.startTime) self.startTime = timestamp;
                const progress = Math.min((timestamp - self.startTime) / self.duration, 1);

                // Easing function: easeOutQuart
                const ease = 1 - Math.pow(1 - progress, 4);
                const current = startValue + change * ease;

                self.displayValue = new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(current);

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            }

            window.requestAnimationFrame(step);
        }
    }));
});
