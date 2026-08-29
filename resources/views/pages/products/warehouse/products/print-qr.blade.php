@php
    // URL-driven settings — change via query string or the settings panel below
    $onePerPage = (bool) request('one_per_page', 1);
    $cols       = $onePerPage ? 1 : max(1, min(3, (int) request('cols', 3)));
    $barWidth   = max(1, min(6, (int) request('bar_width', 2)));
    $barHeight  = max(20, min(200, (int) request('bar_height', 60)));
    $fontSize   = max(8, min(18, (int) request('font_size', 11)));
    $gap        = $onePerPage ? 0 : max(0, min(20, (int) request('gap', 6)));

    $colWidth = $onePerPage
        ? '100%'
        : match ($cols) {
            1 => '98%',
            2 => '48.5%',
            default => '31.5%',
        };

    $dns2d   = new \Milon\Barcode\DNS2D();
    $baseUrl = rtrim(env('BARCODE_BASE_URL', config('app.url')), '/');
    $qrSize  = $barWidth * 2;
    $imgData = $dns2d->getBarcodePNG($baseUrl . '/' . $product->unique_id, 'QRCODE', $qrSize, $qrSize);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QR Code — {{ $product->unique_id }}</title>
    <style>
        /* ───────── Print page ───────── */
        @page {
            margin: {{ $onePerPage ? '0.1in' : '0.25in' }};
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }
        }

        /* ───────── Settings panel ───────── */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
        }

        .no-print {
            background: #1e293b;
            color: #f1f5f9;
            padding: 14px 18px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 18px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
        }

        .no-print h2 {
            font-size: 13px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin: 0 0 10px;
            width: 100%;
        }

        .ctrl-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ctrl-group label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .ctrl-group select,
        .ctrl-group input[type=number] {
            background: #334155;
            border: 1px solid #475569;
            color: #f1f5f9;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 13px;
            width: 110px;
        }

        .ctrl-group input[type=range] {
            width: 120px;
            cursor: pointer;
            accent-color: #0078C8;
        }

        .range-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .range-val {
            font-size: 12px;
            font-weight: 700;
            color: #e2e8f0;
            min-width: 28px;
            text-align: center;
            background: #0f172a;
            border-radius: 4px;
            padding: 2px 5px;
        }

        /* Presets */
        .presets {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .presets label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .preset-row {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .preset-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #475569;
            cursor: pointer;
            background: #334155;
            color: #cbd5e1;
            white-space: nowrap;
            transition: all .15s;
        }

        .preset-btn:hover {
            background: #0078C8;
            border-color: #0078C8;
            color: #fff;
        }

        /* Action buttons */
        .actions {
            margin-left: auto;
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }

        .btn-apply {
            padding: 8px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            background: #0078C8;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .btn-apply:hover {
            background: #006bb0;
        }

        .btn-print {
            padding: 8px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 700;
            background: #16a34a;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #15803d;
        }

        /* ───────── Barcode grid ───────── */
        .barcode-grid {
            display: flex;
            flex-wrap: wrap;
            gap: {{ $gap }}px;
            padding: {{ $onePerPage ? '0' : '12px' }};
            background: #fff;
        }

        @media print {
            .barcode-grid {
                padding: 0;
                gap: {{ $gap }}px;
                background: #fff;
            }
        }

        .barcode-item {
            width: {{ $colWidth }};
            text-align: center;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: {{ $onePerPage ? '10px 6px' : '6px 4px 5px' }};
            box-sizing: border-box;
            background: #fff;

            @if ($onePerPage)
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            @endif
        }

        @media print {
            .barcode-item {
                border-color: #d1d5db;
            }

            @if ($onePerPage)
                .barcode-item {
                    page-break-after: always;
                    break-after: page;
                    border: none;
                    min-height: 0;
                    padding: 0;
                }

                .barcode-item:last-child {
                    page-break-after: avoid;
                    break-after: avoid;
                }
            @endif
        }

        .barcode-item img {
            max-width: 70%;
            max-height: 70vmin;
            width: auto;
            height: auto;
            display: block;
            margin: 12px auto 0;
        }

        @media print {
            .barcode-item img {
                max-width: 65%;
                max-height: 65vmin;
            }
        }

        .unique-id {
            margin-top: 4px;
            font-family: monospace;
            font-size: {{ $fontSize }}px;
            color: #1e293b;
            letter-spacing: 0.04em;
            word-break: break-all;
        }
    </style>
</head>

<body>

    {{-- ═══════════════ SETTINGS PANEL (hidden on print) ═══════════════ --}}
    <div class="no-print" id="settings">
        <h2>⚙ QR Code Print Settings</h2>

        {{-- Presets --}}
        <div class="presets">
            <label>Print Presets</label>
            <div class="preset-row">
                <button class="preset-btn" onclick="applyPreset(1, 2, 50, 9, 0)"
                    title="Tiny QR for 2×1 inch sticker">2×1 Tiny</button>
                <button class="preset-btn" onclick="applyPreset(1, 3, 50, 10, 0)"
                    title="Small QR for 2×2 inch sticker">2×2 Small</button>
                <button class="preset-btn" onclick="applyPreset(1, 4, 60, 11, 0)"
                    title="Medium QR for 4×2 inch sticker">4×2 Medium</button>
                <button class="preset-btn" onclick="applyPreset(3, 2, 50, 9, 4)"
                    title="3 per row on A4 sheet">Sheet (3-col)</button>
                <button class="preset-btn {{ $onePerPage ? 'active' : '' }}" onclick="toggleOnePerPage()"
                    title="One QR per label/page — ideal for roll printing"
                    style="{{ $onePerPage ? 'background:#0078C8;border-color:#0078C8;color:#fff;' : '' }}">
                    1 Per Page {{ $onePerPage ? '✓' : '' }}
                </button>
            </div>
        </div>

        {{-- Columns --}}
        <div class="ctrl-group">
            <label>Columns</label>
            <select id="selCols">
                <option value="1" {{ $cols == 1 ? 'selected' : '' }}>1 — Full width</option>
                <option value="2" {{ $cols == 2 ? 'selected' : '' }}>2 — Half width</option>
                <option value="3" {{ $cols == 3 ? 'selected' : '' }}>3 — Third width</option>
            </select>
        </div>

        {{-- QR Size --}}
        <div class="ctrl-group">
            <label>QR Size (1–6)</label>
            <div class="range-row">
                <input type="range" id="rangeBarWidth" min="1" max="6" step="1"
                    value="{{ $barWidth }}"
                    oninput="document.getElementById('valBarWidth').textContent = this.value">
                <span class="range-val" id="valBarWidth">{{ $barWidth }}</span>
            </div>
        </div>

        {{-- Bar Height --}}
        <div class="ctrl-group">
            <label>Bar Height (px)</label>
            <div class="range-row">
                <input type="range" id="rangeBarHeight" min="20" max="200" step="5"
                    value="{{ $barHeight }}"
                    oninput="document.getElementById('valBarHeight').textContent = this.value">
                <span class="range-val" id="valBarHeight">{{ $barHeight }}</span>
            </div>
        </div>

        {{-- Font Size --}}
        <div class="ctrl-group">
            <label>Text Size (px)</label>
            <div class="range-row">
                <input type="range" id="rangeFontSize" min="8" max="18" step="1"
                    value="{{ $fontSize }}"
                    oninput="document.getElementById('valFontSize').textContent = this.value">
                <span class="range-val" id="valFontSize">{{ $fontSize }}</span>
            </div>
        </div>

        {{-- Gap --}}
        <div class="ctrl-group">
            <label>Gap (px)</label>
            <div class="range-row">
                <input type="range" id="rangeGap" min="0" max="20" step="1"
                    value="{{ $gap }}" oninput="document.getElementById('valGap').textContent = this.value">
                <span class="range-val" id="valGap">{{ $gap }}</span>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="actions">
            <button class="btn-apply" onclick="applySettings()">Apply</button>
            <button class="btn-print" onclick="applyAndPrint()">🖨 Print</button>
        </div>
    </div>

    {{-- ═══════════════ QR CODE GRID ═══════════════ --}}
    <div class="barcode-grid">
        <div class="barcode-item">
            <img src="data:image/png;base64,{{ $imgData }}" alt="{{ $product->unique_id }}">
            <p class="unique-id">{{ $product->unique_id }}</p>
            @if($product->title)
                <p style="margin-top:4px;font-size:{{ $fontSize - 1 }}px;color:#64748b;font-family:Arial,sans-serif;">
                    {{ $product->title }}
                </p>
            @endif
        </div>
    </div>

    <script>
        const ONE_PER_PAGE = {{ $onePerPage ? 'true' : 'false' }};

        function getParams() {
            return {
                cols:         document.getElementById('selCols').value,
                bar_width:    document.getElementById('rangeBarWidth').value,
                bar_height:   document.getElementById('rangeBarHeight').value,
                font_size:    document.getElementById('rangeFontSize').value,
                gap:          document.getElementById('rangeGap').value,
                one_per_page: ONE_PER_PAGE ? '1' : '0',
            };
        }

        function buildUrl(params, autoprint = false) {
            const url = new URL(window.location.href);
            Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
            if (autoprint) url.searchParams.set('autoprint', '1');
            else url.searchParams.delete('autoprint');
            return url.toString();
        }

        function applySettings() {
            window.location.href = buildUrl(getParams());
        }

        function applyAndPrint() {
            const p = getParams();
            const cur = new URLSearchParams(window.location.search);
            const same = ['cols', 'bar_width', 'bar_height', 'font_size', 'gap', 'one_per_page']
                .every(k => (cur.get(k) ?? '0') == p[k]);
            if (same) {
                window.print();
            } else {
                window.location.href = buildUrl(p, true);
            }
        }

        function toggleOnePerPage() {
            const url = new URL(window.location.href);
            url.searchParams.set('one_per_page', ONE_PER_PAGE ? '0' : '1');
            window.location.href = url.toString();
        }

        function applyPreset(cols, barWidth, barHeight, fontSize, gap) {
            document.getElementById('selCols').value          = cols;
            document.getElementById('rangeBarWidth').value    = barWidth;
            document.getElementById('valBarWidth').textContent  = barWidth;
            document.getElementById('rangeBarHeight').value   = barHeight;
            document.getElementById('valBarHeight').textContent = barHeight;
            document.getElementById('rangeFontSize').value    = fontSize;
            document.getElementById('valFontSize').textContent  = fontSize;
            document.getElementById('rangeGap').value         = gap;
            document.getElementById('valGap').textContent       = gap;
            window.location.href = buildUrl({
                cols, bar_width: barWidth, bar_height: barHeight,
                font_size: fontSize, gap, one_per_page: '0'
            });
        }

        // Auto-print when redirected with ?autoprint=1
        @if (request('autoprint') == '1')
            window.addEventListener('load', () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('autoprint');
                window.history.replaceState({}, '', url.toString());
                setTimeout(() => window.print(), 300);
            });
        @endif
    </script>

</body>

</html>
