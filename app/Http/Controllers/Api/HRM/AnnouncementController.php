<?php

namespace App\Http\Controllers\Api\HRM;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * List all announcements for the authenticated employee's company.
     *
     * Filters results to announcements that are targeted at:
     *   - The employee's department (send_to_type = 'department')
     *   - The employee's reporting_to manager (send_to_type = 'reporting_to')
     *   - Or announcements with no specific target (send_to_type is null)
     *
     * GET /api/employee/announcements
     */
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\Employee $employee */
            $employee = Auth::user();
            $companyId = $employee->company_id;

            // Resolve the manager's ID from the employee's reporting_to (stores manager's full_name)
            $managerId = null;
            if ($employee->full_name) {
                $managerId = Employee::where('company_id', $companyId)
                    ->where('full_name', $employee->full_name)
                    ->value('id');
            }

            $query = Announcement::where('company_id', $companyId)
                ->orderBy('id', 'desc')
                ->where(function ($q) use ($employee, $managerId) {
                    // No target = broadcast to everyone in company
                    $q->whereNull('send_to_type')
                        ->orWhere('send_to_type', '');

                    // Announcements targeted to the employee's own department
                    // send_to_department_id is a JSON array (stored as strings e.g. ["8"])
                    if ($employee->department_id) {
                        $q->orWhere(function ($q2) use ($employee) {
                            $q2->where('send_to_type', 'department')
                                ->whereJsonContains('send_to_department_id', (string) $employee->department_id);
                        });
                    }

                    // Announcements targeted to employees reporting to a specific manager
                    // send_to_employee_id is a JSON array of manager IDs (stored as strings e.g. ["66"])
                    if ($managerId) {
                        $q->orWhere(function ($q2) use ($managerId) {
                            $q2->where('send_to_type', 'reporting_to')
                                ->whereJsonContains('send_to_employee_id', (string) $managerId);
                        });
                    }
                });

            // Optional search by title
            if ($request->filled('q')) {
                $search = $request->q;
                $query->where('title', 'like', "%{$search}%");
            }

            // Optional filter by priority
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            // Optional filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            $perPage = $request->input('per_page', 20);
            $announcements = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $announcements->map(fn ($a) => $this->formatAnnouncement($a)),
                'meta' => [
                    'current_page' => $announcements->currentPage(),
                    'last_page' => $announcements->lastPage(),
                    'per_page' => $announcements->perPage(),
                    'total' => $announcements->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single announcement by ID.
     *
     * GET /api/employee/announcements/{id}
     */
    public function show($id)
    {
        try {
            $companyId = Auth::user()->company_id;

            $announcement = Announcement::where('company_id', $companyId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatAnnouncement($announcement),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format a single announcement for the API response.
     */
    private function formatAnnouncement(Announcement $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'category' => $a->category,
            'priority' => $a->priority,
            'content' => $a->content,
            'created_at' => $a->created_at?->toISOString(),
            'updated_at' => $a->updated_at?->toISOString(),
        ];
    }
}
