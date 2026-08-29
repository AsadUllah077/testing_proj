<?php

namespace App\Services\AI;

use App\Models\ChartOfAccount;
use App\Models\TaxRate;
use App\Models\Vendor;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Facades\Ai;

class DocumentProcessorService
{
    private const VENDOR_MATCH_THRESHOLD = 0.60;
    private const TAX_RATE_TOLERANCE = 0.5;

    /**
     * Process a document based on its type.
     */
    public function process(UploadedFile $file, string $type = 'invoice'): array
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
            $prompt = $this->buildPrompt($type);
            $extracted = Ai::extractJson($prompt, $base64, $mediaType);
            
            if (empty($extracted)) {
                return [
                    'success' => false,
                    'message' => 'AI could not extract structured data from this document.',
                ];
            }

            // Map extracted data to our database entities
            $results = $this->mapExtractedData($extracted, $type);

            return [
                'success' => true,
                'data' => $results,
                'type' => $type
            ];

        } catch (\Throwable $e) {
            Log::error('Document processing error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the prompt based on document type.
     */
    private function buildPrompt(string $type): string
    {
        if ($type === 'bank_statement') {
            return Prompts::bankStatementExtraction();
        }

        $coaList = $this->getCoaContext();
        $invoicePrompt = Prompts::invoiceExtraction();

        return "$invoicePrompt\n\nCOA CONTEXT (Suggest the most relevant account number):\n$coaList";
    }

    /**
     * Map extracted JSON to database entities.
     */
    private function mapExtractedData(array $extracted, string $type): array
    {
        if ($type === 'bank_statement') {
            return $extracted;
        }

        $taxRates = TaxRate::all();
        $entityType = $type === 'receipt' ? 'customer' : 'vendor';
        $vendorMatch = $this->matchEntity($extracted['vendor_name'] ?? null, $entityType);
        
        $rows = [];
        foreach (($extracted['line_items'] ?? []) as $item) {
            $taxId = $this->matchTaxRate($item['tax_percentage'] ?? null, $taxRates);
            $accountId = $this->matchAccount($item['suggested_account_code'] ?? null);

            $rows[] = [
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['unit_price'] ?? 0,
                'tax_id' => $taxId,
                'account_id' => $accountId,
                'total' => $item['total'] ?? 0,
            ];
        }

        return [
            'vendor_id' => $vendorMatch['id'] ?? null,
            'vendor_name' => $extracted['vendor_name'] ?? null,
            'invoice_no' => $extracted['invoice_no'] ?? null,
            'date' => $extracted['date'] ?? null,
            'total' => $extracted['total_amount'] ?? 0,
            'rows' => $rows,
        ];
    }

    /**
     * Get a condensed list of Chart of Accounts for AI context.
     */
    private function getCoaContext(): string
    {
        return ChartOfAccount::forCompany(loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id)
            ->where('type', 'Account')
            ->select('account_number', 'name', 'category')
            ->get()
            ->map(fn($a) => "{$a->account_number}: {$a->name} ({$a->category})")
            ->implode("\n");
    }

    /**
     * Match an account number to an ID.
     */
    private function matchAccount(?string $accountNumber): ?int
    {
        if (empty($accountNumber)) return null;
        
        if (preg_match('/^(\d+)/', $accountNumber, $matches)) {
            $accountNumber = $matches[1];
        }
        
        return ChartOfAccount::forCompany(loggedCompanyId()['company_id'] ?? auth('web')->user()?->company_id)
            ->where('account_number', $accountNumber)
            ->value('id');
    }

    /**
     * Fuzzy-match vendor or customer.
     */
    private function matchEntity(?string $name, string $type = 'vendor'): ?array
    {
        if (empty($name)) return null;

        $model = $type === 'vendor' ? Vendor::class : Customer::class;
        $entities = $model::all(['id', 'name']);

        $bestMatch = null;
        $bestScore = 0;

        foreach ($entities as $entity) {
            similar_text(strtolower($name), strtolower($entity->name), $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $entity;
            }
        }

        return ($bestScore > 60) ? ['id' => $bestMatch->id, 'name' => $bestMatch->name] : null;
    }

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

    private function resolveMediaType(UploadedFile $file): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };
    }
}
