<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsPurchaseOrder;
use App\Models\LogisticsPurchaseOrderItem;
use App\Models\LogisticsPurchaseOrderAttachment;
use App\Models\LogisticsSupplierV2;
use App\Imports\Logistics\PurchaseOrdersImport;
use App\Exports\Logistics\PurchaseOrdersTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Validators\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = LogisticsPurchaseOrder::with('supplier')
            ->when($request->q, function ($query, $q) {
                $query->where('po_number', 'like', "%{$q}%")
                    ->orWhereHas('supplier', function ($sq) use ($q) {
                        $sq->where('company_name', 'like', "%{$q}%");
                    });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                $query->whereDate('order_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                $query->whereDate('order_date', '<=', $toDate);
            })
            ->latest()
            ->paginate(20);

        $suppliers = LogisticsSupplierV2::select('id', 'company_name')->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.purchase_orders.partials.table', compact('orders'))->render(),
                'pagination' => $orders->links()->toHtml(),
            ]);
        }

        return view('pages.products.logistics.purchase_orders.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        $suppliers = LogisticsSupplierV2::all();
        $po_number = 'LPO-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        return view('pages.products.logistics.purchase_orders.create', compact('suppliers', 'po_number'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:logistics_suppliers_v2,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'required|string',
            'payment_terms' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $tax_amount = 0;

            foreach ($request->items as $item) {
                $line_total = $item['quantity'] * $item['price'];
                $item_tax = ($line_total * ($item['tax_percentage'] ?? 0)) / 100;
                $subtotal += $line_total;
                $tax_amount += $item_tax;
            }

            $order = LogisticsPurchaseOrder::create([
                'company_id' => session('company_id') ?? 1, // Fallback for now
                'supplier_id' => $request->supplier_id,
                'po_number' => $request->po_number,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'currency' => $request->currency,
                'payment_terms' => $request->payment_terms,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'total_amount' => $subtotal + $tax_amount,
                'notes' => $request->notes,
                'created_by' => auth('web')->id() ?? 1,
            ]);

            foreach ($request->items as $item) {
                $line_total = $item['quantity'] * $item['price'];
                $item_tax = ($line_total * ($item['tax_percentage'] ?? 0)) / 100;

                LogisticsPurchaseOrderItem::create([
                    'logistics_purchase_order_id' => $order->id,
                    'sku' => $item['sku'],
                    'description' => $item['description'],
                    'hs_code' => $item['hs_code'],
                    'quantity' => $item['quantity'],
                    'weight_kg' => $item['weight_kg'] ?? 0,
                    'price' => $item['price'],
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'total' => $line_total + $item_tax,
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('logistics/po_attachments', 'public');
                    LogisticsPurchaseOrderAttachment::create([
                        'logistics_purchase_order_id' => $order->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Order created successfully', 'redirect' => route('logistics.purchase-orders.index')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(LogisticsPurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items', 'attachments', 'creator']);
        return view('pages.products.logistics.purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(LogisticsPurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items', 'attachments']);
        $suppliers = LogisticsSupplierV2::all();
        return view('pages.products.logistics.purchase_orders.edit', compact('purchaseOrder', 'suppliers'));
    }

    public function update(Request $request, LogisticsPurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'supplier_id' => 'required|exists:logistics_suppliers_v2,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'required|string',
            'payment_terms' => 'required|string',
            'status' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $tax_amount = 0;

            foreach ($request->items as $item) {
                $line_total = $item['quantity'] * $item['price'];
                $item_tax = ($line_total * ($item['tax_percentage'] ?? 0)) / 100;
                $subtotal += $line_total;
                $tax_amount += $item_tax;
            }

            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'currency' => $request->currency,
                'payment_terms' => $request->payment_terms,
                'status' => $request->status,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'total_amount' => $subtotal + $tax_amount,
                'notes' => $request->notes,
            ]);

            // Clear old items and recreate
            $purchaseOrder->items()->delete();

            foreach ($request->items as $item) {
                $line_total = $item['quantity'] * $item['price'];
                $item_tax = ($line_total * ($item['tax_percentage'] ?? 0)) / 100;

                LogisticsPurchaseOrderItem::create([
                    'logistics_purchase_order_id' => $purchaseOrder->id,
                    'sku' => $item['sku'],
                    'description' => $item['description'],
                    'hs_code' => $item['hs_code'],
                    'quantity' => $item['quantity'],
                    'weight_kg' => $item['weight_kg'] ?? 0,
                    'price' => $item['price'],
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'total' => $line_total + $item_tax,
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('logistics/po_attachments', 'public');
                    LogisticsPurchaseOrderAttachment::create([
                        'logistics_purchase_order_id' => $purchaseOrder->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order updated successfully',
                'redirect' => route('logistics.purchase-orders.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(LogisticsPurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            // Delete files from storage
            foreach ($purchaseOrder->attachments as $attachment) {
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }

            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteAttachment($id)
    {
        try {
            $attachment = LogisticsPurchaseOrderAttachment::findOrFail($id);
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
            return response()->json(['success' => true, 'message' => 'Attachment deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
    public function downloadTemplate()
    {
        return Excel::download(new PurchaseOrdersTemplateExport, 'purchase_orders_import_template.xlsx');
    }

    public function importPurchaseOrders(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            Excel::import(new PurchaseOrdersImport, $request->file('file'));
            return redirect()->back()->with('success', 'Purchase Orders imported successfully!');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            return redirect()->back()->with('error', 'Import failed: ' . implode(' | ', $errors));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import error: ' . $e->getMessage());
        }
    }
}
