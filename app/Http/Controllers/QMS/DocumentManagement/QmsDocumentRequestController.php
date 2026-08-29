<?php

namespace App\Http\Controllers\QMS\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Models\QmsDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QmsDocumentRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'requested_by' => 'required|string|max:255',
        ]);

        QmsDocumentRequest::create([
            'title'        => $request->title,
            'description'  => $request->description,
            'requested_by' => $request->requested_by,
            'status'       => 'open',
            'due_date'     => $request->due_date ?: null,
            'assigned_to'  => $request->assigned_to ?: null,
            'company_id'   => Auth::user()->company_id,
            'created_by'   => Auth::id(),
        ]);

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'requests'])
            ->with('success', 'Request submitted.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,in_review,completed']);

        $docRequest = QmsDocumentRequest::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $docRequest->update(['status' => $request->status]);

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'requests'])
            ->with('success', 'Request status updated.');
    }

    public function destroy($id)
    {
        $docRequest = QmsDocumentRequest::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $docRequest->delete();

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'requests'])
            ->with('success', 'Request deleted.');
    }
}
