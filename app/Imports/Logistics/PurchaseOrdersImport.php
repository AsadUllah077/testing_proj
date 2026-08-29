<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsPurchaseOrder;
use App\Models\LogisticsPurchaseOrderItem;
use App\Models\LogisticsSupplierV2;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrdersImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows)
    {
        // Group rows by PO Number
        $grouped = $rows->groupBy('po_number');

        foreach ($grouped as $poNumber => $items) {
            if (empty($poNumber)) continue;

            $firstRow = $items->first();

            // Find supplier by name
            $supplier = LogisticsSupplierV2::where('company_name', 'like', '%' . trim($firstRow['supplier_name']) . '%')->first();
            
            if (!$supplier) {
                // You might want to skip or handle error. For now, we skip if supplier not found.
                continue;
            }

            DB::beginTransaction();
            try {
                // Calculate totals
                $subtotal = 0;
                $taxAmount = 0;

                foreach ($items as $itemRow) {
                    $qty = (float)($itemRow['quantity'] ?? 0);
                    $price = (float)($itemRow['price'] ?? 0);
                    $taxPct = (float)($itemRow['tax_percentage'] ?? 0);
                    
                    $lineSubtotal = $qty * $price;
                    $lineTax = ($lineSubtotal * $taxPct) / 100;
                    
                    $subtotal += $lineSubtotal;
                    $taxAmount += $lineTax;
                }

                // Create PO Header
                $po = LogisticsPurchaseOrder::create([
                    'company_id' => Auth::user()->company_id ?? 1,
                    'supplier_id' => $supplier->id,
                    'po_number' => $poNumber,
                    'order_date' => $this->transformDate($firstRow['order_date'] ?? null) ?? now(),
                    'expected_delivery_date' => $this->transformDate($firstRow['expected_delivery_date'] ?? null),
                    'currency' => $firstRow['currency'] ?? 'USD',
                    'payment_terms' => $firstRow['payment_terms'] ?? 'Net 30',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount,
                    'notes' => $firstRow['notes'] ?? null,
                    'status' => 'Draft',
                    'created_by' => Auth::id() ?? 1,
                ]);

                // Create PO Items
                foreach ($items as $itemRow) {
                    $qty = (float)($itemRow['quantity'] ?? 0);
                    $price = (float)($itemRow['price'] ?? 0);
                    $taxPct = (float)($itemRow['tax_percentage'] ?? 0);
                    
                    $lineSubtotal = $qty * $price;
                    $lineTax = ($lineSubtotal * $taxPct) / 100;

                    LogisticsPurchaseOrderItem::create([
                        'company_id' => Auth::user()->company_id ?? 1,
                        'logistics_purchase_order_id' => $po->id,
                        'sku' => $itemRow['sku'] ?? 'N/A',
                        'description' => $itemRow['description'] ?? null,
                        'hs_code' => $itemRow['hs_code'] ?? null,
                        'quantity' => $qty,
                        'weight_kg' => (float)($itemRow['weight_kg'] ?? 0),
                        'price' => $price,
                        'tax_percentage' => $taxPct,
                        'total' => $lineSubtotal + $lineTax,
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // Optionally log or skip
            }
        }
    }

    public function rules(): array
    {
        return [
            'po_number' => 'required',
            'supplier_name' => 'required',
            'order_date' => 'required',
            'sku' => 'required',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
        ];
    }

    private function transformDate($value)
    {
        try {
            if (empty($value)) return null;
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
