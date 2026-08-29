<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Barcode;
use App\Models\Product;
use App\Models\ProductScanLog;
use App\Models\WarehouseLocation;
use App\Services\Warehouse\InventoryMovementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductScanController extends Controller
{
    public function __construct(private readonly InventoryMovementService $inventoryMovements) {}

    /**
     * Location A → Photography  (Yellow)
     * Location B → Warehouse#2  (Green)
     */
    const LOCATION_MAP = [
        'A' => ['location_name' => 'Photography', 'color' => 'Yellow'],
        'B' => ['location_name' => 'Warehouse#2',  'color' => 'Green'],
    ];

    // ─────────────────────────────────────────────
    // Scan Pages
    // ─────────────────────────────────────────────

    /** GET /warehouse/scan/location-a */
    public function showA()
    {
        $companyId = auth()->user()->company_id;
        $locationRecord = WarehouseLocation::where('name', 'Photography')
            ->where('company_id', $companyId)
            ->first();

        return view('pages.products.warehouse.scan.location-a', [
            'location' => 'A',
            'color' => 'Yellow',
            'locationName' => $locationRecord?->name ?? 'Photography',
            'locationMissing' => ! $locationRecord,
        ]);
    }

    /** GET /warehouse/scan/location-b */
    public function showB()
    {
        $companyId = auth()->user()->company_id;
        $locationRecord = WarehouseLocation::where('name', 'Warehouse#2')
            ->where('company_id', $companyId)
            ->first();

        return view('pages.products.warehouse.scan.location-b', [
            'location' => 'B',
            'color' => 'Green',
            'locationName' => $locationRecord?->name ?? 'Warehouse#2',
            'locationMissing' => ! $locationRecord,
        ]);
    }

    // ─────────────────────────────────────────────
    // Process Scan (AJAX POST)
    // ─────────────────────────────────────────────

    /** POST /warehouse/scan */
    public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:255',
            'location' => 'required|in:A,B',
        ]);

        $barcodeValue = trim($request->barcode);

        // Strip URL prefix if present
        $urlPrefix = 'https://erp.archiwiz.com/';
        if (str_starts_with($barcodeValue, $urlPrefix)) {
            $barcodeValue = substr($barcodeValue, strlen($urlPrefix));
        }

        $location = $request->location;
        $mapEntry = self::LOCATION_MAP[$location];
        $color = $mapEntry['color'];
        $locationName = $mapEntry['location_name'];
        $companyId = auth()->user()->company_id;

        // Resolve warehouse location record from DB
        $warehouseLocation = WarehouseLocation::where('name', $locationName)
            ->where('company_id', $companyId)
            ->first();

        // ── Try to resolve the product ──────────────────────
        $product = null;

        // 1. Check barcodes table by unique_id
        $barcodeRecord = Barcode::where('unique_id', $barcodeValue)
            ->where('company_id', $companyId)
            ->first();

        if ($barcodeRecord && $barcodeRecord->used_for_type === 'product' && $barcodeRecord->used_for_id) {
            $product = Product::find($barcodeRecord->used_for_id);
        }

        // 2. Fallback: match product directly by unique_id
        if (! $product) {
            $product = Product::where('unique_id', $barcodeValue)
                ->where('company_id', $companyId)
                ->first();
        }

        // ── Save scan log ────────────────────────────────────
        $scanLog = ProductScanLog::create([
            'barcode' => $barcodeValue,
            'location' => $location,
            'color' => $color,
            'warehouse_location_name' => $warehouseLocation?->name ?? $locationName,
            'scanned_at' => Carbon::now(),
            'product_id' => $product?->id,
            'company_id' => $companyId,
            'scanned_by' => auth()->id(),
        ]);

        // ── Update product location ─────────────────────────
        if ($product && $warehouseLocation) {
            $this->inventoryMovements->moveProduct(
                $product,
                $warehouseLocation->id,
                auth()->id(),
                recordHistory: false
            );
        }

        return response()->json([
            'success' => true,
            'barcode' => $barcodeValue,
            'location' => $location,
            'color' => $color,
            'warehouse_location' => $warehouseLocation?->name ?? $locationName,
            'scanned_at' => $scanLog->scanned_at->format('h:i:s A'),
            'product' => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'unique_id' => $product->unique_id,
                'url' => route('warehouse.products.show', $product->id),
            ] : null,
            'message' => $product
                ? "✅ Barcode scanned — Product found: {$product->title}"
                : '⚠️ Barcode scanned — No matching product found in system',
            'type' => $product ? 'success' : 'warning',
        ]);
    }

    // ─────────────────────────────────────────────
    // Scan Logs Index
    // ─────────────────────────────────────────────

    /** GET /warehouse/scan/logs */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = ProductScanLog::with(['product', 'scannedBy'])
            ->where('company_id', $companyId)
            ->latest('scanned_at');

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        if ($request->filled('barcode')) {
            $query->where('barcode', 'like', '%'.$request->barcode.'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scanned_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scanned_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 50);
        $logs = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.warehouse.scan.partials._logs_table', compact('logs'))->render(),
                'pagination' => $logs->links()->render(),
            ]);
        }

        return view('pages.products.warehouse.scan.logs', compact('logs'));
    }
}
