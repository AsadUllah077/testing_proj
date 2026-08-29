{{-- Shared Xero-style report CSS — included by all accounting reports --}}
<style>
    /* ── Report Header (centered like Xero) ── */
    .xero-report-header {
        border-bottom: 1px solid #e8e8e8;
        padding-bottom: 16px;
        margin-bottom: 24px;
        text-align: center;
        position: relative;
    }

    .xero-report-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }

    .xero-report-subtitle {
        color: #888;
        font-size: 13px;
        margin-top: 2px;
    }

    .xero-company-name {
        font-weight: 500;
        color: #555;
        font-size: 13px;
    }

    /* ── Summary Row (minimal, centered) ── */
    .xero-summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2rem;
        padding: 1rem 0;
        margin-bottom: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .xero-summary-item {
        text-align: center;
    }

    .xero-summary-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #999;
        letter-spacing: 0.3px;
    }

    .xero-summary-value {
        font-size: 1.125rem;
        font-weight: 600;
        color: #333;
        margin-top: 2px;
        font-variant-numeric: tabular-nums;
    }

    /* ── Expand/Collapse buttons ── */
    .xero-toggle-btn {
        padding: 4px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 12px;
        color: #666;
        background: transparent;
        cursor: pointer;
        transition: all 0.15s;
    }

    .xero-toggle-btn:hover {
        border-color: #999;
        color: #333;
    }

    /* ── Table Base ── */
    .xero-table {
        width: 100%;
        border-collapse: collapse;
    }

    /* for border in all reports except balance sheet */
    .xero-table tr {
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.15s ease;
    }

    .xero-table tbody tr:not(.xero-section-header):not(.xero-sub-label):hover {
        background-color: #edf6ff;
    }

    /* for border in all reports except balance sheet */
    .xero-table th {
        padding: 10px 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 1px solid #ddd;
        background: transparent;
    }

    .xero-table th.text-right {
        text-align: right;
    }

    .xero-table td {
        padding: 7px 16px;
        border-bottom: none;
        font-size: 13px;
        color: #333;
        line-height: 1.6;
    }

    .xero-table td.text-right {
        text-align: right;
    }

    .xero-table td.amount {
        text-align: right;
        font-weight: 400;
        white-space: nowrap;
    }

    /* ── Account name links (Xero blue) ── */
    .xero-link {
        color: #0078C8;
        cursor: pointer;
        transition: color 0.15s;
    }

    .xero-link:hover {
        color: #005a9e;
        text-decoration: underline;
    }

    /* ── Section Header (Assets, Revenue, etc.) ── */
    .xero-section-header td {
        padding: 20px 16px 6px 16px;
        font-weight: 700;
        font-size: 14px;
        color: #333;
        border-bottom: 1px solid #ccc;
        background: transparent;
    }

    /* ── Sub-section label (Current Assets, etc.) ── */
    .xero-sub-label td {
        padding: 12px 16px 4px 28px;
        font-weight: 600;
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: none;
        background: transparent;
    }

    /* ── Group header (expandable) ── */
    .xero-group-header td {
        padding: 7px 16px 7px 32px;
        font-weight: 600;
        color: #333;
        border-bottom: none;
        background: transparent;
    }

    /* ── Sub-account row ── */
    .xero-sub-account {
        padding-left: 52px !important;
        color: #333;
        font-size: 13px;
    }

    /* ── Sub-section total ── */
    .xero-sub-total td {
        padding: 8px 16px 8px 28px;
        border-top: 1px solid #ddd;
        font-weight: 600;
        font-size: 13px;
        color: #333;
        background: transparent;
    }

    /* ── Section total ── */
    .xero-section-total td {
        padding: 10px 16px;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        font-weight: 700;
        font-size: 13px;
        color: #333;
        background: transparent;
    }

    /* ── Grand total / highlight row ── */
    .xero-grand-total td {
        padding: 12px 16px;
        border-top: 2px solid #333;
        border-bottom: 2px solid #333;
        font-weight: 700;
        font-size: 14px;
        color: #333;
        background: transparent;
    }

    /* ── Category header (for tabular reports) ── */
    .xero-category-row td {
        padding: 10px 16px;
        font-weight: 600;
        color: #333;
        border-bottom: 1px solid #eee;
        background: transparent;
    }

    /* ── Tabular report (Journal, Ledger, TB, Aged) ── */
    .xero-data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    /* for border in all reports except balance sheet */
    .xero-data-table tr {
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.15s ease;
    }

    .xero-data-table tbody tr:hover {
        background-color: #edf6ff;
    }

    /* for border in all reports except balance sheet */
    .xero-data-table thead th {
        padding: 10px 16px;
        font-size: 11px;
        font-weight: 600;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 1px solid #ddd;
        background: transparent;
        text-align: left;
    }

    .xero-data-table thead th.text-right {
        text-align: right;
    }

    .xero-data-table td {
        padding: 7px 16px;
        color: #333;
        border-bottom: none;
    }

    .xero-data-table td.text-right {
        text-align: right;
    }

    /* ── Group row in data tables ── */
    .xero-data-table .xero-group td {
        padding: 10px 16px;
        font-weight: 600;
        color: #333;
        border-top: 1px solid #eee;
        background: transparent;
    }

    /* ── Sub-row in data tables ── */
    .xero-data-table .xero-sub td {
        padding-left: 40px;
    }

    /* ── Totals in data tables ── */
    .xero-data-table .xero-page-total td {
        padding: 10px 16px;
        font-weight: 600;
        border-top: 1px solid #ddd;
        color: #333;
        background: transparent;
    }

    .xero-data-table .xero-total td {
        padding: 10px 16px;
        font-weight: 700;
        border-top: 2px solid #333;
        color: #333;
        background: transparent;
    }

    /* ── Subtotal row in aged/journal ── */
    .xero-data-table .xero-subtotal td {
        padding: 8px 16px;
        font-weight: 600;
        border-top: 1px solid #ddd;
        color: #555;
        background: transparent;
    }

    /* ── Variance colors ── */
    .positive,
    .inflow {
        color: #0f9f6e;
    }

    .negative,
    .outflow {
        color: #e02424;
    }

    .zero {
        color: #999;
    }

    .variance-column {
        color: #666;
        font-size: 12px;
    }

    /* ── Print ── */
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>
