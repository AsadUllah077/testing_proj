<?php

// app/Http/Controllers/Product/Warehouse/TechnicianController.php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Services\Warehouse\InspectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicianController extends Controller
{
    public function __construct(private readonly InspectionService $inspections) {}

    /**
     * Display a listing of technicians and their assigned tasks.
     */
    // public function index(Request $request)
    // {
    //     try {
    //         // Get filter inputs
    //         $status = $request->get('status', 'All');
    //         $category = $request->get('category', 'All');
    //         $taskType = $request->get('task_type', 'All');
    //         $search = $request->get('search');
    //         $perPage = $request->get('per_page', 10);

    //         // Build query for product assignments
    //         $query = ProductAssignment::with([
    //             'product' => function ($q) {
    //                 $q->with(['category', 'disposition', 'workFlowStage'])
    //                     ->where('scrap_status', 0)
    //                     ->where('sold_status', 0);
    //             },
    //             'technician',
    //             'assigner',
    //             'workflowStage',
    //             'disposition',
    //         ])
    //             ->whereHas('product', function ($q) {
    //                 $q->where('scrap_status', 0)
    //                     ->where('sold_status', 0);
    //             })
    //             ->latest('assigned_at');

    //         // Apply status filter
    //         if ($status !== 'All') {
    //             if ($status === 'Pending') {
    //                 $query->whereNull('completed_at')
    //                     ->whereNull('started_at');
    //             } elseif ($status === 'In Progress') {
    //                 $query->whereNull('completed_at')
    //                     ->whereNotNull('started_at');
    //             } elseif ($status === 'Complete') {
    //                 $query->whereNotNull('completed_at');
    //             }
    //         }

    //         // Apply category filter
    //         if ($category !== 'All') {
    //             $query->whereHas('product.category', function ($q) use ($category) {
    //                 $q->where('name', $category);
    //             });
    //         }

    //         // Apply task type filter
    //         if ($taskType !== 'All') {
    //             $query->where('task_type', $taskType);
    //         }

    //         // Apply search
    //         if ($search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->whereHas('product', function ($sq) use ($search) {
    //                     $sq->where('scrap_status', 0)
    //                         ->where('sold_status', 0)
    //                         ->where(function ($inner) use ($search) {
    //                             $inner->where('unique_id', 'LIKE', "%{$search}%")
    //                                 ->orWhere('title', 'LIKE', "%{$search}%")
    //                                 ->orWhere('product_details', 'LIKE', "%{$search}%");
    //                         });
    //                 })->orWhereHas('technician', function ($sq) use ($search) {
    //                     $sq->where('full_name', 'LIKE', "%{$search}%")
    //                         ->orWhere('email', 'LIKE', "%{$search}%");
    //                 })->orWhere('task_type', 'LIKE', "%{$search}%")
    //                     ->orWhere('remarks', 'LIKE', "%{$search}%");
    //             });
    //         }

    //         // Get paginated results
    //         $assignments = $query->paginate($perPage);

    //         // Get data for dropdowns
    //         $categories = ProductCategory::orderBy('name')->pluck('name');

    //         // Get technicians with active task counts
    //         $technicians = Employee::whereHas('department', function ($q) {
    //             $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
    //         })
    //             ->orderBy('full_name')
    //             ->get(['id', 'full_name', 'email']);

    //         // Add current tasks count manually
    //         foreach ($technicians as $tech) {
    //             $tech->current_tasks = ProductAssignment::where('technician_id', $tech->id)
    //                 ->whereNull('completed_at')
    //                 ->whereHas('product', function ($q) {
    //                     $q->where('scrap_status', 0)
    //                         ->where('sold_status', 0);
    //                 })
    //                 ->count();
    //         }

    //         // Get available products for bulk assignment
    //         // Only products that are not scrapped, not sold, and not currently assigned
    //         $products = Product::with(['category', 'disposition'])
    //             ->where('scrap_status', 0)
    //             ->where('sold_status', 0)
    //             ->whereDoesntHave('currentAssignment', function ($q) {
    //                 $q->whereNull('completed_at');
    //             })
    //             ->orderBy('created_at', 'desc')
    //             ->limit(100)
    //             ->get();

    //         $taskTypes = ProductAssignment::distinct('task_type')
    //             ->whereNotNull('task_type')
    //             ->orderBy('task_type')
    //             ->pluck('task_type');

    //         // Get workflow stages for dropdown
    //         $workflowStages = WorkflowStage::orderBy('name')->get(['id', 'name']);

    //         // Get dispositions for dropdown
    //         $dispositions = Disposition::orderBy('name')->get(['id', 'name']);

    //         // Calculate active technicians based on filtered tasks
    //         $activeTechnicians = 0;
    //         foreach ($technicians as $tech) {
    //             $hasActiveTasks = ProductAssignment::where('technician_id', $tech->id)
    //                 ->whereNull('completed_at')
    //                 ->whereHas('product', function ($q) {
    //                     $q->where('scrap_status', 0)->where('sold_status', 0);
    //                 })
    //                 ->exists();
    //             if ($hasActiveTasks) {
    //                 $activeTechnicians++;
    //             }
    //         }

    //         // Update stats with correct active technicians count
    //         $stats['active_technicians'] = $activeTechnicians;
    //         // Get statistics (filtered by non-scrapped and non-sold products)
    //         $stats = [
    //             'total_tasks' => ProductAssignment::whereHas('product', function ($q) {
    //                 $q->where('scrap_status', 0)->where('sold_status', 0);
    //             })->count(),

    //             'pending_tasks' => ProductAssignment::whereHas('product', function ($q) {
    //                 $q->where('scrap_status', 0)->where('sold_status', 0);
    //             })->whereNull('completed_at')
    //                 ->whereNull('started_at')
    //                 ->count(),

    //             'in_progress_tasks' => ProductAssignment::whereHas('product', function ($q) {
    //                 $q->where('scrap_status', 0)->where('sold_status', 0);
    //             })->whereNull('completed_at')
    //                 ->whereNotNull('started_at')
    //                 ->count(),

    //             'completed_tasks' => ProductAssignment::whereHas('product', function ($q) {
    //                 $q->where('scrap_status', 0)->where('sold_status', 0);
    //             })->whereNotNull('completed_at')
    //                 ->count(),

    //             'active_technicians' => $activeTechnicians,
    //             'total_technicians' => $technicians->count(),
    //         ];

    //         if ($request->ajax()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'html' => view('pages.products.warehouse.technicians.partials.table', compact('assignments'))->render(),
    //                 'pagination' => $assignments->links()->toHtml(),
    //                 'stats' => $stats,
    //             ]);
    //         }

    //         return view('pages.products.warehouse.technicians.index', compact(
    //             'assignments',
    //             'categories',
    //             'technicians',
    //             'products',
    //             'taskTypes',
    //             'workflowStages',
    //             'dispositions',
    //             'stats'
    //         ));

    //     } catch (\Exception $e) {
    //         Log::error('TechnicianController@index Error: '.$e->getMessage());

    //         if ($request->ajax()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Error loading technicians data: '.$e->getMessage(),
    //             ], 500);
    //         }

    //         return redirect()->back()->with('error', 'Error loading technicians data: '.$e->getMessage());
    //     }
    // }
    public function index(Request $request)
    {
        try {
            // Get the currently logged in user and their role
            $authUser = auth('web')->user();
            $roleName = strtolower($authUser->role->name ?? '');
            // dd($roleName);
            $isSuperOrProductAdmin = in_array($roleName, ['super admin', 'product admin']);

            // Get filter inputs
            $status = $request->get('status', 'All');
            $category = $request->get('category', 'All');
            $taskType = $request->get('task_type', 'All');
            $search = $request->get('search');
            $perPage = $request->get('per_page', 20);
            $technicianId = $request->get('technician_id', 'All');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Build query for product assignments
            $query = ProductAssignment::with([
                'product' => function ($q) {
                    $q->with(['category', 'disposition', 'workFlowStage'])
                        ->where('scrap_status', 0)
                        ->where('sold_status', 0);
                },
                'technician',
                'assigner',
                'workflowStage',
                'disposition',
            ])
                ->whereHas('product', function ($q) {
                    $q->where('scrap_status', 0)
                        ->where('sold_status', 0);
                })
                ->latest('assigned_at');

            // Restriction logic for non-admins
            $applyRestriction = function ($q) use ($isSuperOrProductAdmin, $authUser) {
                if (! $isSuperOrProductAdmin) {
                    $q->whereHas('technician', function ($sq) use ($authUser) {
                        $sq->where('linked_erp_user_id', $authUser->id);
                    });
                }

                return $q;
            };
            // restricted table query
            $applyRestriction($query);

            // Apply technician filter (if admin)
            if ($isSuperOrProductAdmin && $technicianId !== 'All') {
                $query->where('technician_id', $technicianId);
            }

            // Apply status filter
            if ($status !== 'All') {
                if ($status === 'Pending') {
                    $query->whereNull('completed_at')
                        ->whereNull('started_at');
                } elseif ($status === 'In Progress') {
                    $query->whereNull('completed_at')
                        ->whereNotNull('started_at');
                } elseif ($status === 'Complete') {
                    $query->whereNotNull('completed_at');
                }
            }

            // Apply category filter
            if ($category !== 'All') {
                $query->whereHas('product.category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            // Apply task type filter
            if ($taskType !== 'All') {
                $query->where('task_type', $taskType);
            }

            // Apply date filters (for completed tasks)
            if ($dateFrom) {
                $query->whereDate('completed_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('completed_at', '<=', $dateTo);
            }

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($sq) use ($search) {
                        $sq->where('scrap_status', 0)
                            ->where('sold_status', 0)
                            ->where(function ($inner) use ($search) {
                                $inner->where('unique_id', 'LIKE', "%{$search}%")
                                    ->orWhere('title', 'LIKE', "%{$search}%")
                                    ->orWhere('product_details', 'LIKE', "%{$search}%");
                            });
                    })->orWhereHas('technician', function ($sq) use ($search) {
                        $sq->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })->orWhere('task_type', 'LIKE', "%{$search}%")
                        ->orWhere('remarks', 'LIKE', "%{$search}%");
                });
            }

            // Get paginated results
            $assignments = $query->paginate($perPage);

            // Get data for dropdowns
            $categories = ProductCategory::orderBy('name')->pluck('name');

            // Get technicians list - restrict if not admin
            $techQuery = Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%']);
            });

            if (! $isSuperOrProductAdmin) {
                $techQuery->where('linked_erp_user_id', $authUser->id);
            }

            $technicians = $techQuery->orderBy('full_name')
                ->get(['id', 'full_name', 'email', 'linked_erp_user_id']);

            // Add current tasks count manually
            foreach ($technicians as $tech) {
                $tech->current_tasks = ProductAssignment::where('technician_id', $tech->id)
                    ->whereNull('completed_at')
                    ->whereHas('product', function ($q) {
                        $q->where('scrap_status', 0)
                            ->where('sold_status', 0);
                    })
                    ->count();
            }

            // Batch-compute overdue and avg completion for workload panel
            $techIds = $technicians->pluck('id')->toArray();

            $overdueMap = ProductAssignment::whereIn('technician_id', $techIds)
                ->whereNull('completed_at')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->selectRaw('technician_id, COUNT(*) as cnt')
                ->groupBy('technician_id')
                ->pluck('cnt', 'technician_id');

            $avgMap = ProductAssignment::whereIn('technician_id', $techIds)
                ->whereNotNull('completed_at')
                ->whereNotNull('started_at')
                ->selectRaw('technician_id, AVG(EXTRACT(EPOCH FROM (completed_at - started_at)) / 3600) as avg_hours')
                ->groupBy('technician_id')
                ->pluck('avg_hours', 'technician_id');

            foreach ($technicians as $tech) {
                $tech->overdue_tasks = (int) ($overdueMap->get($tech->id, 0));
                $tech->avg_completion_hours = round((float) ($avgMap->get($tech->id, 0)), 1);
            }

            // Get available products for bulk assignment
            // Only products that are not scrapped, not sold, and not currently assigned
            $products = Product::with(['category', 'disposition'])
                ->where('scrap_status', 0)
                ->where('sold_status', 0)
                ->whereDoesntHave('currentAssignment', function ($q) {
                    $q->whereNull('completed_at');
                })
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            $taskTypes = ProductAssignment::distinct('task_type')
                ->whereNotNull('task_type')
                ->orderBy('task_type')
                ->pluck('task_type');

            // Get workflow stages for dropdown
            $workflowStages = WorkflowStage::orderBy('name')->get(['id', 'name']);

            // Get dispositions for dropdown
            $dispositions = Disposition::orderBy('name')->get(['id', 'name']);

            // Calculate active technicians based on filtered tasks
            $activeTechnicians = 0;
            foreach ($technicians as $tech) {
                $hasActiveTasks = ProductAssignment::where('technician_id', $tech->id)
                    ->whereNull('completed_at')
                    ->whereHas('product', function ($q) {
                        $q->where('scrap_status', 0)->where('sold_status', 0);
                    })
                    ->exists();
                if ($hasActiveTasks) {
                    $activeTechnicians++;
                }
            }

            // Get statistics - restricted if not admin
            $stats = [
                'total_tasks' => $applyRestriction(ProductAssignment::whereHas('product', function ($q) {
                    $q->where('scrap_status', 0)->where('sold_status', 0);
                }))->count(),

                'pending_tasks' => $applyRestriction(ProductAssignment::whereHas('product', function ($q) {
                    $q->where('scrap_status', 0)->where('sold_status', 0);
                }))->whereNull('completed_at')
                    ->whereNull('started_at')
                    ->count(),

                'in_progress_tasks' => $applyRestriction(ProductAssignment::whereHas('product', function ($q) {
                    $q->where('scrap_status', 0)->where('sold_status', 0);
                }))->whereNull('completed_at')
                    ->whereNotNull('started_at')
                    ->count(),

                'completed_tasks' => $applyRestriction(ProductAssignment::whereHas('product', function ($q) {
                    $q->where('scrap_status', 0)->where('sold_status', 0);
                }))->whereNotNull('completed_at')
                    ->count(),

                'active_technicians' => $activeTechnicians,
                'total_technicians' => $technicians->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('pages.products.warehouse.technicians.partials.table', compact('assignments'))->render(),
                    'pagination' => $assignments->links()->toHtml(),
                    'stats' => $stats,
                ]);
            }

            return view('pages.products.warehouse.technicians.index', compact(
                'assignments',
                'categories',
                'technicians',
                'products',
                'taskTypes',
                'workflowStages',
                'dispositions',
                'stats'
            ));

        } catch (\Exception $e) {
            Log::error('TechnicianController@index Error: '.$e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading technicians data: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error loading technicians data: '.$e->getMessage());
        }
    }

    /**
     * Handle bulk assignment of tasks to technicians.
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'technician_id' => 'required|exists:users,id',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'task_type' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $technician = User::findOrFail($request->technician_id);
            $currentUser = auth('web')->user();
            $productIds = $request->product_ids;
            $successCount = 0;
            $errors = [];

            foreach ($productIds as $productId) {
                try {
                    $product = Product::findOrFail($productId);

                    // Check for existing active assignment
                    $existingAssignment = ProductAssignment::where('product_id', $productId)
                        ->whereNull('completed_at')
                        ->first();

                    $assignmentData = [
                        'product_id' => $productId,
                        'technician_id' => $request->technician_id,
                        'assigned_by' => $currentUser->id,
                        'task_type' => $request->task_type,
                        'priority' => $request->priority,
                        'due_date' => $request->due_date,
                        'remarks' => $request->remarks,
                        'assigned_at' => now(),
                        'company_id' => $product->company_id,
                    ];

                    if ($existingAssignment) {
                        // Update existing assignment
                        $existingAssignment->update($assignmentData);

                        // Log update
                        $product->histories()->create([
                            'action' => 'assignment_updated',
                            'field' => 'product_assignment',
                            'old_value' => json_encode(['technician' => $existingAssignment->technician?->username]),
                            'new_value' => json_encode(['technician' => $technician->username, 'task_type' => $request->task_type]),
                            'notes' => "Assignment updated to {$technician->username} for {$request->task_type}",
                            'changed_by' => $currentUser->id,
                            'company_id' => $product->company_id,
                        ]);
                    } else {
                        // Create new assignment
                        ProductAssignment::create($assignmentData);

                        // Log creation
                        $product->histories()->create([
                            'action' => 'assignment_created',
                            'field' => 'product_assignment',
                            'old_value' => null,
                            'new_value' => json_encode(['technician' => $technician->username, 'task_type' => $request->task_type]),
                            'notes' => "Assigned to {$technician->username} for {$request->task_type}",
                            'changed_by' => $currentUser->id,
                            'company_id' => $product->company_id,
                        ]);
                    }

                    // Update product's assigned_to field
                    $product->update([
                        'assigned_to' => $request->technician_id,
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Product ID {$productId}: ".$e->getMessage();
                    Log::error("Failed to assign product {$productId}: ".$e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned {$successCount} out of ".count($productIds)." products to {$technician->username}",
                'count' => $successCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TechnicianController@bulkAssign Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete assignments
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:product_assignments,id',
        ]);

        try {
            DB::beginTransaction();

            $assignments = ProductAssignment::whereIn('id', $request->ids)->get();
            $count = $assignments->count();

            foreach ($assignments as $assignment) {
                // Log deletion
                if ($assignment->product) {
                    $assignment->product->histories()->create([
                        'action' => 'assignment_deleted',
                        'field' => 'product_assignment',
                        'old_value' => json_encode(['technician' => $assignment->technician?->username, 'task_type' => $assignment->task_type]),
                        'new_value' => null,
                        'notes' => 'Assignment deleted',
                        'changed_by' => auth('web')->id(),
                        'company_id' => $assignment->company_id,
                    ]);
                }

                $assignment->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} assignments",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TechnicianController@bulkDelete Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignments: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk mark assignments as complete
     */
    public function bulkComplete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:product_assignments,id',
        ]);

        try {
            DB::beginTransaction();

            $assignments = ProductAssignment::whereIn('id', $request->ids)
                ->whereNull('completed_at')
                ->get();

            $count = $assignments->count();

            foreach ($assignments as $assignment) {
                $assignment->update([
                    'completed_at' => now(),
                    'result' => 'completed',
                    'status' => 'completed',
                ]);

                if ($assignment->product) {
                    $assignment->product->histories()->create([
                        'action' => 'task_completed',
                        'field' => 'task_status',
                        'old_value' => 'in_progress',
                        'new_value' => 'completed',
                        'notes' => 'Task marked as complete via bulk action',
                        'changed_by' => auth('web')->id(),
                        'company_id' => $assignment->company_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$count} tasks as complete",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TechnicianController@bulkComplete Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete tasks: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start a task for a specific assignment.
     */
    public function startTask($id)
    {
        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $this->inspections->start($assignment, auth('web')->id());

            return response()->json([
                'success' => true,
                'message' => 'Task started successfully',
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('TechnicianController@startTask Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to start task: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry a failed task
     */
    public function retryTask($id)
    {
        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $this->inspections->retry($assignment, auth('web')->id());

            return response()->json([
                'success' => true,
                'message' => 'Task retry initiated',
            ]);

        } catch (\Exception $e) {
            Log::error('TechnicianController@retryTask Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry task: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the appropriate submission form based on task type
     */
    /**
     * Show the appropriate submission form based on task type
     */
    /**
     * Show the appropriate submission form based on task type
     */
    public function submitResult(Request $request, $id, $type)
    {
        // dd($type);
        try {
            Log::info('Looking for assignment with ID: '.$id);

            // Find the assignment by its ID
            $assignment = ProductAssignment::with([
                'product.category',
                'product.visualInspections',
                'product.functionalTestings',
                'technician',
                'visualInspectionResult',
                'functionalTestingResult',
                'generalTaskResult',
            ])
                ->find($id);

            if (! $assignment) {
                Log::error('Assignment not found with ID: '.$id);

                // Get available assignments for debugging
                $availableIds = ProductAssignment::pluck('id')->toArray();

                return redirect()->route('warehouse.technicians.index')
                    ->with('error', 'Assignment not found. Available IDs: '.implode(', ', $availableIds));
            }

            // Map type to view
            $viewMap = [
                'visual' => 'pages.products.warehouse.technicians.visual-inspection',
                'functional' => 'pages.products.warehouse.technicians.functional-testing',
                'calibration' => 'pages.products.warehouse.technicians.general-task',
                'repair' => 'pages.products.warehouse.technicians.general-task',
                'quality' => 'pages.products.warehouse.technicians.general-task',
                'general' => 'pages.products.warehouse.technicians.general-task',
            ];

            $view = $viewMap[$type] ?? 'pages.products.warehouse.technicians.general-task';

            $isEditing = $request->boolean('edit') && (bool) $assignment->completed_at;

            if ($assignment->completed_at && ! $isEditing) {
                return redirect()->route('warehouse.technicians.view.result', $assignment->id)
                    ->with('info', 'This task is already completed. Viewing results.');
            }

            $existingResult = match ($type) {
                'visual' => $assignment->visualInspectionResult,
                'functional' => $assignment->functionalTestingResult,
                default => $assignment->generalTaskResult,
            };

            return view($view, compact('assignment', 'type', 'isEditing', 'existingResult'));

        } catch (\Exception $e) {
            Log::error('TechnicianController@submitResult Error: '.$e->getMessage());

            return redirect()->route('warehouse.technicians.index')
                ->with('error', 'Failed to load submission form: '.$e->getMessage());
        }
    }

    /**
     * Submit Visual Inspection results
     */
    public function submitVisualInspection(Request $request, $id)
    {
        $request->merge([
            'grade' => $request->filled('grade') ? $request->grade : $request->cosmetic_grade,
        ]);

        $request->validate([
            'checklist_items' => 'required|array',
            'grade' => 'required|in:C0,C1,C2,C3,C4,C5,C6,C7,C8,C9',
            'cosmetic_grade' => 'nullable|in:C0,C1,C2,C3,C4,C5,C6,C7,C8,C9',
            'functional_grade' => 'nullable|in:F1,F2,F3,F4,F5,F6',
            'inspection_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
            'photos' => 'nullable|array',
        ]);

        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $result = $this->inspections->submitVisual(
                $assignment,
                $request->only([
                    'checklist_items',
                    'grade',
                    'functional_grade',
                    'inspection_notes',
                    'remarks',
                    'photos',
                ]),
                auth('web')->id()
            );
            $wasCompleted = $result['wasCompleted'];

            return redirect()->route('warehouse.technicians.view.result', $assignment->id)
                ->with('success', $wasCompleted ? 'Visual inspection results updated successfully' : 'Visual inspection results submitted successfully');

        } catch (\Exception $e) {
            Log::error('TechnicianController@submitVisualInspection Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to submit results: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Submit Functional Testing results
     */
    /**
     * Submit Functional Testing results
     */
    public function submitFunctionalTesting(Request $request, $id)
    {
        $request->merge([
            'grade' => $request->filled('grade') ? $request->grade : $request->functional_grade,
        ]);

        $request->validate([
            'test_results' => 'required|array',
            'test_results.*.name' => 'required|string',
            'test_results.*.notes' => 'nullable|string',
            'grade' => 'required|in:F1,F2,F3,F4,F5,F6',
            'cosmetic_grade' => 'nullable|in:C0,C1,C2,C3,C4,C5,C6,C7,C8,C9',
            'functional_grade' => 'nullable|in:F1,F2,F3,F4,F5,F6',
            'overall_summary' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $result = $this->inspections->submitFunctional(
                $assignment,
                $request->only([
                    'test_results',
                    'grade',
                    'cosmetic_grade',
                    'overall_summary',
                    'remarks',
                ]),
                auth('web')->id()
            );
            $wasCompleted = $result['wasCompleted'];

            return redirect()->route('warehouse.technicians.view.result', $assignment->id)
                ->with('success', $wasCompleted ? 'Functional testing results updated successfully' : 'Functional testing results submitted successfully');

        } catch (\Exception $e) {
            Log::error('TechnicianController@submitFunctionalTesting Error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Failed to submit results: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Submit General Task results
     */
    public function submitGeneralTask(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'nullable|string',
            'technician_notes' => 'nullable|string',
            'checklist_items' => 'nullable|array',
            'grade' => 'nullable|in:A,B,C,F',
            'cosmetic_grade' => 'nullable|in:C0,C1,C2,C3,C4,C5,C6,C7,C8,C9',
            'functional_grade' => 'nullable|in:F1,F2,F3,F4,F5,F6',
        ]);

        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $result = $this->inspections->submitGeneral(
                $assignment,
                $request->only([
                    'remarks',
                    'technician_notes',
                    'checklist_items',
                    'grade',
                    'cosmetic_grade',
                    'functional_grade',
                ]),
                auth('web')->id()
            );
            $wasCompleted = $result['wasCompleted'];

            return redirect()->route('warehouse.technicians.view.result', $assignment->id)
                ->with('success', $wasCompleted ? 'Task results updated successfully' : 'Task results submitted successfully');

        } catch (\Exception $e) {
            Log::error('TechnicianController@submitGeneralTask Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to submit results: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Complete a task and save results.
     */
    public function completeTask(Request $request, $id)
    {
        $request->validate([
            'result' => 'required|in:pass,fail',
            'notes' => 'nullable|string',
            'test_data' => 'nullable|array',
        ]);

        try {
            $assignment = ProductAssignment::with('product')->findOrFail($id);
            $this->inspections->complete(
                $assignment,
                $request->only(['result', 'notes', 'test_data']),
                auth('web')->id()
            );

            return redirect()->route('warehouse.technicians.index')
                ->with('success', 'Task completed successfully');

        } catch (\Exception $e) {
            Log::error('TechnicianController@completeTask Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to complete task: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * View result of a completed task.
     */
    /**
     * View result of a completed task
     */
    /**
     * View result of a completed task
     */
    public function viewResult($id)
    {
        try {
            $assignment = ProductAssignment::with([
                'product.category',
                'product.disposition',
                'product.visualInspections',
                'product.functionalTestings',
                'technician',
                'assigner',
                'workflowStage',
                'disposition',
            ])->findOrFail($id);

            $taskType = strtolower(trim($assignment->task_type));

            // Load the appropriate result based on task type
            $result = match ($taskType) {
                'visual inspection' => $assignment->visualInspectionResult,
                'functional testing' => $assignment->functionalTestingResult,
                default => $assignment->generalTaskResult,
            };

            if (! $result) {
                return redirect()->route('warehouse.technicians.index')
                    ->with('error', 'No results found for this task');
            }

            $visualInspection = $assignment->product->visualInspections->first();
            $functionalTesting = $assignment->product->functionalTestings->first();

            return view('pages.products.warehouse.technicians.view-result', compact(
                'assignment',
                'result',
                'visualInspection',
                'functionalTesting'
            ));

        } catch (\Exception $e) {
            Log::error('TechnicianController@viewResult Error: '.$e->getMessage());

            return redirect()->route('warehouse.technicians.index')
                ->with('error', 'Failed to load result: '.$e->getMessage());
        }
    }

    /**
     * Export to PDF
     */
    public function exportPDF(Request $request)
    {
        // Implement PDF export logic here
        return redirect()->back()->with('info', 'PDF export coming soon');
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        // Implement Excel export logic here
        return redirect()->back()->with('info', 'Excel export coming soon');
    }

    /**
     * Show the assignment edit form.
     */
    public function edit($id)
    {
        try {
            $assignment = ProductAssignment::with([
                'product.workFlowStage',
                'technician',
            ])->findOrFail($id);

            $taskType = strtolower(trim($assignment->task_type ?? ''));
            $stageName = strtolower(trim($assignment->product->workFlowStage->name ?? ''));

            if (str_contains($taskType, 'functional') || str_contains($stageName, 'functional')) {
                $urlType = 'functional';
            } elseif (str_contains($taskType, 'visual') || str_contains($stageName, 'visual')) {
                $urlType = 'visual';
            } elseif (str_contains($taskType, 'calibration')) {
                $urlType = 'calibration';
            } elseif (str_contains($taskType, 'repair')) {
                $urlType = 'repair';
            } elseif (str_contains($taskType, 'quality')) {
                $urlType = 'quality';
            } else {
                $urlType = 'general';
            }

            if (! $assignment->started_at && ! $assignment->completed_at) {
                return redirect()->route('warehouse.technicians.show', $assignment->id)
                    ->with('info', 'Start the task to submit results.');
            }

            return redirect()->route('warehouse.technicians.submit.result', [
                'id' => $assignment->id,
                'type' => $urlType,
                'edit' => $assignment->completed_at ? 1 : 0,
            ]);

        } catch (\Exception $e) {
            Log::error('TechnicianController@edit Error: '.$e->getMessage());

            return redirect()->route('warehouse.technicians.index')
                ->with('error', 'Assignment not found');
        }
    }

    /**
     * Redirect legacy edit submissions to the result-edit flow.
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('warehouse.technicians.edit', $id)
            ->with('info', 'Use the task result form to edit completed task results.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $assignment = ProductAssignment::with([
                'product.category',
                'product.disposition',
                'product.workFlowStage',
                'technician',
                'assigner',
                'workflowStage',
                'disposition',
            ])->findOrFail($id);

            return view('pages.products.warehouse.technicians.show', compact('assignment'));

        } catch (\Exception $e) {
            Log::error('TechnicianController@show Error: '.$e->getMessage());

            return redirect()->route('warehouse.technicians.index')
                ->with('error', 'Assignment not found');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $assignment = ProductAssignment::with('product')->findOrFail($id);

            if ($assignment->product) {
                $assignment->product->histories()->create([
                    'action' => 'assignment_deleted',
                    'field' => 'product_assignment',
                    'old_value' => json_encode(['technician' => $assignment->technician?->username, 'task_type' => $assignment->task_type]),
                    'new_value' => null,
                    'notes' => 'Assignment deleted',
                    'changed_by' => auth('web')->id(),
                    'company_id' => $assignment->company_id,
                ]);
            }

            $assignment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TechnicianController@destroy Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment: '.$e->getMessage(),
            ], 500);
        }
    }
}
