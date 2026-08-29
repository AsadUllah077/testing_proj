<?php

namespace App\Http\Controllers;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = Announcement::where('company_id', $companyId)->orderBy('id', 'desc');
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('title', 'like', "%{$q}%");
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        $categories = Announcement::where('company_id', $companyId)
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->pluck('category');
        $announcements = $query->paginate(20)->onEachSide(1);
        return view('pages.hrm.announcements.index', compact('announcements', 'categories'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $departments = Department::where('company_id', $companyId)->orderBy('department')->get();
        // Fetch all active employees as potential managers
        $employees = Employee::where('company_id', $companyId)->where('status', 'active')->orderBy('full_name')->get();
        return view('pages.hrm.announcements.create', compact('departments', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high'],
            'content' => ['required', 'string'],
            'send_to_type' => ['nullable', 'in:department,reporting_to'],
            'send_to_department_id' => ['nullable', 'array'],
            'send_to_department_id.*' => ['exists:departments,id'],
            'send_to_employee_id' => ['nullable', 'array'],
            'send_to_employee_id.*' => ['exists:employees,id'],
        ]);

        $data['company_id'] = Auth::user()->company_id;

        $announcement = Announcement::create($data);

        // Send notifications to recipients
        $this->sendNotificationsToRecipients($announcement);

        return redirect()->route('hrm.announcements.index')->with('success', 'Announcement created and notifications sent.');
    }

    public function show($id)
    {
        $companyId = Auth::user()->company_id;
        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        return view('pages.hrm.announcements.show', compact('announcement'));
    }

    public function edit($id)
    {
        $companyId = Auth::user()->company_id;
        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $departments = Department::where('company_id', $companyId)->orderBy('department')->get();
        $employees = Employee::where('company_id', $companyId)->where('status', 'active')->orderBy('full_name')->get();
        return view('pages.hrm.announcements.edit', compact('announcement', 'departments', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $companyId = Auth::user()->company_id;
        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high'],
            'content' => ['required', 'string'],
            'send_to_type' => ['nullable', 'in:department,reporting_to'],
            'send_to_department_id' => ['nullable', 'array'],
            'send_to_department_id.*' => ['exists:departments,id'],
            'send_to_employee_id' => ['nullable', 'array'],
            'send_to_employee_id.*' => ['exists:employees,id'],
        ]);

        $announcement->update($data);

        // Re-send notifications with the updated content
        $this->sendNotificationsToRecipients($announcement->fresh());

        return redirect()->route('hrm.announcements.index')->with('success', 'Announcement updated and notifications sent.');
    }

    public function destroy($id)
    {
        $companyId = Auth::user()->company_id;
        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $announcement->delete();
        return redirect()->route('hrm.announcements.index')->with('success', 'Announcement deleted');
    }

    public function downloadPdf($id)
    {
        $companyId = Auth::user()->company_id;
        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);

        $company = Auth::user()->company; // Assuming company is loaded or available

        // Generate a reference number like NO-26-01
        $refNo = 'NO-' . date('y', strtotime($announcement->created_at)) . '-' . str_pad($announcement->id, 2, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('pages.hrm.announcements.pdf', compact('announcement', 'company', 'refNo'));

        return $pdf->download($refNo . '.pdf');
    }

    /**
     * Resolve the list of recipient employees based on the send_to_type field,
     * then dispatch email and FCM notifications to each one.
     */
    private function sendNotificationsToRecipients(Announcement $announcement): void
    {
        if (!$announcement->send_to_type) {
            return;
        }

        $companyId = $announcement->company_id;
        $recipients = collect();
        if ($announcement->send_to_type === 'department' && !empty($announcement->send_to_department_id)) {
            // All active employees in the selected departments
            $recipients = Employee::where('company_id', $companyId)
                ->whereIn('department_id', (array)$announcement->send_to_department_id)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->get();
        } elseif ($announcement->send_to_type === 'reporting_to' && !empty($announcement->send_to_employee_id)) {
            $recipients = Employee::where('company_id', $companyId)
                ->whereIn('id', (array)$announcement->send_to_employee_id)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->get();
        }

        if (!$announcement->relationLoaded('company')) {
            $announcement->load('company');
        }
        $companyName = $announcement->company->name ?? loggedCompanyId()['company_name'] ?? 'Safe Solutions';
        foreach ($recipients as $employee) {
            // Send Email
            if ($employee->email) {
                try {
                    Mail::to($employee->email)->send(new AnnouncementMail($announcement, $employee->full_name, $companyName));
                } catch (\Exception $e) {
                    Log::warning("Announcement email failed for employee {$employee->id}: " . $e->getMessage());
                }
            }

            // Send FCM Push Notification
            if ($employee->fcm_token) {
                try {
                    FcmService::sendNotification($employee->fcm_token, [
                        'title' => $announcement->title,
                        'body' => \Illuminate\Support\Str::limit(strip_tags($announcement->content), 120),
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Announcement FCM failed for employee {$employee->id}: " . $e->getMessage());
                }
            }
        }
    }
}
