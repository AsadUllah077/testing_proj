@php
    $labelWidth = max(2, min(8, (float) request('width', 5)));
    $labelHeight = max(1, min(8, (float) request('height', 4.5)));
    $fontSize = max(8, min(32, (int) request('font_size', 20)));
    $padding = max(4, min(32, (int) request('padding', 12)));
    $printProducts = isset($products) ? collect($products) : collect([$product]);
    $barcodeBaseUrl = rtrim(env('BARCODE_BASE_URL', config('app.url')), '/');
    $barcodeGenerator = new \Milon\Barcode\DNS2D();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $printProducts->count() > 1 ? 'Bulk Specification Labels' : 'Specification Label - '.($printProducts->first()?->unique_id ?? '') }}</title>
    <style>
        :root {
            --label-width: {{ $labelWidth }}in;
            --label-height: {{ $labelHeight }}in;
            --label-font-size: {{ $fontSize }}px;
            --label-padding: {{ $padding }}px;
        }

        * {
            box-sizing: border-box;
        }

        @page {
            size: {{ $labelWidth }}in {{ $labelHeight }}in;
            margin: 0;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f1f5f9;
            color: #111827;
            font-family: Arial;
        }

        .settings {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
            padding: 14px 18px;
            color: #f8fafc;
            background: #1e293b;
            box-shadow: 0 2px 8px rgb(15 23 42 / 30%);
        }

        .settings-title {
            width: 100%;
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .presets {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .control {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .control label {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .control input,
        .button {
            height: 34px;
            border: 1px solid #475569;
            border-radius: 6px;
            padding: 0 10px;
            color: #f8fafc;
            background: #334155;
            font-size: 12px;
        }

        .control input {
            width: 84px;
        }

        .button {
            cursor: pointer;
            font-weight: 800;
        }

        .button:hover {
            border-color: #0078c8;
            background: #0078c8;
        }

        .button-primary {
            border-color: #0078c8;
            background: #0078c8;
        }

        .button-print {
            margin-left: auto;
            border-color: #16a34a;
            background: #16a34a;
        }

        .preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 30px;
            padding: 30px;
        }

        .label {
            display: flex;
            align-items: center;
            width: var(--label-width);
            min-height: var(--label-height);
            padding: var(--label-padding);
            overflow-wrap: anywhere;
            background: #fffdf5;
            color: #080808;
            font-family: Arial;
            font-size: var(--label-font-size);
            line-height: 1.12;
            box-shadow: 0 16px 34px rgb(15 23 42 / 18%);
        }

        .technical-card {
            position: relative;
            width: 100%;
            border: 2px solid #080808;
            background: #fffdf5;
        }

        .corner,
        .junction {
            position: absolute;
            z-index: 2;
            padding: 0 .08em;
            background: #fffdf5;
            font-weight: 800;
            line-height: 1;
        }

        .corner-top-left {
            top: 0;
            left: 0;
            transform: translate(-50%, -55%);
        }

        .corner-top-right {
            top: 0;
            right: 0;
            transform: translate(50%, -55%);
        }

        .corner-bottom-left {
            bottom: 0;
            left: 0;
            transform: translate(-50%, 50%);
        }

        .corner-bottom-right {
            right: 0;
            bottom: 0;
            transform: translate(50%, 50%);
        }

        .card-header {
            position: relative;
            border-bottom: 2px solid #080808;
            padding: .55em .8em .6em;
            text-align: center;
        }

        .junction-left {
            bottom: 0;
            left: 0;
            transform: translate(-50%, 50%);
        }

        .junction-right {
            right: 0;
            bottom: 0;
            transform: translate(50%, 50%);
        }

        .unique-id {
            margin: 0 0 .25em;
            /* font-size: .78em; */
            font-weight: 800;
            letter-spacing: .035em;
            font-size: 1.22em;
        }

        .product-title {
            margin: 0;
            font-size: 1.22em;
            font-weight: 800;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: max-content 1ch minmax(0, 1fr);
            gap: .18em .45em;
            padding: .7em 1em;
        }

        .card-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) {{ $labelWidth < 3 ? '35%' : '30%' }};
            align-items: stretch;
        }

        .barcode-panel {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: center;
            border-left: 2px solid #080808;
            padding: .55em;
        }

        .barcode-panel img {
            display: block;
            width: 100%;
            max-width: 1.25in;
            height: auto;
            image-rendering: pixelated;
        }

        .detail,
        .no-specifications {
            display: contents;
        }

        .detail-name {
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            font-size: 1.22em;
        }

        .detail-pipe {
            font-weight: 800;
            text-align: center;
        }

        .detail-value {
            min-width: 0;
            font-weight: bold !important;
            font-size: 1.22em;
        }

        .card-footer {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: .4em 1.2em;
            border-top: 2px solid #080808;
            padding: .55em .8em;
        }

        .card-footer .junction-left {
            top: 0;
            bottom: auto;
            transform: translate(-50%, -50%);
        }

        .card-footer .junction-right {
            top: 0;
            bottom: auto;
            transform: translate(50%, -50%);
        }

        .footer-label {
            font-weight: 900;
            font-size: 1.22em;
            text-transform: uppercase;
        }

        .footer-value {
            font-weight: 900;
            font-size: 1.22em;
        }

        @media print {
            body {
                min-height: 0;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .preview {
                display: block;
                padding: 0;
            }

            .label {
                width: var(--label-width);
                min-height: var(--label-height);
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                box-shadow: none;
                break-inside: avoid;
                break-after: page;
                page-break-after: always;
            }

            .label:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <form method="GET" class="settings no-print" id="printSettings">
        <h1 class="settings-title">Product Specification Label Settings</h1>

        <div class="control">
            <label>Presets</label>
            <div class="presets">
                <button type="button" class="button" onclick="applyPreset(2, 1, 10, 6)">2 x 1</button>
                <button type="button" class="button" onclick="applyPreset(3, 2, 14, 10)">3 x 2</button>
                <button type="button" class="button" onclick="applyPreset(4, 2, 16, 12)">4 x 2</button>
                <button type="button" class="button" onclick="applyPreset(4, 3, 18, 14)">4 x 3</button>
            </div>
        </div>

        <div class="control">
            <label for="width">Width (in)</label>
            <input id="width" name="width" type="number" min="2" max="8" step="0.25" value="{{ $labelWidth }}">
        </div>
        <div class="control">
            <label for="height">Height (in)</label>
            <input id="height" name="height" type="number" min="1" max="8" step="0.25" value="{{ $labelHeight }}">
        </div>
        <div class="control">
            <label for="font_size">Text size</label>
            <input id="font_size" name="font_size" type="number" min="8" max="32" value="{{ $fontSize }}">
        </div>
        <div class="control">
            <label for="padding">Padding</label>
            <input id="padding" name="padding" type="number" min="4" max="32" value="{{ $padding }}">
        </div>

        <button type="submit" class="button button-primary">Apply</button>
        <button type="button" class="button button-print" onclick="window.print()">Print Label</button>
    </form>

    <main class="preview">
        @foreach ($printProducts as $product)
            @php
                $specifications = collect($product->specifications ?? [])
                    ->map(function ($specification) {
                        if (is_array($specification)) {
                            return [
                                'key' => trim((string) ($specification['key'] ?? '')),
                                'value' => trim((string) ($specification['value'] ?? '')),
                            ];
                        }

                        return ['key' => trim((string) $specification), 'value' => ''];
                    })
                    ->filter(fn ($specification) => $specification['key'] !== '' || $specification['value'] !== '')
                    ->values();
                $barcodeImage = $barcodeGenerator->getBarcodePNG(
                    $barcodeBaseUrl.'/'.$product->unique_id,
                    'QRCODE',
                    4,
                    4
                );
            @endphp
        <section class="label" aria-label="Product specification label">
            <div class="technical-card">
                {{-- <span class="corner corner-top-left" aria-hidden="true">+</span>
                <span class="corner corner-top-right" aria-hidden="true">+</span>
                <span class="corner corner-bottom-left" aria-hidden="true">+</span>
                <span class="corner corner-bottom-right" aria-hidden="true">+</span> --}}

                <header class="card-header">
                    {{-- <span class="junction junction-left" aria-hidden="true">+</span>
                    <span class="junction junction-right" aria-hidden="true">+</span> --}}
                    {{-- <div class="unique-id">{{ $product->unique_id ?: 'N/A' }}</div> --}}
                    <h1 class="product-title">{{ $product->title ?: 'N/A' }}</h1>
                </header>

                <div class="card-body">
                    <div class="detail-grid">
                        @forelse ($specifications as $specification)
                            <div class="detail">
                                <span class="detail-name">{{ $specification['key'] !== '' ? $specification['key'] : 'SPEC' }}</span>
                                <span class="detail-pipe" aria-hidden="true">|</span>
                                <span class="detail-value" style="font-weight: bold !important;">{{ $specification['value'] !== '' ? $specification['value'] : 'N/A' }}</span>
                            </div>
                        @empty
                            <div class="no-specifications">
                                <span class="detail-name">SPEC</span>
                                <span class="detail-pipe" aria-hidden="true">|</span>
                                <span class="detail-value">N/A</span>
                            </div>
                        @endforelse
                    </div>

                    <aside class="barcode-panel" aria-label="Product barcode">
                        <img src="data:image/png;base64,{{ $barcodeImage }}"
                            alt="Barcode for {{ $product->unique_id }}">
                    </aside>
                </div>

                <footer class="card-footer">
                    {{-- <span class="junction junction-left" aria-hidden="true">+</span>
                    <span class="junction junction-right" aria-hidden="true">+</span> --}}
                    <div>
                        <span class="footer-label">ID:</span>
                        <span class="footer-value">{{ $product->unique_id ?: 'N/A' }}</span>
                    </div>
                    {{-- <div>
                        <span class="footer-label">Status:</span>
                        <span class="footer-value">{{ $displayStatus }}</span>
                    </div> --}}
                </footer>
            </div>
        </section>
        @endforeach
    </main>

    <script>
        function applyPreset(width, height, fontSize, padding) {
            document.getElementById('width').value = width;
            document.getElementById('height').value = height;
            document.getElementById('font_size').value = fontSize;
            document.getElementById('padding').value = padding;
            document.getElementById('printSettings').submit();
        }
    </script>
</body>
</html>
