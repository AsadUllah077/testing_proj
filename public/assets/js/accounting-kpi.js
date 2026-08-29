/* ── Colour palette ─────────────────────────────────────────── */
const C = {
    primary: "#13b5ea", // Xero blue
    pDark: "#0f9fd4",
    pLight: "#eaf7fd",
    navy: "#253544",
    success: "#4cb653", // Xero green
    danger: "#e74c3c", // Xero red
    warning: "#ff8b00", // orange
    purple: "#6554c0",
    teal: "#00b8d9",
    gray: "#dfe1e6",
    muted: "#6b778c",
    bg: "#f4f5f7",
    white: "#ffffff",
};

/* ── Helpers ────────────────────────────────────────────────── */
function hex(h, a) {
    const r = parseInt(h.slice(1, 3), 16),
        g = parseInt(h.slice(3, 5), 16),
        b = parseInt(h.slice(5, 7), 16);
    return `rgba(${r},${g},${b},${a})`;
}

function grad(ctx, color, h = 120) {
    const g = ctx.createLinearGradient(0, 0, 0, h);
    g.addColorStop(0, hex(color, 0.3));
    g.addColorStop(1, hex(color, 0));
    return g;
}
Chart.defaults.font.family = "'Inter','Instrument Sans','Segoe UI',sans-serif";
Chart.defaults.color = C.muted;

const mini = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1100,
        easing: "easeOutQuart",
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            enabled: true,
            backgroundColor: C.navy,
            padding: 7,
            cornerRadius: 5,
            displayColors: false,
            callbacks: {
                label: (ctx) => Number(ctx.raw).toLocaleString(),
            },
        },
    },
    scales: {
        x: {
            display: false,
        },
        y: {
            display: false,
        },
    },
    elements: {
        point: {
            radius: 0,
            hoverRadius: 4,
        },
        line: {
            borderWidth: 2,
            tension: 0.4,
        },
    },
    layout: {
        padding: 0,
    },
};

/* ── Sparkline factory ──────────────────────────────────────── */
function spark(id, data, color, type = "line") {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
        type,
        data: {
            labels: kpi.monthly_labels,
            datasets: [
                {
                    data,
                    borderColor: color,
                    backgroundColor:
                        type === "line"
                            ? (ctx) => grad(ctx.chart.ctx, color, 55)
                            : hex(color, 0.7),
                    fill: type === "line",
                    borderRadius: type === "bar" ? 2 : undefined,
                    barPercentage: 0.6,
                },
            ],
        },
        options: {
            ...mini,
            scales: {
                x: {
                    display: false,
                },
                y: {
                    display: false,
                },
            },
        },
    });
}

const inc = kpi.monthly_income.map((d) => d.value);
const exp = kpi.monthly_expenses.map((d) => d.value);
const cogs = kpi.monthly_cogs.map((d) => d.value);
const netM = inc.map((v, i) => v - exp[i] - cogs[i]);
const balM = kpi.monthly_balance.map((d) => d.balance);
const gpM = inc.map((v, i) => (v > 0 ? ((v - cogs[i]) / v) * 100 : 0));
const npmM = inc.map((v, i) => (v > 0 ? (netM[i] / v) * 100 : 0));

/* Sparklines */
spark("sp-rev", inc, C.primary);
spark("sp-cogs", cogs, C.warning);
spark("sp-opex", exp, C.danger);
spark("sp-net", netM, C.success);
spark("sp-gpm", gpM, C.teal, "bar");
spark(
    "sp-opm",
    gpM.map((_, i) =>
        inc[i] > 0
            ? ((netM[i] - (inc[i] - exp[i] - cogs[i] - netM[i])) / inc[i]) * 100
            : 0,
    ),
    C.primary,
    "bar",
);
spark("sp-npm", npmM, C.success, "bar");
spark("sp-ebitdam", npmM, C.purple, "bar");
spark(
    "sp-ar",
    inc.map(() => kpi.accounts_receivable / 12),
    C.primary,
);
spark(
    "sp-ap",
    inc.map(() => kpi.accounts_payable / 12),
    C.warning,
);
spark(
    "sp-wc",
    inc.map(() => kpi.working_capital / 12),
    C.teal,
);
spark("sp-bal", balM, C.navy);
spark(
    "sp-roa",
    inc.map((_, i) => (i === 11 ? kpi.roa : 0)),
    C.primary,
    "bar",
);
spark(
    "sp-roe",
    inc.map((_, i) => (i === 11 ? kpi.roe : 0)),
    C.teal,
    "bar",
);
spark("sp-ebit", netM, C.navy);
spark("sp-ebitda", netM, C.purple);

/* ── Main Trend Chart ───────────────────────────────────────── */
new Chart(document.getElementById("mainTrendChart"), {
    type: "bar",
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            {
                label: "Revenue",
                data: inc,
                backgroundColor: hex(C.primary, 0.75),
                borderRadius: 4,
                barPercentage: 0.55,
                order: 2,
            },
            {
                label: "Expenses",
                data: exp.map((v, i) => v + cogs[i]),
                backgroundColor: hex(C.danger, 0.65),
                borderRadius: 4,
                barPercentage: 0.55,
                order: 3,
            },
            {
                label: "Net Profit",
                data: netM,
                type: "line",
                borderColor: C.success,
                backgroundColor: "transparent",
                borderWidth: 2.5,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: C.white,
                pointBorderColor: C.success,
                order: 1,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1400,
            easing: "easeOutQuart",
        },
        plugins: {
            legend: {
                display: true,
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 16,
                    font: {
                        size: 11,
                    },
                },
            },
            tooltip: {
                mode: "index",
                intersect: false,
                backgroundColor: C.navy,
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: (ctx) =>
                        ctx.dataset.label +
                        ": " +
                        Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
        },
    },
});

/* ── P&L Breakdown ──────────────────────────────────────────── */
new Chart(document.getElementById("plBreakdown"), {
    type: "bar",
    data: {
        labels: [
            "Revenue",
            "Cost of Sales",
            "Gross Profit",
            "Operating Exp.",
            "Net Profit",
        ],
        datasets: [
            {
                data: [
                    kpi.profit_loss_data.revenue,
                    -Math.abs(kpi.profit_loss_data.cogs),
                    kpi.profit_loss_data.gross_profit,
                    -Math.abs(kpi.profit_loss_data.expenses),
                    kpi.profit_loss_data.net_income,
                ],
                backgroundColor: [
                    hex(C.primary, 0.85),
                    hex(C.warning, 0.85),
                    hex(C.teal, 0.85),
                    hex(C.danger, 0.85),
                    kpi.profit_loss_data.net_income >= 0
                        ? hex(C.success, 0.9)
                        : hex(C.danger, 0.9),
                ],
                borderRadius: 6,
                barThickness: 50,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1600,
            easing: "easeOutExpo",
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: (ctx) => Math.abs(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 12,
                        weight: "600",
                    },
                    color: C.navy,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Margin Trend ───────────────────────────────────────────── */
new Chart(document.getElementById("marginTrendChart"), {
    type: "line",
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            {
                label: "Gross Margin %",
                data: gpM,
                borderColor: C.teal,
                backgroundColor: (ctx) => grad(ctx.chart.ctx, C.teal, 220),
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 3,
                pointBackgroundColor: C.white,
                pointBorderColor: C.teal,
            },
            {
                label: "Net Margin %",
                data: npmM,
                borderColor: C.primary,
                backgroundColor: "transparent",
                fill: false,
                tension: 0.4,
                borderWidth: 2,
                borderDash: [5, 3],
                pointRadius: 2,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 7,
                    padding: 14,
                    font: {
                        size: 11,
                    },
                },
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        ctx.dataset.label +
                        ": " +
                        ctx.parsed.y.toFixed(1) +
                        "%",
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toFixed(0) + "%",
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Net Profit Bar ─────────────────────────────────────────── */
new Chart(document.getElementById("netProfitBarChart"), {
    type: "bar",
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            {
                label: "Net Profit",
                data: netM,
                backgroundColor: netM.map((v) =>
                    v >= 0 ? hex(C.success, 0.75) : hex(C.danger, 0.75),
                ),
                borderRadius: 4,
                barPercentage: 0.65,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) => Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Gauge factory ──────────────────────────────────────────── */
function gauge(id, value, max, color) {
    const el = document.getElementById(id);
    if (!el) return;
    const v = Math.min(Math.abs(value), max);
    new Chart(el, {
        type: "doughnut",
        data: {
            labels: ["Value", "Rest"],
            datasets: [
                {
                    data: [v, max - v],
                    backgroundColor: [color, C.gray],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: -90,
                    hoverOffset: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "80%",
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    enabled: false,
                },
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1700,
                easing: "easeOutCirc",
            },
        },
    });
}
gauge(
    "gauCR",
    kpi.current_ratio,
    3,
    kpi.current_ratio >= 1.5
        ? C.success
        : kpi.current_ratio >= 1
          ? C.warning
          : C.danger,
);
gauge("gauQR", kpi.quick_ratio, 2, kpi.quick_ratio >= 1 ? C.success : C.danger);
gauge(
    "gauDebt",
    kpi.financial_position.debt_ratio,
    100,
    kpi.financial_position.debt_ratio > 70
        ? C.danger
        : kpi.financial_position.debt_ratio > 50
          ? C.warning
          : C.success,
);
gauge(
    "gauZScore",
    kpi.altman_z_score,
    5,
    kpi.altman_z_score >= 2.99
        ? C.success
        : kpi.altman_z_score >= 1.81
          ? C.warning
          : C.danger,
);

/* ── AR vs AP ───────────────────────────────────────────────── */
new Chart(document.getElementById("arApChart"), {
    type: "bar",
    data: {
        labels: ["Receivables", "Payables"],
        datasets: [
            {
                data: [kpi.accounts_receivable, kpi.accounts_payable],
                backgroundColor: [hex(C.primary, 0.8), hex(C.warning, 0.8)],
                borderRadius: 6,
                barThickness: 50,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) => Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 12,
                        weight: "600",
                    },
                    color: C.navy,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Balance History ────────────────────────────────────────── */
const ctxBal = document.getElementById("balanceHistoryChart").getContext("2d");
new Chart(ctxBal, {
    type: "line",
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            {
                label: "Balance",
                data: balM,
                borderColor: C.primary,
                backgroundColor: (ctx) => grad(ctx.chart.ctx, C.primary, 280),
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: C.white,
                pointBorderColor: C.primary,
                pointHoverRadius: 7,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                mode: "index",
                intersect: false,
                backgroundColor: C.navy,
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: (ctx) =>
                        "Balance: " + Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Cash Flow Composition ──────────────────────────────────── */
new Chart(document.getElementById("cashFlowCompositionChart"), {
    type: "bar",
    data: {
        labels: kpi.monthly_income.map((d) => d.month),
        datasets: [
            {
                label: "Income",
                data: inc,
                backgroundColor: hex(C.success, 0.75),
                borderRadius: 4,
                barPercentage: 0.6,
            },
            {
                label: "Expenses",
                data: exp.map((v, i) => v + cogs[i]),
                backgroundColor: hex(C.danger, 0.65),
                borderRadius: 4,
                barPercentage: 0.6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 16,
                    font: {
                        size: 11,
                    },
                },
            },
            tooltip: {
                mode: "index",
                intersect: false,
                backgroundColor: C.navy,
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: (ctx) =>
                        ctx.dataset.label +
                        ": " +
                        Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Assets Donut ───────────────────────────────────────────── */
new Chart(document.getElementById("assetsDonut"), {
    type: "doughnut",
    data: {
        labels: ["Current", "Non-Current"],
        datasets: [
            {
                data: [
                    kpi.financial_position.current_assets,
                    Math.max(
                        0,
                        kpi.financial_position.total_assets -
                            kpi.financial_position.current_assets,
                    ),
                ],
                backgroundColor: [C.primary, C.teal],
                borderWidth: 0,
                hoverOffset: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "65%",
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 7,
                    padding: 8,
                    font: {
                        size: 10,
                    },
                },
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        ctx.label + ": " + Number(ctx.raw).toLocaleString(),
                },
            },
        },
        animation: {
            animateScale: true,
            animateRotate: true,
            duration: 1500,
        },
    },
});

/* ── Capital Structure ──────────────────────────────────────── */
new Chart(document.getElementById("capitalChart"), {
    type: "bar",
    data: {
        labels: ["Capital Structure"],
        datasets: [
            {
                label: "Equity",
                data: [Math.max(0, kpi.financial_position.total_equity)],
                backgroundColor: hex(C.success, 0.8),
                borderRadius: 4,
            },
            {
                label: "Liabilities",
                data: [kpi.financial_position.total_liabilities],
                backgroundColor: hex(C.danger, 0.75),
                borderRadius: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: "y",
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 16,
                    font: {
                        size: 11,
                    },
                },
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        ctx.dataset.label +
                        ": " +
                        Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                stacked: true,
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    font: {
                        size: 10,
                    },
                },
            },
            y: {
                stacked: true,
                grid: {
                    display: false,
                },
            },
        },
    },
});

/* ── CCC Chart ──────────────────────────────────────────────── */
new Chart(document.getElementById("cccChart"), {
    type: "bar",
    data: {
        labels: ["AR Days (DSO)", "Inventory Days (DIO)", "AP Days (DPO)"],
        datasets: [
            {
                data: [
                    kpi.efficiency_ratios.ar_days,
                    kpi.efficiency_ratios.inventory_days,
                    kpi.efficiency_ratios.ap_days,
                ],
                backgroundColor: [
                    hex(C.primary, 0.8),
                    hex(C.purple, 0.8),
                    hex(C.warning, 0.8),
                ],
                borderRadius: 6,
                barThickness: 50,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) => ctx.parsed.y.toFixed(1) + " days",
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: "600",
                    },
                    color: C.navy,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v + "d",
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Turnover Chart ─────────────────────────────────────────── */
new Chart(document.getElementById("turnoverChart"), {
    type: "bar",
    data: {
        labels: [
            "AR Turnover",
            "AP Turnover",
            "Inv. Turnover",
            "Asset Turnover",
            "WC Turnover",
        ],
        datasets: [
            {
                data: [
                    kpi.efficiency_ratios.ar_turnover,
                    kpi.efficiency_ratios.ap_turnover,
                    kpi.efficiency_ratios.inventory_turnover,
                    kpi.efficiency_ratios.asset_turnover,
                    kpi.efficiency_ratios.working_capital_turnover,
                ],
                backgroundColor: [
                    hex(C.primary, 0.8),
                    hex(C.warning, 0.8),
                    hex(C.purple, 0.8),
                    hex(C.teal, 0.8),
                    hex(C.success, 0.8),
                ],
                borderRadius: 6,
                barThickness: 38,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) => ctx.parsed.y.toFixed(2) + "x",
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                        weight: "600",
                    },
                    color: C.navy,
                    maxRotation: 30,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v + "x",
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Return Metrics ─────────────────────────────────────────── */
new Chart(document.getElementById("returnMetricsChart"), {
    type: "bar",
    data: {
        labels: [
            "ROA",
            "ROE",
            "DuPont ROE",
            "Op. Margin",
            "Net Margin",
            "EBITDA Margin",
        ],
        datasets: [
            {
                data: [
                    kpi.roa,
                    kpi.roe,
                    kpi.dupont_roe,
                    kpi.operating_margin,
                    kpi.net_profit_margin,
                    kpi.ebitda_margin,
                ],
                backgroundColor: [
                    hex(C.primary, 0.8),
                    hex(C.teal, 0.8),
                    hex(C.purple, 0.8),
                    hex(C.warning, 0.8),
                    hex(C.success, 0.8),
                    hex(C.pDark, 0.8),
                ],
                borderRadius: 6,
                barThickness: 35,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) => ctx.parsed.y.toFixed(1) + "%",
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                        weight: "600",
                    },
                    color: C.navy,
                    maxRotation: 30,
                },
            },
            y: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                    borderDash: [4, 4],
                },
                ticks: {
                    callback: (v) => v.toFixed(0) + "%",
                    color: C.muted,
                    font: {
                        size: 10,
                    },
                },
            },
        },
    },
});

/* ── Top Accounts (horizontal) ──────────────────────────────── */
new Chart(document.getElementById("topAccountsChart"), {
    type: "bar",
    data: {
        labels: kpi.top_accounts.map((a) =>
            a.name.length > 16 ? a.name.substring(0, 16) + "…" : a.name,
        ),
        datasets: [
            {
                label: "Balance",
                data: kpi.top_accounts.map((a) => a.balance),
                backgroundColor: kpi.top_accounts.map((a) =>
                    a.balance >= 0 ? hex(C.success, 0.75) : hex(C.danger, 0.75),
                ),
                borderRadius: 4,
                barThickness: 18,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: "y",
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        "Balance: " + Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                border: {
                    display: false,
                },
                grid: {
                    color: C.bg,
                },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    font: {
                        size: 9,
                    },
                },
            },
            y: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: {
                        size: 10,
                    },
                    color: C.muted,
                },
            },
        },
    },
});

/* ── Operations & Tax Charts ────────────────────────────────── */

// Cost Structure Donut
new Chart(document.getElementById("costStructureChart"), {
    type: "doughnut",
    data: {
        labels: ["COGS", "Payroll", "Overhead", "Net Profit"],
        datasets: [
            {
                data: [
                    Math.abs(kpi.cogs),
                    Math.abs(kpi.payroll_expense),
                    Math.abs(kpi.overhead_expense),
                    Math.max(0, kpi.net_profit),
                ],
                backgroundColor: [
                    hex(C.warning, 0.85),
                    hex(C.danger, 0.8),
                    hex(C.muted, 0.6),
                    hex(C.success, 0.85),
                ],
                borderWidth: 0,
                hoverOffset: 6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "60%",
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 12,
                    font: { size: 11 },
                },
            },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        ctx.label + ": " + Number(ctx.raw).toLocaleString(),
                },
            },
        },
        animation: { animateScale: true, animateRotate: true, duration: 1600 },
    },
});

// Breakeven Analysis (line chart)
(function () {
    const be = kpi.breakeven_revenue;
    const rev = kpi.total_income;
    const fixed = kpi.overhead_expense + kpi.payroll_expense;
    const points = [0, be * 0.4, be * 0.7, be, rev * 0.8, rev];
    const revenueL = points;
    const totalCostL = points.map(
        (r) => fixed + r * (1 - kpi.contribution_margin_ratio / 100),
    );
    new Chart(document.getElementById("breakevenChart"), {
        type: "line",
        data: {
            labels: points.map((v) => Math.round(v).toLocaleString()),
            datasets: [
                {
                    label: "Revenue",
                    data: revenueL,
                    borderColor: C.success,
                    backgroundColor: "transparent",
                    borderWidth: 2.5,
                    tension: 0,
                    pointRadius: 3,
                },
                {
                    label: "Total Cost",
                    data: totalCostL,
                    borderColor: C.danger,
                    backgroundColor: "transparent",
                    borderWidth: 2.5,
                    tension: 0,
                    borderDash: [6, 3],
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: "bottom",
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 12,
                        font: { size: 11 },
                    },
                },
                tooltip: {
                    backgroundColor: C.navy,
                    callbacks: {
                        label: (ctx) =>
                            ctx.dataset.label +
                            ": " +
                            Number(ctx.raw).toLocaleString(),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 9 },
                        color: C.muted,
                        maxRotation: 30,
                    },
                },
                y: {
                    border: { display: false },
                    grid: { color: hex(C.muted, 0.1) },
                    ticks: {
                        callback: (v) => v.toLocaleString(),
                        font: { size: 9 },
                        color: C.muted,
                    },
                },
            },
        },
    });
})();

// Monthly Net Cash Flow
new Chart(document.getElementById("netCashFlowChart"), {
    type: "bar",
    data: {
        labels: kpi.monthly_net_cash_flow.map((d) => d.month),
        datasets: [
            {
                label: "Net Cash Flow",
                data: kpi.monthly_net_cash_flow.map((d) => d.value),
                backgroundColor: kpi.monthly_net_cash_flow.map((d) =>
                    d.value >= 0 ? hex(C.success, 0.75) : hex(C.danger, 0.75),
                ),
                borderRadius: 4,
                barPercentage: 0.65,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: C.navy,
                callbacks: {
                    label: (ctx) =>
                        "Net CF: " + Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 }, color: C.muted },
            },
            y: {
                border: { display: false },
                grid: { color: C.bg, borderDash: [4, 4] },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: { size: 10 },
                },
            },
        },
    },
});

// Expense Composition stacked bar
new Chart(document.getElementById("expenseCompositionChart"), {
    type: "bar",
    data: {
        labels: kpi.monthly_labels,
        datasets: [
            {
                label: "COGS",
                data: kpi.monthly_cogs.map((d) => d.value),
                backgroundColor: hex(C.warning, 0.8),
                borderRadius: 2,
            },
            {
                label: "Payroll",
                data: kpi.monthly_income.map(() => kpi.payroll_expense / 12),
                backgroundColor: hex(C.danger, 0.7),
                borderRadius: 2,
            },
            {
                label: "Overhead",
                data: kpi.monthly_income.map(() => kpi.overhead_expense / 12),
                backgroundColor: hex(C.muted, 0.5),
                borderRadius: 2,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 12,
                    font: { size: 11 },
                },
            },
            tooltip: {
                mode: "index",
                intersect: false,
                backgroundColor: C.navy,
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: (ctx) =>
                        ctx.dataset.label +
                        ": " +
                        Number(ctx.raw).toLocaleString(),
                },
            },
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false },
                ticks: { font: { size: 10 }, color: C.muted },
            },
            y: {
                stacked: true,
                border: { display: false },
                grid: { color: C.bg, borderDash: [4, 4] },
                ticks: {
                    callback: (v) => v.toLocaleString(),
                    color: C.muted,
                    font: { size: 10 },
                },
            },
        },
    },
});

/* ── Tab switching ──────────────────────────────────────────── */
document.querySelectorAll(".kpi-tab").forEach((t) => {
    t.addEventListener("click", function () {
        document
            .querySelectorAll(".kpi-tab")
            .forEach((x) => x.classList.remove("active"));
        document
            .querySelectorAll(".tab-pane")
            .forEach((x) => x.classList.remove("active"));
        this.classList.add("active");
        const p = document.getElementById("tab-" + this.dataset.tab);
        if (p) p.classList.add("active");
    });
});

/* ── Stripe-style dropdown ─────────────────────────────────── */
(function () {
    const dropdown = document.getElementById("stripeDropdown");
    if (!dropdown) return;
    const toggle = document.getElementById("stripeToggle");
    const label = document.getElementById("stripeLabel");
    const select = dropdown.closest("form").querySelector("select");
    const items = dropdown.querySelectorAll(".stripe-dropdown-item");

    toggle.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("open");
    });

    items.forEach(function (item) {
        item.addEventListener("click", function () {
            const val = this.dataset.value;
            label.textContent = this.textContent.trim();
            items.forEach(function (i) { i.classList.remove("active"); });
            this.classList.add("active");
            dropdown.classList.remove("open");
            select.value = val;
            select.dispatchEvent(new Event("change"));
        });
    });

    document.addEventListener("click", function (e) {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove("open");
        }
    });
})();
