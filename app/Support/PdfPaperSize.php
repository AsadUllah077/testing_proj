<?php

namespace App\Support;

class PdfPaperSize
{
    public const DEFAULT_INVOICE = 'long_invoice';
    public const DEFAULT_REPORT = 'a4';

    private const SIZES = [
        'long_invoice' => [
            'label' => 'Long Invoice Page (One Page)',
            'points' => [595.28, 1190.55],
            'css' => ['210mm', '420mm'],
        ],
        'a4' => [
            'label' => 'A4',
            'points' => [595.28, 841.89],
            'css' => ['210mm', '297mm'],
        ],
        'a3' => [
            'label' => 'A3',
            'points' => [841.89, 1190.55],
            'css' => ['297mm', '420mm'],
        ],
        'letter' => [
            'label' => 'Letter',
            'points' => [612, 792],
            'css' => ['8.5in', '11in'],
        ],
        'legal' => [
            'label' => 'Legal',
            'points' => [612, 1008],
            'css' => ['8.5in', '14in'],
        ],
        'executive' => [
            'label' => 'Executive',
            'points' => [522, 756],
            'css' => ['7.25in', '10.5in'],
        ],
        'folio' => [
            'label' => 'Folio',
            'points' => [612, 936],
            'css' => ['8.5in', '13in'],
        ],
        'tabloid' => [
            'label' => 'Tabloid / Ledger',
            'points' => [792, 1224],
            'css' => ['11in', '17in'],
        ],
        'envelope_10' => [
            'label' => 'Envelope #10',
            'points' => [297, 684],
            'css' => ['4.125in', '9.5in'],
        ],
        'envelope_dl' => [
            'label' => 'Envelope DL',
            'points' => [311.81, 623.62],
            'css' => ['110mm', '220mm'],
        ],
        'envelope_c5' => [
            'label' => 'Envelope C5',
            'points' => [459.21, 649.13],
            'css' => ['162mm', '229mm'],
        ],
    ];

    public static function options(): array
    {
        return array_map(fn(array $size) => $size['label'], self::SIZES);
    }

    public static function validationRule(): string
    {
        return 'nullable|string|in:' . implode(',', array_keys(self::SIZES));
    }

    public static function normalize(?string $paperSize, string $default = self::DEFAULT_INVOICE): string
    {
        return array_key_exists((string) $paperSize, self::SIZES) ? (string) $paperSize : $default;
    }

    public static function apply($pdf, ?string $paperSize, string $orientation = 'portrait')
    {
        [$width, $height] = self::points($paperSize);

        if ($orientation === 'landscape' && $height > $width) {
            [$width, $height] = [$height, $width];
        }

        return $pdf->setPaper([0, 0, $width, $height]);
    }

    public static function cssSize(?string $paperSize, string $orientation = 'portrait'): string
    {
        [$width, $height] = self::css($paperSize);

        if ($orientation === 'landscape') {
            [$width, $height] = [$height, $width];
        }

        return "{$width} {$height}";
    }

    private static function points(?string $paperSize): array
    {
        $key = self::normalize($paperSize);

        return self::SIZES[$key]['points'];
    }

    private static function css(?string $paperSize): array
    {
        $key = self::normalize($paperSize);

        return self::SIZES[$key]['css'];
    }
}
