<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use Illuminate\Support\Collection;

class ExceptionService
{
    public function detectForProducts(Collection $products): Collection
    {
        return $products->flatMap(function (Product $product) {
            $exceptions = [];

            if (! $product->category_id) {
                $exceptions[] = $this->make($product, 'Missing category');
            }
            if (! $product->warehouse_location_id) {
                $exceptions[] = $this->make($product, 'Missing warehouse location');
            }
            if (! $product->work_flow_stage_id) {
                $exceptions[] = $this->make($product, 'Missing workflow stage');
            }
            if (! $product->disposition_id) {
                $exceptions[] = $this->make($product, 'Missing disposition');
            }
            if ((int) $product->manual_status === 1 && (int) $product->count <= 0) {
                $exceptions[] = $this->make($product, 'Manual product count is zero');
            }
            if ((int) $product->sold_status === 1 && (int) $product->scrap_status === 1) {
                $exceptions[] = $this->make($product, 'Product is marked both sold and scrap');
            }
            if ((int) $product->manual_status === 1 && $product->raw_sold_units > max(0, (int) $product->count)) {
                $exceptions[] = $this->make($product, 'Sold quantity is greater than product count');
            }
            if (! $product->assigned_to && in_array($product->reporting_status, ['not_ready', 'issued'], true)) {
                $exceptions[] = $this->make($product, 'Operational product has no technician');
            }

            $failedDisposition = in_array(
                strtolower((string) $product->disposition?->name),
                ['fail', 'failed', 'rejected'],
                true
            );
            if ($product->reporting_status === 'ready' && $failedDisposition) {
                $exceptions[] = $this->make($product, 'Ready for Sale conflicts with failed disposition');
            }

            return $exceptions;
        })->values();
    }

    public function assign(array $exception, int $assigneeId): array
    {
        return array_merge($exception, [
            'assignee_id' => $assigneeId,
            'status' => 'assigned',
        ]);
    }

    public function acknowledge(array $exception, int $actorId): array
    {
        return array_merge($exception, [
            'acknowledged_by' => $actorId,
            'acknowledged_at' => now(),
            'status' => 'acknowledged',
        ]);
    }

    public function resolve(array $exception, int $actorId, ?string $notes = null): array
    {
        return array_merge($exception, [
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
            'status' => 'resolved',
        ]);
    }

    public function escalate(array $exception, int $assigneeId, ?string $reason = null): array
    {
        return array_merge($exception, [
            'assignee_id' => $assigneeId,
            'escalation_reason' => $reason,
            'escalated_at' => now(),
            'status' => 'escalated',
        ]);
    }

    private function make(Product $product, string $message): array
    {
        return [
            'product_id' => $product->id,
            'unique_id' => $product->unique_id,
            'title' => $product->title,
            'message' => $message,
            'age_days' => $product->age_days,
        ];
    }
}
