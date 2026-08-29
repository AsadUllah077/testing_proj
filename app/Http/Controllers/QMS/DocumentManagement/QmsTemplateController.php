<?php

namespace App\Http\Controllers\QMS\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Models\QmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QmsTemplateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'name'     => 'required|string|max:255',
            'file'     => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('qms/templates', 'public');

        QmsTemplate::create([
            'category'   => $request->category,
            'name'       => $request->name,
            'file_path'  => $path,
            'file_name'  => $file->getClientOriginalName(),
            'file_size'  => $file->getSize(),
            'mime_type'  => $file->getMimeType(),
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'templates'])
            ->with('success', 'Template uploaded successfully.');
    }

    public function destroy($id)
    {
        $template = QmsTemplate::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $template->delete();

        return redirect()->route('qms.documentmanagement.index', ['tab' => 'templates'])
            ->with('success', 'Template deleted.');
    }

    public function download($id)
    {
        $template = QmsTemplate::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return Storage::disk('public')->download($template->file_path, $template->file_name);
    }
}
