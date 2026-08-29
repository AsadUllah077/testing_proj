<?php

namespace App\Http\Controllers\QMS\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Models\QmsExternalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QmsExternalDocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title'  => 'required|string|max:255',
            'source' => 'required|string|max:255',
        ]);

        $count = QmsExternalDocument::where('company_id', Auth::user()->company_id)
            ->withTrashed()->count() + 1;
        $refNo = 'EXT-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filePath = $file->store('qms/external', 'public');
            $fileName = $file->getClientOriginalName();
        }

        QmsExternalDocument::create([
            'ref_no'      => $refNo,
            'title'       => $request->title,
            'source'      => $request->source,
            'status'      => $request->status ?? 'current',
            'expiry_date' => $request->expiry_date ?: null,
            'owner_id'    => $request->owner_id ?: null,
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'company_id'  => Auth::user()->company_id,
            'created_by'  => Auth::id(),
        ]);

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'external'])
            ->with('success', 'External document added.');
    }

    public function destroy($id)
    {
        $doc = QmsExternalDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $doc->delete();

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'external'])
            ->with('success', 'External document removed.');
    }

    public function download($id)
    {
        $doc = QmsExternalDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return Storage::disk('public')->download($doc->file_path, $doc->file_name);
    }
}
