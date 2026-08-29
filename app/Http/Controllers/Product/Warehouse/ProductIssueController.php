<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use App\Models\ProductIssue;
use App\Services\Warehouse\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductIssueController extends Controller
{
    public function __construct(private readonly InventoryMovementService $inventoryMovements) {}

    public function index(Request $request)
    {
        $query = ProductIssue::with(['product', 'employee', 'issuedBy'])->latest();

        $status = $request->get('status', 'All');
        $category = $request->get('category');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');

        if ($status !== 'All') {
            $query->where('status', $status);
        }

        if ($category) {
            $query->whereHas('product', fn ($product) => $product->where('category_id', $category));
        }

        if ($dateFrom) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('issue_date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {

                $q->where('reference', 'LIKE', "%{$search}%")
                    ->orWhereHas('product', function ($sq) use ($search) {
                        $sq->where('unique_id', 'LIKE', "%{$search}%")
                            ->orWhere('title', 'LIKE', "%{$search}%");
                    })->orWhereHas('employee', function ($sq) use ($search) {
                        $sq->where('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $issues = $query->paginate($request->get('per_page', 20))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.warehouse.product-issue.partials.table', compact('issues'))->render(),
                'pagination' => $issues->links()->toHtml(),
            ]);
        }

        $categories = ProductCategory::select('id', 'name')->orderBy('name')->get();

        return view('pages.products.warehouse.product-issue.index', compact('issues', 'categories'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        $products = $this->availableProductsForIssue($companyId)
            ->get(['id', 'unique_id', 'title', 'manual_status', 'count']);

        $employees = Employee::where('company_id', $companyId)
            ->whereIn('status', ['Active', 'active'])
            ->get(['id', 'full_name']);

        return view('pages.products.warehouse.product-issue.create', compact('products', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'employee_id' => 'nullable|exists:employees,id',
            'reference' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $product = Product::where('company_id', auth()->user()->company_id)
                ->findOrFail($request->product_id);
            $employee = $request->filled('employee_id') ? Employee::findOrFail($request->employee_id) : null;
            $userId = auth()->id();
            $companyId = auth()->user()->company_id;
            $quantity = $request->quantity ?? 1;

            if ($message = $this->issueAvailabilityMessage($product, $quantity)) {
                return response()->json(['success' => false, 'message' => $message]);
            }

            $this->inventoryMovements->issueToEmployee(
                $product,
                $employee,
                $quantity,
                [
                    'reference' => $request->reference,
                    'issue_date' => $request->issue_date,
                    'notes' => $request->notes,
                ],
                $userId,
                $companyId
            );

            return response()->json([
                'success' => true,
                'message' => 'Product '.$product->unique_id.' issued successfully'.($employee ? ' to '.$employee->full_name : '').'.',
            ]);

        } catch (\Exception $e) {
            Log::error('ProductIssueController@store Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to issue product: '.$e->getMessage()]);
        }
    }

    public function edit(ProductIssue $productIssue)
    {
        $companyId = auth()->user()->company_id;

        $products = $this->availableProductsForIssue($companyId, $productIssue)
            ->get(['id', 'unique_id', 'title', 'manual_status', 'count']);

        $employees = Employee::where('company_id', $companyId)
            ->whereIn('status', ['Active', 'active'])
            ->get(['id', 'full_name']);

        return view('pages.products.warehouse.product-issue.edit', compact('productIssue', 'products', 'employees'));
    }

    public function update(Request $request, ProductIssue $productIssue)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'employee_id' => 'nullable|exists:employees,id',
            'reference' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $oldProductId = $productIssue->product_id;
            $newProductId = $request->product_id;
            $oldEmployeeId = $productIssue->employee_id;
            $newEmployeeId = $request->filled('employee_id') ? $request->employee_id : null;
            $newEmployee = $newEmployeeId ? Employee::find($newEmployeeId) : null;
            $newEmployeeName = $this->employeeLabel($newEmployee);
            $companyId = auth()->user()->company_id;
            $userId = auth()->id();
            $requestedProduct = Product::where('company_id', $companyId)
                ->findOrFail($newProductId);
            $requestedQuantity = $requestedProduct->manual_status == 1 ? ($request->quantity ?? 1) : 1;

            if ($message = $this->issueAvailabilityMessage($requestedProduct, $requestedQuantity, $productIssue)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => $message]);
            }

            // 1. Handle Product Change
            if ($oldProductId != $newProductId) {
                // REVERT OLD PRODUCT
                $oldProduct = Product::findOrFail($oldProductId);
                if ($oldProduct->manual_status == 1) {
                    $oldProduct->count += $productIssue->quantity;
                    $oldProduct->save();

                    ProductHistory::create([
                        'product_id' => $oldProduct->id,
                        'action' => 'issue_updated_revert',
                        'field' => 'count',
                        'old_value' => $oldProduct->count - $productIssue->quantity,
                        'new_value' => $oldProduct->count,
                        'notes' => "Product changed in issue record. Count reverted by {$productIssue->quantity}.",
                        'changed_by' => $userId,
                        'company_id' => $companyId,
                    ]);
                } else {
                    $originalStatus = $oldProduct->status;
                    $oldProduct->status = 'Ready for Sale';
                    $oldProduct->assigned_to = null;
                    $oldProduct->save();

                    ProductHistory::create([
                        'product_id' => $oldProduct->id,
                        'action' => 'issue_updated_revert',
                        'field' => 'status',
                        'old_value' => $originalStatus,
                        'new_value' => 'Ready for Sale',
                        'notes' => 'Product changed in issue record. Reverted to Ready for Sale.',
                        'changed_by' => $userId,
                        'company_id' => $companyId,
                    ]);
                }

                // APPLY TO NEW PRODUCT
                $newProduct = $requestedProduct;
                $newQuantity = $request->quantity ?? 1;
                if ($newProduct->manual_status == 1) {
                    if ($newProduct->count < $newQuantity) {
                        DB::rollBack();

                        return response()->json(['success' => false, 'message' => 'New product '.$newProduct->unique_id.' only has '.$newProduct->count.' units.']);
                    }
                    $newProduct->count -= $newQuantity;
                    $newProduct->save();

                    ProductHistory::create([
                        'product_id' => $newProduct->id,
                        'action' => 'issue_updated_apply',
                        'field' => 'count',
                        'old_value' => $newProduct->count + $newQuantity,
                        'new_value' => $newProduct->count,
                        'notes' => "Assigned {$newQuantity} unit(s) via issue edit to {$newEmployeeName}",
                        'changed_by' => $userId,
                        'company_id' => $companyId,
                    ]);
                } else {
                    if ($newProduct->assigned_to && $newProduct->assigned_to != $newEmployeeId) {
                        DB::rollBack();

                        return response()->json(['success' => false, 'message' => 'New product '.$newProduct->unique_id.' is already assigned to someone else.']);
                    }
                    $originalStatus = $newProduct->status;
                    $newProduct->status = 'Issued';
                    $newProduct->assigned_to = $newEmployeeId;
                    $newProduct->save();

                    ProductHistory::create([
                        'product_id' => $newProduct->id,
                        'action' => 'issue_updated_apply',
                        'field' => 'status',
                        'old_value' => $originalStatus,
                        'new_value' => 'Issued',
                        'notes' => "Product issued via edit to {$newEmployeeName}",
                        'changed_by' => $userId,
                        'company_id' => $companyId,
                    ]);
                }
            } else {
                // 2. Handle changes on the same Product
                $product = $productIssue->product;

                // Handle Employee change
                if ($oldEmployeeId != $newEmployeeId) {
                    if ($product->manual_status == 0) {
                        $product->assigned_to = $newEmployeeId;
                        $product->save();

                        ProductHistory::create([
                            'product_id' => $product->id,
                            'action' => 'assignment_changed',
                            'field' => 'assigned_to',
                            'old_value' => $oldEmployeeId,
                            'new_value' => $newEmployeeId,
                            'notes' => 'Reassigned via edit from '.$this->employeeLabel($productIssue->employee)." to {$newEmployeeName}",
                            'changed_by' => $userId,
                            'company_id' => $companyId,
                        ]);
                    }
                }

                // Handle Quantity change
                if ($product->manual_status == 1) {
                    $oldQty = $productIssue->quantity;
                    $newQty = $request->quantity ?? 1;

                    if ($oldQty != $newQty) {
                        $diff = $newQty - $oldQty;
                        if ($product->count < $diff) {
                            DB::rollBack();

                            return response()->json(['success' => false, 'message' => 'Not enough stock to increase quantity. Available: '.$product->count]);
                        }

                        $product->count -= $diff;
                        $product->save();

                        ProductHistory::create([
                            'product_id' => $product->id,
                            'action' => 'issue_quantity_updated',
                            'field' => 'count',
                            'old_value' => $product->count + $diff,
                            'new_value' => $product->count,
                            'notes' => "Quantity changed from {$oldQty} to {$newQty}. Adjusting count by {$diff}.",
                            'changed_by' => $userId,
                            'company_id' => $companyId,
                        ]);
                    }
                }
            }

            $productIssue->update([
                'product_id' => $newProductId,
                'employee_id' => $newEmployeeId,
                'reference' => $request->reference,
                'issue_date' => $request->issue_date,
                'quantity' => $requestedProduct->manual_status == 1 ? ($request->quantity ?? 1) : 1,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Issue record updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductIssueController@update Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update issue: '.$e->getMessage()]);
        }
    }

    public function returnProduct(Request $request, ProductIssue $productIssue)
    {
        if ($productIssue->status == 'Returned') {
            return response()->json(['success' => false, 'message' => 'This product has already been returned.']);
        }

        try {
            $companyId = auth()->user()->company_id;
            $userId = auth()->id();
            $this->inventoryMovements->returnIssue($productIssue, $userId, $companyId);

            return response()->json([
                'success' => true,
                'message' => 'Product returned successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('ProductIssueController@returnProduct Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to process return: '.$e->getMessage()]);
        }
    }

    public function destroy(ProductIssue $productIssue)
    {
        try {
            DB::beginTransaction();

            $product = $productIssue->product;
            $employee = $productIssue->employee;
            $employeeName = $this->employeeLabel($employee);
            $companyId = auth()->user()->company_id;
            $userId = auth()->id();

            if ($product->manual_status == 1) {
                // For bulk/manual products, increment the count back
                $issueQty = $productIssue->quantity;
                $product->count += $issueQty;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'issue_deleted',
                    'field' => 'count',
                    'old_value' => $product->count - $issueQty,
                    'new_value' => $product->count,
                    'notes' => "Issue of {$issueQty} unit(s) to {$employeeName} deleted. Count reverted.",
                    'changed_by' => $userId,
                    'company_id' => $companyId,
                ]);
            } else {
                // For standard products, reset status and assigned_to
                $oldStatus = $product->status;
                $product->status = 'Active';
                $product->assigned_to = null;
                $product->save();

                ProductHistory::create([
                    'product_id' => $product->id,
                    'action' => 'issue_deleted',
                    'field' => 'status/assigned_to',
                    'old_value' => $oldStatus.'/'.$employeeName,
                    'new_value' => 'Active/None',
                    'notes' => "Issue to {$employeeName} deleted. Product available again.",
                    'changed_by' => $userId,
                    'company_id' => $companyId,
                ]);
            }

            $productIssue->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Issue record deleted and inventory reverted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductIssueController@destroy Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete issue: '.$e->getMessage());
        }
    }

    private function employeeLabel(?Employee $employee): string
    {
        return $employee?->full_name ?: 'No employee';
    }

    private function availableProductsForIssue(int $companyId, ?ProductIssue $currentIssue = null)
    {
        return Product::where('company_id', $companyId)
            ->where('sold_status', 0)
            ->where('scrap_status', 0)
            ->where(function ($query) use ($currentIssue) {
                $query->where(function ($manual) {
                    $manual->where('manual_status', 1)
                        ->where('count', '>', 0);
                })->orWhere(function ($single) use ($currentIssue) {
                    $issuedProductIds = ProductIssue::select('product_id')
                        ->whereIn('status', ['Issued', 'issued']);

                    if ($currentIssue) {
                        $issuedProductIds->where('id', '!=', $currentIssue->id);
                    }

                    $single->where('manual_status', '!=', 1)
                        ->whereNotIn('id', $issuedProductIds);
                });

                if ($currentIssue) {
                    $query->orWhere('id', $currentIssue->product_id);
                }
            });
    }

    private function issueAvailabilityMessage(Product $product, int $quantity, ?ProductIssue $currentIssue = null): ?string
    {
        if ($product->sold_status || $product->scrap_status) {
            return 'Product '.$product->unique_id.' is not available for issue.';
        }

        if ($product->manual_status == 1) {
            $availableQuantity = $product->count;

            if ($currentIssue && $currentIssue->product_id == $product->id) {
                $availableQuantity += $currentIssue->quantity;
            }

            if ($availableQuantity < $quantity) {
                return 'Product '.$product->unique_id.' only has '.$availableQuantity.' units in stock.';
            }

            return null;
        }

        $activeIssueQuery = ProductIssue::where('product_id', $product->id)
            ->whereIn('status', ['Issued', 'issued']);

        if ($currentIssue) {
            $activeIssueQuery->where('id', '!=', $currentIssue->id);
        }

        if ($activeIssueQuery->exists()) {
            return 'Product '.$product->unique_id.' is already issued.';
        }

        return null;
    }
}
