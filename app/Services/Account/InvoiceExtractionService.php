<?php

namespace App\Services\Account;

use App\Models\TaxRate;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Facades\Ai;
use App\Services\AI\Prompts;

class InvoiceExtractionService
{
    /** Minimum similarity score (0-1) to auto-match a vendor. */
    private const VENDOR_MATCH_THRESHOLD = 0.60;

    /** Maximum tolerance when matching extracted tax % to a TaxRate record. */
    private const TAX_RATE_TOLERANCE = 0.5;

    /** Tokens stripped before text similarity scoring. */
    private const STOPWORDS = [
        'the', 'and', 'for', 'from', 'to', 'of', 'on', 'at', 'by', 'in', 'with',
        'pvt', 'ltd', 'llc', 'inc', 'corp', 'co', 'company', 'limited',
    ];

    /**
     * Extract structured invoice data from an uploaded file.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function extract(UploadedFile $file, string $partyType = 'vendor'): array
    {
        $mediaType = $this->resolveMediaType($file);
        if (!$mediaType) {
            return [
                'success' => false,
                'message' => 'Unsupported file type. Please upload a PDF, JPG, or PNG.',
            ];
        }

        $base64 = base64_encode(file_get_contents($file->getRealPath()));

        try {
            $extracted = Ai::extractJson(
                $partyType === 'customer'
                    ? Prompts::saleInvoiceExtraction()
                    : Prompts::invoiceExtraction(),
                $base64,
                $mediaType
            );
        } catch (\Throwable $e) {
            Log::error('Invoice extraction error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Invoice extraction failed: ' . $e->getMessage(),
            ];
        }

        if (empty($extracted)) {
            return [
                'success' => false,
                'message' => 'Could not extract data from the uploaded file. Please enter the invoice manually.',
            ];
        }

        // Resolve vendor/customer and tax rates against the database.
        $formData = $this->mapToFormData($extracted, $partyType);

        return [
            'success' => true,
            'data'    => $formData,
        ];
    }

    /**
     * Map raw extracted data to form-ready payload (with vendor_id, tax_ids, etc.)
     */
    private function mapToFormData(array $extracted, string $partyType): array
    {
        $partyName = $partyType === 'customer'
            ? ($extracted['customer_name'] ?? $extracted['vendor_name'] ?? null)
            : ($extracted['vendor_name'] ?? null);

        $partyMatch = $this->matchParty($partyName, $partyType);
        $taxRates = TaxRate::all();

        $rows = [];
        foreach (($extracted['line_items'] ?? []) as $item) {
            $taxId = $this->matchTaxRate($item['tax_percentage'] ?? null, $taxRates);
            $rows[] = [
                'description'    => $item['description'] ?? '',
                'quantity'       => $item['quantity'] ?? 1,
                'price'          => $item['unit_price'] ?? 0,
                'tax_id'         => $taxId,
                'tax_percentage' => $item['tax_percentage'] ?? null,
                'total'          => $item['total'] ?? 0,
            ];
        }

        $data = [
            'invoice_no'  => $extracted['invoice_no'] ?? null,
            'date'        => $extracted['date'] ?? null,
            'ref_no'      => $extracted['ref_no'] ?? null,
            'currency'    => $extracted['currency'] ?? null,
            'note'        => $extracted['note'] ?? null,
            'rows'        => $rows,
        ];

        if ($partyType === 'customer') {
            $data['customer_id'] = $partyMatch['id'] ?? null;
            $data['customer_name'] = $partyName;
            $data['customer_score'] = $partyMatch['score'] ?? null;
            return $data;
        }

        $data['vendor_id'] = $partyMatch['id'] ?? null;
        $data['vendor_name'] = $partyName;
        $data['vendor_score'] = $partyMatch['score'] ?? null;

        return $data;
    }

    /**
     * Fuzzy-match an extracted party name to an existing Vendor or Customer record.
     */
    private function matchParty(?string $extractedName, string $partyType): ?array
    {
        if (empty($extractedName)) return null;

        $parties = $partyType === 'customer'
            ? Customer::all(['id', 'name'])
            : Vendor::all(['id', 'name']);

        if ($parties->isEmpty()) return null;

        $normalizedExtracted = $this->normalizeText($extractedName);
        $tokensExtracted     = $this->tokenize($normalizedExtracted);

        $bestMatch = null;
        $bestScore = 0;

        foreach ($parties as $party) {
            $normalizedParty = $this->normalizeText($party->name);
            $tokensParty     = $this->tokenize($normalizedParty);
            $score           = $this->textSimilarity($normalizedExtracted, $normalizedParty, $tokensExtracted, $tokensParty);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $party;
            }
        }

        if ($bestScore < self::VENDOR_MATCH_THRESHOLD || !$bestMatch) {
            return null;
        }

        return [
            'id'    => $bestMatch->id,
            'name'  => $bestMatch->name,
            'score' => round($bestScore * 100),
        ];
    }

    /**
     * Match an extracted tax percentage to a TaxRate record.
     */
    private function matchTaxRate(?float $percentage, $taxRates): ?int
    {
        if ($percentage === null) return null;

        foreach ($taxRates as $rate) {
            if (abs($rate->tax_rate - $percentage) < self::TAX_RATE_TOLERANCE) {
                return $rate->id;
            }
        }

        return null;
    }

    private function normalizeText(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s) ?? '';
        $s = preg_replace('/\s+/', ' ', $s) ?? '';
        return trim($s);
    }

    private function tokenize(string $normalized): array
    {
        if ($normalized === '') return [];
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn($t) => strlen($t) >= 2 && !in_array($t, self::STOPWORDS, true));
        return array_values(array_unique($tokens));
    }

    private function textSimilarity(string $a, string $b, array $tokensA, array $tokensB): float
    {
        if ($a === '' || $b === '') return 0.0;

        similar_text($a, $b, $charPct);

        $jaccard = 0.0;
        if (!empty($tokensA) && !empty($tokensB)) {
            $intersect = count(array_intersect($tokensA, $tokensB));
            $union     = count(array_unique(array_merge($tokensA, $tokensB)));
            $jaccard   = $union > 0 ? $intersect / $union : 0.0;
        }

        return max($charPct / 100, $jaccard);
    }

    private function resolveMediaType(UploadedFile $file): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        return match ($ext) {
            'pdf'          => 'application/pdf',
            'jpg', 'jpeg'  => 'image/jpeg',
            'png'          => 'image/png',
            default        => null,
        };
    }
}
