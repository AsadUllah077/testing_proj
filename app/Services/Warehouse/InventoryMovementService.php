<?php

namespace App\Services\Warehouse;

use App\Models\Employee;
use App\Models\Part;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductIssue;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    public function receivePart(
        Part $part,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Part {
        return DB::transaction(function () use ($part, $quantity, $reason, $referenceType, $referenceId) {
            $part->addStock($quantity, $reason, $referenceType, $referenceId);

            return $part->refresh();
        });
    }

    public function moveProduct(
        Product $product,
        int $locationId,
        int $actorId,
        ?string $notes = null,
        bool $recordHistory = true
    ): Product {
        return DB::transaction(function () use ($product, $locationId, $actorId, $notes, $recordHistory) {
            $oldLocationId = $product->warehouse_location_id;

            $attributes = ['warehouse_location_id' => $locationId];
            if ($recordHistory) {
                $attributes['last_action'] = 'Location changed';
            }
            $product->update($attributes);

            if ($recordHistory) {
                $product->histories()->create([
                    'action' => 'location_change',
                    'field' => 'warehouse_location_id',
                    'old_value' => $oldLocationId,
                    'new_value' => $locationId,
                    'notes' => $notes,
                    'changed_by' => $actorId,
                    'company_id' => $product->company_id,
                ]);
            }

            return $product->refresh();
        });
    }

    public function issueToEmployee(
        Product $product,
        ?Employee $employee,
        int $quantity,
        array $issueData,
        int $actorId,
        int $companyId
    ): ProductIssue {
        return DB::transaction(function () use ($product, $employee, $quantity, $issueData, $actorId, $companyId) {
            $employeeName = $employee?->full_name ?: 'No employee';

            if ((int) $product->manual_status === 1) {
                if ($product->count < $quantity) {
                    throw new \DomainException(
                        "Product {$product->unique_id} only has {$product->count} units in stock."
                    );
                }

                $oldCount = $product->count;
                $product->count -= $quantity;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'issued_to_employee',
                    'field' => 'count',
                    'old_value' => $oldCount,
                    'new_value' => $product->count,
                    'notes' => "{$quantity} unit(s) issued to {$employeeName}",
                    'changed_by' => $actorId,
                    'company_id' => $companyId,
                ]);
            } else {
                $quantity = 1;
                $oldStatus = $product->status;
                $product->status = 'Issued';
                $product->assigned_to = $employee?->id;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'issued_to_employee',
                    'field' => 'status',
                    'old_value' => $oldStatus,
                    'new_value' => 'Issued',
                    'notes' => "Product issued to {$employeeName}",
                    'changed_by' => $actorId,
                    'company_id' => $companyId,
                ]);
            }

            return ProductIssue::create([
                'product_id' => $product->id,
                'employee_id' => $employee?->id,
                'issued_by' => $actorId,
                'quantity' => $quantity,
                'reference' => $issueData['reference'] ?? null,
                'issue_date' => $issueData['issue_date'],
                'notes' => $issueData['notes'] ?? null,
                'company_id' => $companyId,
            ]);
        });
    }

    public function transferPart(Part $part, string $toLocation, int $quantity, ?string $reason = null): Part
    {
        if ($part->quantity < $quantity) {
            throw new \DomainException('Insufficient stock for transfer');
        }

        if ($part->location === $toLocation) {
            throw new \DomainException('Destination location cannot be the same as source location');
        }

        return DB::transaction(function () use ($part, $toLocation, $quantity, $reason) {
            $fromLocation = $part->location;
            $beforeQuantity = $part->quantity;

            $part->location = $toLocation;
            $part->save();

            $part->movements()->create([
                'movement_type' => 'TRANSFER',
                'quantity' => $quantity,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $part->quantity,
                'reason' => $reason ?? 'Stock transfer',
                'created_by' => auth('web')->id(),
                'company_id' => $part->company_id,
            ]);

            return $part->refresh();
        });
    }

    public function returnIssue(ProductIssue $issue, int $actorId, int $companyId): ProductIssue
    {
        return DB::transaction(function () use ($issue, $actorId, $companyId) {
            $issue->loadMissing(['product', 'employee']);
            $product = $issue->product;
            $employeeName = $issue->employee?->full_name ?: 'No employee';

            if ((int) $product->manual_status === 1) {
                $issueQuantity = $issue->quantity;
                $oldCount = $product->count;
                $product->count += $issueQuantity;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'product_returned',
                    'field' => 'count',
                    'old_value' => $oldCount,
                    'new_value' => $product->count,
                    'notes' => "Returned {$issueQuantity} unit(s) by {$employeeName}",
                    'changed_by' => $actorId,
                    'company_id' => $companyId,
                ]);
            } else {
                $oldStatus = $product->status;
                $product->status = 'Ready for Sale';
                $product->assigned_to = null;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'product_returned',
                    'field' => 'status',
                    'old_value' => $oldStatus,
                    'new_value' => 'Ready for Sale',
                    'notes' => "Returned by {$employeeName}",
                    'changed_by' => $actorId,
                    'company_id' => $companyId,
                ]);
            }

            $issue->update([
                'status' => 'Returned',
                'return_date' => now(),
            ]);

            return $issue->refresh();
        });
    }

    public function adjustPart(Part $part, int $newQuantity, string $reason): Part
    {
        return DB::transaction(function () use ($part, $newQuantity, $reason) {
            $before = (int) $part->quantity;
            $part->quantity = $newQuantity;
            $part->save();

            $part->movements()->create([
                'movement_type' => 'ADJUSTMENT',
                'quantity' => abs($newQuantity - $before),
                'before_quantity' => $before,
                'after_quantity' => $newQuantity,
                'reason' => $reason,
                'created_by' => auth('web')->id(),
                'company_id' => $part->company_id,
            ]);

            return $part->refresh();
        });
    }

    public function reconcilePart(Part $part): array
    {
        $movementBalance = (int) $part->movements()
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN movement_type = 'IN' THEN quantity
                        WHEN movement_type = 'OUT' THEN -quantity
                        WHEN movement_type = 'ADJUSTMENT' THEN after_quantity - before_quantity
                        ELSE 0
                    END
                ), 0) AS balance
            ")
            ->value('balance');

        return [
            'part_id' => $part->id,
            'recorded_quantity' => (int) $part->quantity,
            'movement_balance' => $movementBalance,
            'difference' => (int) $part->quantity - $movementBalance,
            'is_reconciled' => (int) $part->quantity === $movementBalance,
        ];
    }
}
