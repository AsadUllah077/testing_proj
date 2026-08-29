<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public const CATEGORIES = [
        'Infrastructure',
        'Frontend',
        'Backend / API',
        'Database',
        'Security',
        'Network',
        'UI / UX',
        'Performance',
        'Integration',
        'Other',
    ];

    public const SEVERITIES = [
        'low'      => 'Low — Minor cosmetic issue',
        'medium'   => 'Medium — Moderate impact',
        'high'     => 'High — Major functionality affected',
        'critical' => 'Critical — System down',
    ];

    public function index(Request $request)
    {
        $query = Ticket::with(['assignee', 'reporter'])
            ->where('company_id', Auth::user()->company_id);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($severity = $request->get('severity')) {
            $query->where('severity', $severity);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $tickets = $query->latest()->paginate($request->get('per_page', 20))->withQueryString();
        
        $employees   = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $departments = Department::orderBy('department')->get();
        $categories  = self::CATEGORIES;

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('pages.tickets.partials.table', compact('tickets'))->render(),
                'pagination' => view('components.data-table-paginatior', ['paginator' => $tickets])->render(),
            ]);
        }

        return view('pages.tickets.index', compact('tickets', 'employees', 'departments', 'categories'));
    }

    public function create()
    {
        $employees   = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();
        $departments = Department::orderBy('department')->get();
        $categories  = self::CATEGORIES;
        $severities  = self::SEVERITIES;

        return view('pages.tickets.create', compact('employees', 'departments', 'categories', 'severities'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'severity'      => 'required|in:low,medium,high,critical',
            'category'      => 'nullable|string|max:255',
            'assigned_to'   => 'nullable|exists:employees,id',
            'due_date'      => 'nullable|date',
            'region'        => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'attachment'    => 'nullable|file|max:10240|mimes:png,jpg,jpeg,pdf,mp4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('tickets', 'public');
            }

            $ticket = Ticket::create([
                'ticket_number' => null,
                'title'         => $data['title'],
                'description'   => $data['description'],
                'severity'      => $data['severity'],
                'status'        => 'open',
                'reported_by'   => Auth::id(),
                'company_id'    => Auth::user()->company_id,
                'attachment'    => $attachmentPath,
            ]);

            $ticket->update(['ticket_number' => 'TKT-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT)]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket submitted successfully!',
                'data'    => $ticket,
            ], 201);

        } catch (\Exception $e) {
            Log::error('TicketController@store failed', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['assignee', 'reporter', 'department', 'comments.user']);
        $employees = Employee::where('company_id', Auth::user()->company_id)->orderBy('full_name')->get();

        return view('pages.tickets.show', compact('ticket', 'employees'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'nullable|exists:employees,id',
            'status'      => 'required|in:open,in-progress,review,resolved,closed',
            'due_date'    => 'nullable|date',
            'severity'    => 'nullable|in:low,medium,high,critical',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Only Super Admin can assign/re-assign tickets
            if (isset($data['assigned_to']) && $data['assigned_to'] != $ticket->assigned_to && !Auth::user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Super Admins can assign or re-assign tickets.',
                ], 403);
            }

            $ticket->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully!',
                'data'    => $ticket->fresh(['assignee']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Ticket $ticket)
    {
        try {
            $ticket->delete();
            return response()->json(['success' => true, 'message' => 'Ticket deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_ids'  => 'required|array',
            'ticket_ids.*'=> 'exists:tickets,id',
            'assigned_to' => 'required|exists:employees,id',
            'category'    => 'required|string',
            'due_date'    => 'required|date',
            'due_time'    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (!Auth::user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only Super Admins can perform bulk assignment.'], 403);
        }

        try {
            $dueDateTime = $request->due_date . ' ' . $request->due_time;
            
            // Prevent re-assigning completed tickets
            $completedCount = Ticket::whereIn('id', $request->ticket_ids)
                ->where('status', 'completed')
                ->count();
                
            if ($completedCount > 0) {
                return response()->json(['success' => false, 'message' => 'Completed units cannot be re-assigned.'], 400);
            }

            Ticket::whereIn('id', $request->ticket_ids)->update([
                'assigned_to' => $request->assigned_to,
                'category'    => $request->category,
                'due_date'    => $dueDateTime,
                'assigned_at' => now(),
                'status'      => 'assigned',
            ]);

            return response()->json(['success' => true, 'message' => 'Tickets assigned successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function assignedTickets(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'No employee record found for your user.');
        }

        $tickets = Ticket::where('assigned_to', $employee->id)
            ->whereIn('status', ['assigned', 'in-progress', 'completed'])
            ->latest()
            ->paginate(20);

        return view('pages.tickets.assigned', compact('tickets'));
    }

    public function start(Ticket $ticket)
    {
        if ($ticket->status !== 'assigned') {
            return response()->json(['success' => false, 'message' => 'Ticket is not in assigned state.'], 400);
        }

        $ticket->update([
            'status' => 'in-progress',
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Ticket started!']);
    }

    public function complete(Request $request, Ticket $ticket)
    {
        $request->validate([
            'feedback' => 'required|string',
        ]);

        $ticket->update([
            'status' => 'completed',
            'completed_at' => now(),
            'feedback' => $request->feedback,
        ]);

        return response()->json(['success' => true, 'message' => 'Ticket completed!']);
    }
}
