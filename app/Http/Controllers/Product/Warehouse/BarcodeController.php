<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Barcode;
use App\Models\ProductCategory;
use App\Services\Warehouse\BarcodeBatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BarcodeController extends Controller
{
    public function __construct(private readonly BarcodeBatchService $barcodeBatches) {}

    public function index(Request $request)
    {
        $categories = ProductCategory::where('company_id', auth('web')->user()->company_id)
            ->orWhereNull('company_id')
            ->get();

        $sortCol = $request->input('sort') === 'unique_id' ? 'unique_id' : 'created_at';
        $sortDir = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $barcodes = Barcode::with('category')
            ->where('company_id', auth('web')->user()->company_id)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->where('unique_id', 'like', "%{$search}%")
                        ->orWhere('container_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy($sortCol, $sortDir)
            ->paginate(in_array((int) $request->input('per_page'), [25, 50, 100, 200, 500]) ? (int) $request->input('per_page') : 100)
            ->withQueryString();

        return view('pages.products.warehouse.barcode.index', compact('categories', 'barcodes'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'container_id' => 'required|string|max:50',
            'category_id' => 'required|exists:product_categories,id',
            'start_sequence' => 'required|string|max:20',
            'end_sequence' => 'required|string|max:20',
        ]);

        try {
            $category = ProductCategory::findOrFail($request->category_id);
            $result = $this->barcodeBatches->generate(
                $request->container_id,
                $category,
                $request->start_sequence,
                $request->end_sequence,
                auth('web')->id(),
                auth('web')->user()->company_id
            );
            $generatedIds = $result['generated_ids'];

            return redirect()->route('warehouse.barcode')
                ->with('success', 'Successfully generated '.count($generatedIds).' barcodes')
                ->with('generated_ids', $generatedIds);

        } catch (\DomainException $e) {
            return redirect()->route('warehouse.barcode')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('warehouse.barcode')
                ->with('error', 'Failed to generate barcodes: '.$e->getMessage());
        }
    }

    // Add this method to update barcode status to 'used'
    public function markAsUsed(Barcode $barcode)
    {
        try {
            if ($barcode->status === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode is already marked as used',
                ], 400);
            }

            // Update barcode status
            $barcode->status = 'used';
            $barcode->save();

            // Also update the associated product if exists
            $product = \App\Models\Product::where('unique_id', $barcode->unique_id)->first();

            if ($product) {
                $oldStatus = $product->status;
                $product->status = 'Used';
                $product->last_action = 'Barcode Used';
                $product->save();

                // Create product history
                \App\Models\ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'barcode_used',
                    'old_value' => json_encode(['barcode_status' => 'active', 'product_status' => $oldStatus]),
                    'new_value' => json_encode([
                        'barcode_status' => 'used',
                        'product_status' => 'Used',
                        'barcode_id' => $barcode->id,
                        'barcode_unique_id' => $barcode->unique_id,
                    ]),
                    'notes' => 'Barcode marked as used',
                    'changed_by' => auth('web')->id(),
                    'company_id' => auth('web')->user()->company_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Barcode marked as used successfully',
                'data' => [
                    'barcode_id' => $barcode->id,
                    'unique_id' => $barcode->unique_id,
                    'status' => $barcode->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark barcode as used: '.$e->getMessage(),
            ], 500);
        }
    }

    // Update the destroy method to also delete from products table
    public function destroy(Barcode $barcode)
    {
        try {
            if ($barcode->status === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a barcode that has been used',
                ], 400);
            }

            // Find and delete associated product
            $product = \App\Models\Product::where('unique_id', $barcode->unique_id)->first();

            if ($product) {
                // Create history before deletion
                \App\Models\ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'product_deleted',
                    'old_value' => json_encode([
                        'unique_id' => $product->unique_id,
                        'title' => $product->title,
                        'barcode_id' => $barcode->id,
                    ]),
                    'new_value' => null,
                    'notes' => 'Product deleted along with barcode',
                    'changed_by' => auth('web')->id(),
                    'company_id' => auth('web')->user()->company_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $product->delete();
            }

            if ($barcode->barcode_image_path && Storage::disk('public')->exists($barcode->barcode_image_path)) {
                Storage::disk('public')->delete($barcode->barcode_image_path);
            }

            $barcode->delete();

            return response()->json([
                'success' => true,
                'message' => 'Barcode and associated product deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete barcode: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkMarkAsUsed(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:barcodes,id',
            ]);

            $barcodes = Barcode::whereIn('id', $request->ids)
                ->where('status', '!=', 'used')
                ->get();

            $count = $barcodes->count();
            $skipped = count($request->ids) - $count;
            $updatedProducts = 0;

            DB::beginTransaction();

            foreach ($barcodes as $barcode) {
                // Update barcode status
                $barcode->status = 'used';
                $barcode->save();

                // Update associated product
                $product = \App\Models\Product::where('unique_id', $barcode->unique_id)->first();

                if ($product) {
                    $oldStatus = $product->status;
                    $product->status = 'Used';
                    $product->last_action = 'Barcode Used';
                    $product->save();
                    $updatedProducts++;

                    // Create product history
                    \App\Models\ProductHistory::create([
                        'product_id' => $product->id,
                        'action' => 'barcode_used',
                        'old_value' => json_encode([
                            'barcode_status' => 'active',
                            'product_status' => $oldStatus,
                            'barcode_id' => $barcode->id,
                        ]),
                        'new_value' => json_encode([
                            'barcode_status' => 'used',
                            'product_status' => 'Used',
                            'barcode_id' => $barcode->id,
                            'barcode_unique_id' => $barcode->unique_id,
                        ]),
                        'notes' => 'Barcode marked as used via bulk action',
                        'changed_by' => auth('web')->id(),
                        'company_id' => auth('web')->user()->company_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $message = "Marked {$count} barcodes as used successfully";
            if ($skipped > 0) {
                $message .= ". Skipped {$skipped} barcodes that were already used.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
                'updated_products' => $updatedProducts,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark barcodes as used: '.$e->getMessage(),
            ], 500);
        }
    }

    // Update bulkDelete method to also delete products
    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:barcodes,id',
            ]);

            $barcodes = Barcode::whereIn('id', $request->ids)
                ->where('status', '!=', 'used')
                ->get();

            $count = $barcodes->count();
            $skipped = count($request->ids) - $count;
            $deletedProducts = 0;

            foreach ($barcodes as $barcode) {
                // Delete associated product
                $product = \App\Models\Product::where('unique_id', $barcode->unique_id)->first();
                if ($product) {
                    $product->delete();
                    $deletedProducts++;
                }

                if ($barcode->barcode_image_path && Storage::disk('public')->exists($barcode->barcode_image_path)) {
                    Storage::disk('public')->delete($barcode->barcode_image_path);
                }
                $barcode->delete();
            }

            $message = "Deleted {$count} barcodes and {$deletedProducts} associated products successfully";
            if ($skipped > 0) {
                $message .= ". Skipped {$skipped} barcodes that are in use.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
                'deleted_products' => $deletedProducts,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete barcodes: '.$e->getMessage(),
            ], 500);
        }
    }

    public function printBarcodes(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:barcodes,id',
        ]);

        $barcodes = Barcode::with('category')
            ->whereIn('id', $request->ids)
            ->get();

        if ($barcodes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No barcodes found to print',
            ], 404);
        }

        // Generate PDF with embedded barcodes
        $pdf = PDF::loadView('pages.products.warehouse.barcode.print-png', [
            'barcodes' => $barcodes,
            'company' => auth('web')->user()->company,
            'printed_at' => now()->format('Y-m-d H:i:s'),
            'printed_by' => auth('web')->user()->name,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Enable HTML5 parsing and disable DOMPDF's HTML5 parser for better compatibility
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isJavascriptEnabled' => false,
            'chroot' => public_path(),
        ]);

        // Return PDF for download
        return $pdf->download('barcodes_'.time().'.pdf');
    }

    public function printView(Request $request)
    {
        // Store selected IDs in session, then redirect to GET so the
        // settings panel can reload the page freely without losing IDs.
        session(['barcode_print_ids' => $request->ids ?? []]);

        return redirect()->route('warehouse.barcode.print-view.get');
    }

    public function printViewGet(Request $request)
    {
        $ids = session('barcode_print_ids', []);

        $barcodes = Barcode::with('category')
            ->whereIn('id', $ids)
            ->where('company_id', auth('web')->user()->company_id)
            ->get();

        return view('pages.products.warehouse.barcode.print', compact('barcodes'));
    }

    public function printSelectedPdf(Request $request)
    {
        $ids = $request->get('ids', '');
        $barcodeIds = $ids ? explode(',', $ids) : [];

        $barcodes = Barcode::with('category')
            ->whereIn('id', $barcodeIds)
            ->where('company_id', auth('web')->user()->company_id)
            ->get();

        if ($barcodes->isEmpty()) {
            return redirect()->route('warehouse.barcode')
                ->with('error', 'No barcodes found to print');
        }

        $pdf = PDF::loadView('pages.products.warehouse.barcode.print', [
            'barcodes' => $barcodes,
            'company' => auth('web')->user()->company,
            'printed_at' => now()->format('Y-m-d H:i:s'),
            'printed_by' => auth('web')->user()->name,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcodes_'.time().'.pdf');
    }

    public function scan($uniqueId)
    {
        $barcode = Barcode::with(['category', 'usable'])
            ->where('unique_id', $uniqueId)
            ->first();

        if (! $barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $barcode->id,
                'unique_id' => $barcode->unique_id,
                'container_id' => $barcode->container_id,
                'category' => $barcode->category->name ?? 'N/A',
                'status' => $barcode->status,
                'generated_at' => $barcode->generated_at->format('Y-m-d H:i:s'),
                'barcode_url' => $barcode->barcode_url,
            ],
        ]);
    }

    public function getBarcodeImage($uniqueId)
    {
        $barcode = Barcode::where('unique_id', $uniqueId)->first();

        if (! $barcode || ! $barcode->barcode_url) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($barcode->barcode_image_path));
    }

    public function exportPdf(Request $request)
    {
        $ids = $request->get('ids', '');
        $barcodeIds = $ids ? explode(',', $ids) : [];

        $query = Barcode::with('category')
            ->where('company_id', auth('web')->user()->company_id);

        if (! empty($barcodeIds)) {
            $query->whereIn('id', $barcodeIds);
        }

        $barcodes = $query->get();

        $pdf = PDF::loadView('pages.products.warehouse.barcode.export', [
            'barcodes' => $barcodes,
            'company' => auth('web')->user()->company,
            'exported_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('barcodes_export_'.time().'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $ids = $request->get('ids', '');
        $barcodeIds = $ids ? explode(',', $ids) : [];

        $query = Barcode::with('category')
            ->where('company_id', auth('web')->user()->company_id);

        if (! empty($barcodeIds)) {
            $query->whereIn('id', $barcodeIds);
        }

        $barcodes = $query->get();

        // Here you would implement Excel export using Maatwebsite/Excel
        return response()->json([
            'success' => true,
            'message' => 'Excel export would be generated here',
            'count' => $barcodes->count(),
        ]);
    }
}
