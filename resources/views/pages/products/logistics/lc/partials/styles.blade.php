@once
    @push('styles')
        <style>
            .lc-page {
                color: #0f172a;
            }

            .lc-shell {
                max-width: 100%;
            }

            .lc-list-shell {
                display: flex;
                min-height: calc(100svh - 92px);
                flex-direction: column;
            }

            .lc-command-bar,
            .lc-panel,
            .lc-stat-card {
                box-shadow: 0 18px 40px -32px rgba(15, 23, 42, 0.35);
            }

            .lc-command-bar {
                background:
                    linear-gradient(135deg, rgba(0, 120, 200, 0.08), rgba(16, 185, 129, 0.05) 38%, rgba(255, 255, 255, 0.96) 70%),
                    #ffffff;
            }

            .lc-action-btn {
                min-height: 42px;
                white-space: nowrap;
            }

            .lc-stat-card {
                position: relative;
                overflow: hidden;
            }

            .lc-stat-card::after {
                content: "";
                position: absolute;
                inset: auto 16px 0 16px;
                height: 3px;
                border-radius: 999px 999px 0 0;
                background: var(--lc-accent, #0078c8);
                opacity: 0.85;
            }

            .lc-filter-panel #filter-form {
                gap: 14px;
            }

            .lc-table-panel {
                display: flex;
                min-height: max(520px, calc(100svh - 340px));
                flex: 1 1 auto;
                flex-direction: column;
            }

            .lc-table-panel #custom_table {
                display: flex;
                min-height: 0;
                flex: 1 1 auto;
                flex-direction: column;
            }

            .lc-table-scroll {
                min-height: 360px;
                flex: 1 1 auto;
                overflow: auto;
            }

            .lc-table-scroll thead th {
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .lc-table-footer {
                flex-shrink: 0;
            }

            .lc-filter-panel label,
            .lc-form-shell label {
                color: #475569;
                font-size: 0.75rem;
                font-weight: 700;
            }

            .lc-filter-panel select,
            .lc-filter-panel input,
            .lc-form-shell input:not([type="checkbox"]),
            .lc-form-shell select,
            .lc-form-shell textarea {
                border-color: #dbe3ee !important;
                border-radius: 0.75rem !important;
                color: #0f172a !important;
                font-size: 0.875rem !important;
                box-shadow: 0 1px 1px rgba(15, 23, 42, 0.02);
            }

            .lc-filter-panel select:focus,
            .lc-filter-panel input:focus,
            .lc-form-shell input:focus,
            .lc-form-shell select:focus,
            .lc-form-shell textarea:focus {
                border-color: #0078c8 !important;
                box-shadow: 0 0 0 4px rgba(0, 120, 200, 0.12) !important;
            }

            .lc-filter-panel #search-btn,
            .lc-filter-panel #reset-btn {
                min-height: 42px;
                border-radius: 0.75rem;
                box-shadow: none;
            }

            .lc-form-shell > .bg-white {
                border-color: #dbe3ee;
                border-radius: 1rem;
                box-shadow: 0 18px 36px -34px rgba(15, 23, 42, 0.45);
            }

            .lc-form-shell > .bg-white > div:first-child {
                background: linear-gradient(90deg, #f8fafc, #ffffff);
                border-color: #e6edf5;
                padding: 1rem 1.25rem;
            }

            .lc-form-shell > .bg-white > div:first-child .w-1 {
                width: 0.5rem;
                height: 0.5rem;
                background: #0078c8;
            }

            .lc-form-shell > .bg-white > div:first-child h2 {
                font-size: 0.95rem;
                font-weight: 700;
            }

            .lc-form-shell input:not([type="checkbox"]),
            .lc-form-shell select {
                min-height: 44px;
            }

            .lc-document-option {
                min-height: 62px;
            }

            .lc-document-option input {
                accent-color: #0078c8;
            }

            details.lc-row-menu summary {
                list-style: none;
            }

            details.lc-row-menu summary::-webkit-details-marker {
                display: none;
            }

            details.lc-row-menu[open] summary {
                border-color: #0078c8;
                color: #0078c8;
                background: rgba(0, 120, 200, 0.06);
            }

            .lc-file-input::file-selector-button {
                border: 0;
                border-radius: 0.75rem;
                background: #0078c8;
                color: #ffffff;
                font-size: 0.75rem;
                font-weight: 700;
                padding: 0.625rem 0.875rem;
                margin-right: 0.875rem;
                cursor: pointer;
            }

            .lc-file-input::file-selector-button:hover {
                background: #006bb0;
            }

            @media (max-width: 640px) {
                .lc-list-shell {
                    min-height: calc(100svh - 76px);
                }

                .lc-table-panel {
                    min-height: max(560px, calc(100svh - 220px));
                }

                .lc-table-scroll {
                    min-height: 420px;
                }

                .lc-command-actions {
                    width: 100%;
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .lc-command-actions > * {
                    width: 100%;
                }

                .lc-command-actions .lc-primary-action {
                    grid-column: 1 / -1;
                }
            }
        </style>
    @endpush
@endonce
