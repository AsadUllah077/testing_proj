<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticDocument;
use App\Models\LogisticFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $items = $this->getFolderContents(null);

        return view('pages.products.logistics.document.index', compact('items'));
    }

    public function getFolder(Request $request)
    {
        $folderId = $request->query('folder_id') ?: null;
        $items = $this->getFolderContents($folderId);
        $breadcrumb = $this->buildBreadcrumb($folderId);

        return response()->json([
            'items' => $items,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function createFolder(Request $request)
    {
        $validated = $request->validate([
            'folder_name' => 'required|string|max:255',
            'parent_id' => 'nullable',
        ]);

        LogisticFolder::create([
            'name' => $validated['folder_name'],
            'parent_id' => ! empty($validated['parent_id']) ? $validated['parent_id'] : null,
        ]);

        return response()->json(['success' => true]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:10240',
            'folder_id' => 'nullable',
            'booking_id' => 'nullable|string',
            'document_type' => 'nullable|string',
        ]);

        $folderId = $request->input('folder_id') ?: null;
        $path = 'lc-documents'; // Default path

        // Automated folder structure logic
        if ($request->filled('booking_id') && $request->filled('document_type')) {
            $bookingId = $request->input('booking_id');
            $docType = $request->input('document_type');
            $date = now()->format('Y-m-d');

            // 1. Booking Folder (Database)
            $bookingFolder = LogisticFolder::firstOrCreate([
                'name' => $bookingId,
                'parent_id' => null,
            ]);

            // 2. Document Type Folder (Database)
            $typeFolder = LogisticFolder::firstOrCreate([
                'name' => $docType,
                'parent_id' => $bookingFolder->id,
            ]);

            // 3. Date Folder (Database)
            $dateFolder = LogisticFolder::firstOrCreate([
                'name' => $date,
                'parent_id' => $typeFolder->id,
            ]);

            $folderId = $dateFolder->id;

            // Structured filesystem path
            $bookingIdSlug = \Illuminate\Support\Str::slug($bookingId);
            $docTypeSlug = \Illuminate\Support\Str::slug($docType);
            $path = "logistics/trade-documents/{$bookingIdSlug}/{$docTypeSlug}/{$date}";
        }

        $filePath = Storage::disk('public')->putFile($path, $file);

        LogisticDocument::create([
            'name' => $file->getClientOriginalName(),
            'path' => $filePath,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'folder_id' => $folderId,
        ]);

        return response()->json(['success' => true]);
    }

    public function download($id)
    {
        $document = LogisticDocument::findOrFail($id);

        return Storage::disk('public')->download($document->path, $document->name);
    }

    public function delete($id)
    {
        $document = LogisticDocument::findOrFail($id);
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return response()->json(['success' => true]);
    }

    public function deleteFolder($id)
    {
        $folder = LogisticFolder::findOrFail($id);
        $this->deleteAllContents($folder);
        $folder->delete();

        return response()->json(['success' => true]);
    }

    private function deleteAllContents($folder)
    {
        foreach ($folder->documents as $doc) {
            Storage::disk('public')->delete($doc->path);
            $doc->delete();
        }

        foreach ($folder->subfolders as $subfolder) {
            $this->deleteAllContents($subfolder);
            $subfolder->delete();
        }
    }

    private function getFolderContents($folderId)
    {
        $folders = LogisticFolder::where('parent_id', $folderId)
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'type' => 'folder',
                'name' => $f->name,
                'count' => $f->documents()->count() + $f->subfolders()->count(),
                'modified' => $f->updated_at->format('M d, Y'),
            ])
            ->values()
            ->toArray();

        $documents = LogisticDocument::where('folder_id', $folderId)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'type' => 'document',
                'name' => $d->name,
                'size' => $this->formatBytes($d->size),
                'icon' => $this->getFileIcon($d->mime_type),
                'modified' => $d->updated_at->format('M d, Y'),
            ])
            ->values()
            ->toArray();

        return array_merge($folders, $documents);
    }

    private function buildBreadcrumb($folderId)
    {
        $breadcrumb = [];
        $current = $folderId;

        while ($current) {
            $folder = LogisticFolder::find($current);
            if (! $folder) {
                break;
            }

            array_unshift($breadcrumb, [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);

            $current = $folder->parent_id;
        }

        return $breadcrumb;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    private function getFileIcon($mimeType)
    {
        return match (true) {
            str_contains($mimeType, 'pdf') => 'pdf',
            str_contains($mimeType, 'word') => 'word',
            str_contains($mimeType, 'excel') => 'excel',
            str_contains($mimeType, 'image') => 'image',
            default => 'pdf',
        };
    }
}
