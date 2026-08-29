<?php

namespace App\Http\Controllers\QMS\DocumentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\QmsDocument;
use App\Models\QmsDocumentAttachment;
use App\Models\QmsTemplate;
use App\Models\QmsExternalDocument;
use App\Models\QmsDocumentRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $status = $request->input('status');
        $type   = $request->input('type');

        $query = QmsDocument::with(['owner', 'creator'])
            ->where('company_id', Auth::user()->company_id)
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                  ->orWhere('doc_no', 'LIKE', "%$search%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $documents = $query->paginate(20);

        $stats = [
            'controlled' => QmsDocument::where('company_id', Auth::user()->company_id)->count(),
            'pending'    => QmsDocument::where('company_id', Auth::user()->company_id)->where('status', 'under_review')->count(),
            'approved'   => QmsDocument::where('company_id', Auth::user()->company_id)->where('status', 'active')->count(),
            'archived'   => QmsDocument::where('company_id', Auth::user()->company_id)->where('status', 'archived')->count(),
        ];

        $templates = QmsTemplate::where('company_id', Auth::user()->company_id)
            ->orderBy('category')->orderBy('name')
            ->get()
            ->groupBy('category');

        $externalDocs = QmsExternalDocument::with('owner')
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('ref_no')
            ->get();

        $requests = QmsDocumentRequest::with('assignee')
            ->where('company_id', Auth::user()->company_id)
            ->latest()
            ->get()
            ->groupBy('status');

        $users = User::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $activeTab = $request->input('tab', 'register');

        return view('pages.qms.documentmanagement.index', compact(
            'documents', 'stats', 'templates', 'externalDocs', 'requests', 'users', 'activeTab'
        ));
    }

    public function create()
    {
        $departments = Department::where('company_id', Auth::user()->company_id)
            ->orderBy('department')
            ->pluck('department');

        $users = User::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('pages.qms.documentmanagement.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'  => 'required|string|max:255',
            'doc_no' => 'required|string|unique:qms_documents,doc_no',
            'type'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $document = QmsDocument::create([
            'doc_no'                  => $request->doc_no,
            'title'                   => $request->title,
            'summary'                 => $request->summary,
            'type'                    => $request->type,
            'status'                  => 'draft',
            'revision_cycle'          => $request->revision_cycle,
            'purpose'                 => $request->purpose,
            'retention'               => $request->retention,
            'company_id'              => Auth::user()->company_id,
            'owner_id'                => $request->owner_id,
            'created_by'              => Auth::id(),
            'controlled_distribution' => $request->distribution ?? [],
            'is_confidential'         => $request->has('is_confidential'),
            'require_acknowledgment'  => $request->has('require_acknowledgment'),
        ]);

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $path = $file->store('qms/documents', 'public');
            
            QmsDocumentAttachment::create([
                'document_id' => $document->id,
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'file_size'   => $file->getSize(),
                'mime_type'   => $file->getMimeType(),
                'company_id'  => Auth::user()->company_id,
                'created_by'  => Auth::id(),
            ]);
        }

        return redirect()->route('qms.documentmanagement.index')->with('success', 'Controlled document created successfully.');
    }

    public function show($id)
    {
        $document = QmsDocument::with(['owner', 'creator', 'approver', 'attachments.creator'])
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $logs = ActivityLog::with('user')
            ->where('loggable_type', QmsDocument::class)
            ->where('loggable_id', $document->id)
            ->latest()
            ->get();

        return view('pages.qms.documentmanagement.show', compact('document', 'logs'));
    }
    public function edit($id)
    {
        $document = QmsDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $departments = Department::where('company_id', Auth::user()->company_id)
            ->orderBy('department')
            ->pluck('department');

        $users = User::where('company_id', Auth::user()->company_id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('pages.qms.documentmanagement.edit', compact('document', 'departments', 'users'));
    }

    public function update(Request $request, $id)
    {
        $document = QmsDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $oldStatus = $document->status;
        $newStatus = $request->status ?? $oldStatus;

        // Versioning logic: increment minor if title or summary changes
        $version = $document->version ?: '1.0';
        if ($document->title !== $request->title || $document->summary !== $request->summary) {
            $parts = explode('.', $version);
            $major = (int)($parts[0] ?? 1);
            $minor = (int)($parts[1] ?? 0);
            $minor++;
            if ($minor > 9) { $major++; $minor = 0; }
            $version = "$major.$minor";
        }

        $approvedAt = $document->approved_at;
        $approvedBy = $document->approved_by;
        if ($oldStatus !== 'active' && $newStatus === 'active') {
            $approvedAt = now();
            $approvedBy = Auth::id();
        }

        $document->update([
            'title'                   => $request->title,
            'summary'                 => $request->summary,
            'type'                    => $request->type,
            'status'                  => $newStatus,
            'revision_cycle'          => $request->revision_cycle,
            'purpose'                 => $request->purpose,
            'retention'               => $request->retention,
            'owner_id'                => $request->owner_id,
            'version'                 => $version,
            'approved_at'             => $approvedAt,
            'approved_by'             => $approvedBy,
            'controlled_distribution' => $request->distribution ?? [],
            'is_confidential'         => $request->has('is_confidential'),
            'require_acknowledgment'  => $request->has('require_acknowledgment'),
        ]);

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $path = $file->store('qms/documents', 'public');
            
            QmsDocumentAttachment::create([
                'document_id' => $document->id,
                'file_name'   => $file->getClientOriginalName(),
                'file_path'   => $path,
                'file_size'   => $file->getSize(),
                'mime_type'   => $file->getMimeType(),
                'company_id'  => Auth::user()->company_id,
                'created_by'  => Auth::id(),
            ]);
        }

        return redirect()->route('qms.documentmanagement.show', $document->id)->with('success', 'Document updated successfully.');
    }

    public function destroy($id)
    {
        $document = QmsDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $document->delete();

        return redirect()->route('qms.documentmanagement.index')->with('success', 'Document deleted successfully.');
    }
}



