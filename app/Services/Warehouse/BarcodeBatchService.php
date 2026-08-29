<?php

namespace App\Services\Warehouse;

use App\Models\Barcode;
use App\Models\BarcodeBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;

class BarcodeBatchService
{
    public function validateSequenceRange(string $startSequence, string $endSequence): array
    {
        $start = $this->parseSequence($startSequence);
        $end = $this->parseSequence($endSequence);

        if ($start['prefix'] !== $end['prefix']) {
            throw new \DomainException('Start and end sequences must have the same prefix');
        }

        if ($start['number'] > $end['number']) {
            throw new \DomainException('Start sequence must be less than or equal to end sequence');
        }

        return ['start' => $start, 'end' => $end];
    }

    public function generate(
        string $containerId,
        ProductCategory $category,
        string $startSequence,
        string $endSequence,
        int $actorId,
        int $companyId
    ): array {
        $range = $this->validateSequenceRange($startSequence, $endSequence);
        $start = $range['start'];
        $end = $range['end'];
        $categoryPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category->name), 0, 3));

        return DB::transaction(function () use (
            $containerId,
            $category,
            $startSequence,
            $endSequence,
            $actorId,
            $companyId,
            $start,
            $end,
            $categoryPrefix
        ) {
            $generatedIds = [];
            $batch = BarcodeBatch::create([
                'batch_number' => (new BarcodeBatch)->generateBatchNumber(),
                'container_id' => $containerId,
                'category_id' => $category->id,
                'start_sequence' => $startSequence,
                'end_sequence' => $endSequence,
                'quantity' => $end['number'] - $start['number'] + 1,
                'generated_ids' => [],
                'generated_by' => $actorId,
                'company_id' => $companyId,
            ]);

            for ($number = $start['number']; $number <= $end['number']; $number++) {
                $sequenceNumber = $start['prefix'].str_pad($number, strlen((string) $start['number']), '0', STR_PAD_LEFT);
                $uniqueId = strtoupper($containerId.'-'.$categoryPrefix.'-'.$sequenceNumber);
                $baseUrl = rtrim(env('BARCODE_BASE_URL', config('app.url')), '/');
                $barcodeImagePath = $this->generateBarcodeImage($baseUrl.'/'.$uniqueId, $uniqueId);

                $barcode = Barcode::create([
                    'unique_id' => $uniqueId,
                    'container_id' => $containerId,
                    'category_id' => $category->id,
                    'sequence_number' => $sequenceNumber,
                    'barcode_data' => $uniqueId,
                    'barcode_image_path' => $barcodeImagePath,
                    'status' => 'active',
                    'generated_by' => $actorId,
                    'generated_at' => now(),
                    'company_id' => $companyId,
                ]);

                $product = Product::create([
                    'unique_id' => $uniqueId,
                    'title' => '',
                    'product_details' => 'Auto-generated from barcode',
                    'category_id' => $category->id,
                    'status' => 'Inactive',
                    'company_id' => $companyId,
                    'created_by' => $actorId,
                    'product_id_value' => $uniqueId,
                    'last_action' => 'Barcode Generated',
                ]);

                $this->recordGenerationHistory($product, $barcode, $category, $actorId, $companyId);
                $generatedIds[] = $uniqueId;
            }

            $batch->update(['generated_ids' => $generatedIds]);

            return ['batch' => $batch, 'generated_ids' => $generatedIds];
        });
    }

    /**
     * Compatibility extension point. Queue integration can later call generate()
     * without changing controllers or the generation rules.
     */
    public function queue(
        string $containerId,
        ProductCategory $category,
        string $startSequence,
        string $endSequence,
        int $actorId,
        int $companyId
    ): array {
        return $this->generate($containerId, $category, $startSequence, $endSequence, $actorId, $companyId);
    }

    public function retry(BarcodeBatch $batch, int $actorId): array
    {
        return $this->generate(
            $batch->container_id,
            $batch->category,
            $batch->start_sequence,
            $batch->end_sequence,
            $actorId,
            $batch->company_id
        );
    }

    public function cleanArtifacts(BarcodeBatch $batch): int
    {
        $deleted = 0;
        $barcodes = Barcode::whereIn('unique_id', $batch->generated_ids ?? [])->get();

        foreach ($barcodes as $barcode) {
            if ($barcode->barcode_image_path && Storage::disk('public')->exists($barcode->barcode_image_path)) {
                Storage::disk('public')->delete($barcode->barcode_image_path);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function parseSequence(string $sequence): array
    {
        preg_match('/^([A-Za-z]*)(\d+)$/', $sequence, $matches);

        if (count($matches) === 3) {
            return ['prefix' => $matches[1], 'number' => (int) $matches[2]];
        }

        return ['prefix' => '', 'number' => (int) $sequence];
    }

    private function generateBarcodeImage(string $data, ?string $uniqueId = null): ?string
    {
        try {
            $directory = 'barcodes/'.date('Y/m');
            Storage::disk('public')->makeDirectory($directory);

            $barcode = (new DNS2D)->getBarcodePNG($data, 'QRCODE', 8, 8);
            $slug = $uniqueId ? Str::slug($uniqueId) : Str::slug($data);
            $filename = $directory.'/'.$slug.'_'.time().'.png';
            Storage::disk('public')->put($filename, base64_decode($barcode));

            return $filename;
        } catch (\Exception $exception) {
            Log::error('Barcode generation failed: '.$exception->getMessage());

            return null;
        }
    }

    private function recordGenerationHistory(
        Product $product,
        Barcode $barcode,
        ProductCategory $category,
        int $actorId,
        int $companyId
    ): void {
        ProductHistory::create([
            'product_id' => $product->id,
            'action' => 'product_created',
            'old_value' => null,
            'new_value' => json_encode([
                'unique_id' => $product->unique_id,
                'title' => $product->title,
                'category_id' => $product->category_id,
                'category_name' => $category->name,
                'location' => $product->location,
                'status' => $product->status,
                'barcode_id' => $barcode->id,
                'barcode_unique_id' => $barcode->unique_id,
            ]),
            'notes' => 'Product created automatically from barcode generation',
            'changed_by' => $actorId,
            'company_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProductHistory::create([
            'product_id' => $product->id,
            'action' => 'barcode_generated',
            'old_value' => null,
            'new_value' => json_encode([
                'barcode_id' => $barcode->id,
                'barcode_unique_id' => $barcode->unique_id,
                'barcode_type' => $barcode->barcode_type,
                'container_id' => $barcode->container_id,
                'sequence_number' => $barcode->sequence_number,
            ]),
            'notes' => 'Barcode generated and attached to product',
            'changed_by' => $actorId,
            'company_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
