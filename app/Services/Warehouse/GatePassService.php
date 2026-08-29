<?php

namespace App\Services\Warehouse;

use App\Models\GatePass;
use App\Models\GatePassItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class GatePassService
{
    public function reserveProduct(
        GatePass $gatePass,
        Product $product,
        int $quantity,
        array $snapshot,
        int $actorId,
        mixed $historyOldValue = null,
        bool $checkOtherReservations = false
    ): GatePassItem {
        if ((int) $product->manual_status === 1) {
            if ($quantity < 1) {
                throw new \DomainException("Quantity must be at least 1 for product {$product->unique_id}.");
            }

            if ($quantity > $product->count) {
                throw new \DomainException(
                    "Requested quantity ({$quantity}) exceeds available stock ({$product->count}) for product {$product->unique_id}."
                );
            }
        } elseif ($checkOtherReservations && $this->isReservedOnAnotherOpenGatePass($product, $gatePass)) {
            throw new \DomainException("Product {$product->unique_id} is already in another gate pass.");
        }

        if ((int) $product->manual_status === 1) {
            $newCount = $product->count - $quantity;
            $product->update([
                'count' => $newCount,
                'sold_status' => $newCount === 0 ? 1 : 0,
                'status' => $newCount === 0 ? 'used' : $product->status,
            ]);
            $historyNote = "Added to gate pass {$gatePass->gate_pass_id} with quantity {$quantity}. Remaining stock: {$newCount}";
            $historyNewValue = json_encode([
                'gate_pass_id' => $gatePass->gate_pass_id,
                'quantity' => $quantity,
                'remaining_count' => $newCount,
            ]);
        } else {
            $product->update(['sold_status' => 1, 'status' => 'used']);
            $historyNote = "Product added to gate pass {$gatePass->gate_pass_id} and marked as used";
            $historyNewValue = json_encode([
                'gate_pass_id' => $gatePass->gate_pass_id,
                'status' => 'used',
            ]);
        }

        $item = GatePassItem::create([
            'gate_pass_id' => $gatePass->id,
            'product_id' => $product->id,
            'unique_id' => $snapshot['unique_id'],
            'product_name' => $snapshot['product_name'],
            'category_name' => $snapshot['category_name'],
            'quantity' => $quantity,
            'product_details' => $product->toArray(),
            'company_id' => $gatePass->company_id,
        ]);

        $product->histories()->create([
            'action' => 'added_to_gate_pass',
            'field' => 'gate_pass',
            'old_value' => $historyOldValue,
            'new_value' => $historyNewValue,
            'notes' => $historyNote,
            'changed_by' => $actorId,
            'company_id' => $gatePass->company_id,
        ]);

        return $item;
    }

    public function releaseItem(
        GatePassItem $item,
        int $actorId,
        string $action = 'removed_from_gate_pass',
        ?string $reason = null
    ): void {
        $item->loadMissing(['gatePass', 'product']);
        $product = $item->product;

        if (! $product) {
            return;
        }

        $oldStatus = $product->status;

        if ((int) $product->manual_status === 1) {
            $newCount = $product->count + ($item->quantity ?? 1);
            $product->update([
                'count' => $newCount,
                'sold_status' => 0,
                'status' => 'available',
            ]);
            $newValue = ['gate_pass_id' => null, 'status' => 'available', 'count' => $newCount];
            $rejectionNote = "Gate pass rejected, restored quantity: {$item->quantity}. New count: {$newCount}. Reason: {$reason}";
        } else {
            $product->update([
                'sold_status' => 0,
                'status' => 'available',
            ]);
            $newValue = ['gate_pass_id' => null, 'status' => 'available'];
            $rejectionNote = "Gate pass rejected, product marked as available. Reason: {$reason}";
        }

        $notes = match ($action) {
            'gate_pass_rejected' => $rejectionNote,
            'returned_from_gate_pass' => "Product returned from gate pass {$item->gatePass->gate_pass_id} and marked as available",
            default => "Product removed from gate pass {$item->gatePass->gate_pass_id} and marked as available",
        };

        $product->histories()->create([
            'action' => $action,
            'field' => 'gate_pass',
            'old_value' => json_encode([
                'gate_pass_id' => $item->gatePass->gate_pass_id,
                'status' => $oldStatus,
            ]),
            'new_value' => json_encode($newValue),
            'notes' => $notes,
            'changed_by' => $actorId,
            'company_id' => $item->gatePass->company_id,
        ]);
    }

    public function approveQc(GatePass $gatePass, ?string $remarks, int $actorId): GatePass
    {
        if (! $gatePass->canApproveQC()) {
            throw new \DomainException('QC approval is not available for this gate pass.');
        }

        return DB::transaction(function () use ($gatePass, $remarks, $actorId) {
            $gatePass->update([
                'qc_remarks' => $remarks,
                'qc_approved_at' => now(),
                'status' => 'SECURITY_APPROVAL_PENDING',
            ]);

            $this->recordApprovalHistory($gatePass, 'qc_approved', 'gate_pass_qc', 'QC approved for gate pass', $actorId);

            return $gatePass->refresh();
        });
    }

    public function approveSecurity(GatePass $gatePass, string $inchargeName, int $actorId): GatePass
    {
        if (! $gatePass->canApproveSecurity()) {
            throw new \DomainException('Security approval is not available for this gate pass.');
        }

        return DB::transaction(function () use ($gatePass, $inchargeName, $actorId) {
            $gatePass->update([
                'security_incharge_name' => $inchargeName,
                'security_approved_at' => now(),
                'status' => 'QC_AND_SECURITY_APPROVED',
            ]);

            $this->recordApprovalHistory(
                $gatePass,
                'security_approved',
                'gate_pass_security',
                'Security approved for gate pass',
                $actorId
            );

            return $gatePass->refresh();
        });
    }

    public function reject(GatePass $gatePass, string $reason, int $actorId): GatePass
    {
        if ($gatePass->isFullyApproved()) {
            throw new \DomainException('Cannot reject an approved gate pass.');
        }

        return DB::transaction(function () use ($gatePass, $reason, $actorId) {
            $gatePass->update([
                'status' => 'REJECTED',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'rejected_by' => $actorId,
            ]);

            $gatePass->loadMissing('items.product');
            foreach ($gatePass->items as $item) {
                $this->releaseItem($item, $actorId, 'gate_pass_rejected', $reason);
            }

            return $gatePass->refresh();
        });
    }

    public function revise(GatePass $gatePass, array $attributes): GatePass
    {
        return DB::transaction(function () use ($gatePass, $attributes) {
            $gatePass->update($attributes);

            return $gatePass->refresh();
        });
    }

    public function dispatch(GatePass $gatePass): GatePass
    {
        if (! $gatePass->isFullyApproved()) {
            throw new \DomainException('Gate pass must be fully approved before dispatch.');
        }

        return $gatePass;
    }

    private function isReservedOnAnotherOpenGatePass(Product $product, GatePass $gatePass): bool
    {
        return GatePassItem::where('product_id', $product->id)
            ->whereHas('gatePass', function ($query) use ($gatePass) {
                $query->whereNotIn('status', ['QC_AND_SECURITY_APPROVED', 'REJECTED'])
                    ->where('id', '!=', $gatePass->id);
            })
            ->exists();
    }

    private function recordApprovalHistory(
        GatePass $gatePass,
        string $action,
        string $field,
        string $notes,
        int $actorId
    ): void {
        $gatePass->loadMissing('items.product');

        foreach ($gatePass->items as $item) {
            if ($item->product) {
                $item->product->histories()->create([
                    'action' => $action,
                    'field' => $field,
                    'old_value' => null,
                    'new_value' => json_encode([
                        'gate_pass_id' => $gatePass->gate_pass_id,
                        'status' => 'used',
                    ]),
                    'notes' => $notes,
                    'changed_by' => $actorId,
                    'company_id' => $gatePass->company_id,
                ]);
            }
        }
    }
}
