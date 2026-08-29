<style>
    /* ── Balance Sheet specific overrides ── */

    .report-header {
        border-bottom: 1px solid #e8e8e8;
        padding-bottom: 16px;
        margin-bottom: 24px;
        text-align: center;
    }

    .report-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }

    .report-subtitle {
        color: #888;
        font-size: 13px;
        margin-top: 2px;
    }

    .report-controls {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 25px;
        align-items: center;
        padding: 20px;
        border-radius: 6px;
    }

    .btn-xero {
        background-color: #0078C8;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-xero:hover {
        background-color: #0f9fd4;
        transform: translateY(-1px);
    }

    /* ── Table base ── */
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .report-table th {
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

    .report-table th.amount {
        text-align: right;
        width: 150px;
    }

    /* for border in balance sheet */
    .report-table tr {
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.15s ease;
    }

    .report-table tbody tr:not(.section-header):not(.sub-section-label):hover {
        background-color: #edf6ff;
    }

    .report-table td {
        padding: 7px 16px;
        font-size: 13px;
        color: #333;
        line-height: 1.6;
    }

    /* for border in balance sheet */

    .report-table td.amount {
        text-align: right;
        font-weight: 400;
        white-space: nowrap;
    }

    /* ── Account names – blue links (Xero signature) ── */
    .account-name {
        color: #0078C8;
        cursor: pointer;
        transition: color 0.15s;
    }

    .account-name:hover {
        color: #005a9e;
        text-decoration: underline;
    }

    /* ── Section headers: Assets, Liabilities, Equity ── */
    .section-header td {
        padding: 20px 16px 6px 16px;
        font-weight: 700;
        font-size: 14px;
        color: #333;
        border-bottom: 1px solid #ccc;
        background: transparent;
    }

    /* ── Sub-section labels: Current Assets, Non-Current Assets, etc. ── */
    .sub-section-label td {
        padding: 12px 16px 4px 28px;
        font-weight: 600;
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: none;
        background: transparent;
    }

    /* ── Group header (expandable account groups) ── */
    .group-header {
        background: transparent;
        font-weight: 600;
        color: #333;
    }

    .group-header td {
        border-top: none;
        border-bottom: none;
        padding: 7px 16px 7px 32px;
        font-weight: 600;
    }

    /* ── Sub-account rows ── */
    .sub-account {
        padding-left: 52px !important;
        color: #333;
        font-size: 13px;
    }

    /* ── Sub-section total (Total Current Assets, etc.) ── */
    .sub-section-total td {
        padding: 8px 16px 8px 28px;
        border-top: 1px solid #ddd;
        border-bottom: none;
        font-weight: 600;
        font-size: 13px;
        color: #333;
        background: transparent;
    }

    /* ── Section total (Total Assets, Total Liabilities, Total Equity) ── */
    .section-total td {
        padding: 10px 16px;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        font-weight: 700;
        font-size: 13px;
        color: #333;
        background: transparent;
    }

    /* ── Net Assets row ── */
    .net-assets-row td {
        padding: 12px 16px;
        border-top: 2px solid #333;
        border-bottom: 2px solid #333;
        font-weight: 700;
        font-size: 14px;
        color: #333;
        background: transparent;
    }

    /* ── Grand total (Total Equity) ── */
    .grand-total td {
        padding: 10px 16px;
        border-top: 2px solid #333;
        border-bottom: none;
        font-weight: 700;
        font-size: 14px;
        color: #333;
        background: transparent;
    }

    /* ── Legacy class mapping ── */
    .category-header {
        background: transparent;
        font-weight: 700;
    }

    .category-header td {
        padding: 20px 16px 6px 16px;
        border-top: none;
        border-bottom: 1px solid #ccc;
        font-size: 14px;
        color: #333;
    }

    .category-total {
        background: transparent;
        font-weight: 700;
    }

    .category-total td {
        padding: 10px 16px;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
    }

    /* ── Variance colors ── */
    .positive {
        color: #0f9f6e;
    }

    .negative {
        color: #e02424;
    }

    .zero {
        color: #999;
    }

    .variance-column {
        color: #666;
        font-size: 12px;
    }

    .dropdown-menu {
        border: 1px solid #e0e0e0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* ── Balance check SVG stroke animation (Assets = Liabilities + Equity) ── */
    #balance-sheet-card {
        position: relative;
    }

    .balance-border-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: visible;
        opacity: 0;
        border-radius: 8px;
        z-index: 5;
    }

    .balance-border-rect {
        fill: none;
        stroke: transparent;
        stroke-width: 1;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        vector-effect: non-scaling-stroke;
    }

    #balance-sheet-card.balance-success .balance-border-svg {
        animation: border-fade 5.5s ease-out forwards;
    }

    #balance-sheet-card.balance-success .balance-border-rect {
        stroke: #0f9f6e;
        filter: drop-shadow(0 0 4px rgba(15, 159, 110, 0.55));
        animation: stroke-draw 4.5s linear forwards;
    }

    #balance-sheet-card.balance-error .balance-border-svg {
        animation: border-fade 5.5s ease-out forwards;
    }

    #balance-sheet-card.balance-error .balance-border-rect {
        stroke: #e02424;
        filter: drop-shadow(0 0 4px rgba(224, 36, 36, 0.55));
        animation: stroke-draw 4.5s linear forwards;
    }

    @keyframes stroke-draw {
        from {
            stroke-dashoffset: 100;
        }

        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes border-fade {
        0% {
            opacity: 0;
        }

        4% {
            opacity: 1;
        }

        88% {
            opacity: 1;
        }

        100% {
            opacity: 0;
        }
    }

    @media print {

        .report-controls,
        .no-print {
            display: none !important;
        }

        .balance-sheet-container {
            box-shadow: none;
            padding: 10px;
        }

        .balance-border-svg {
            display: none !important;
        }
    }

    @media (max-width: 768px) {
        .report-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    }
</style>
