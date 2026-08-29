@extends('layouts.auth')

@section('title', 'Edit Document: ' . $document->title)

@section('content')
    <div class="mt-4 px-3">

        {{-- Page Header --}}
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600 mb-1">Controlled Document Revision</p>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900">Edit: {{ $document->title }}</h1>
                <div class="flex gap-2">
                    <a href="{{ route('qms.documentmanagement.show', $document->id) }}"
                       class="qms-btn qms-btn-secondary">
                        Back to Details
                    </a>
                </div>
            </div>
        </div>

        <form id="formSubmit"
              method="POST"
              action="{{ route('qms.documentmanagement.update', $document->id) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Document Identity --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Document Identity</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Document Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="{{ $document->title }}" class="form-control" required />
                            </div>
                            <div>
                                <label class="form-label">Document Number (Read-only)</label>
                                <input type="text" value="{{ $document->doc_no }}" class="form-control bg-gray-50 cursor-not-allowed" readonly />
                            </div>
                        </div>
                    </div>

                    {{-- Document Content / Summary --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Document Summary & Purpose Update</div>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Brief Summary</label>
                                <textarea name="summary" rows="3" class="form-control !h-auto py-3">{{ $document->summary }}</textarea>
                                <p class="text-[10px] text-amber-600 mt-1 font-semibold">⚠️ Changing the summary will increment the document version.</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Purpose</label>
                                    <input type="text" name="purpose" value="{{ $document->purpose }}" class="form-control" />
                                </div>
                                <div>
                                    <label class="form-label">Retention Period</label>
                                    <input type="text" name="retention" value="{{ $document->retention }}" class="form-control" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Controlled Distribution --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Updated Distribution</div>
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @forelse($departments as $dept)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="distribution[]" value="{{ $dept }}"
                                               {{ in_array($dept, $document->controlled_distribution ?? []) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-600 group-hover:text-gray-900">{{ $dept }}</span>
                                    </label>
                                @empty
                                    <p class="col-span-4 text-xs text-gray-400">No departments found. Add departments in the HR module first.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-between items-center pt-4">
                        <button type="button" 
                                onclick="if(confirm('Are you sure you want to delete this document permanently?')) document.getElementById('deleteForm').submit();"
                                class="text-red-500 text-sm font-bold hover:underline">
                            🗑️ Delete Permanently
                        </button>
                        <div class="flex gap-3">
                            <button type="button" onclick="window.history.back()" class="qms-btn qms-btn-secondary !px-8">Cancel</button>
                            <button type="submit" class="qms-btn qms-btn-primary !px-10">Save Revision</button>
                        </div>
                    </div>
                </div>

                {{-- Right: Classification & Meta --}}
                <div class="space-y-6">
                    <div class="qms-card">
                        <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Core Classification</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Status</label>
                                <select name="status" class="filter-select">
                                    <option value="draft" {{ $document->status == 'draft' ? 'selected' : '' }}>Drafting</option>
                                    <option value="under_review" {{ $document->status == 'under_review' ? 'selected' : '' }}>Under Review</option>
                                    <option value="active" {{ $document->status == 'active' ? 'selected' : '' }}>Active / Released</option>
                                    <option value="archived" {{ $document->status == 'archived' ? 'selected' : '' }}>Archived</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Document Type</label>
                                <select name="type" class="filter-select">
                                    <option value="Policy" {{ $document->type == 'Policy' ? 'selected' : '' }}>Policy</option>
                                    <option value="SOP" {{ $document->type == 'SOP' ? 'selected' : '' }}>SOP</option>
                                    <option value="Form" {{ $document->type == 'Form' ? 'selected' : '' }}>Form</option>
                                    <option value="Template" {{ $document->type == 'Template' ? 'selected' : '' }}>Template</option>
                                    <option value="Record" {{ $document->type == 'Record' ? 'selected' : '' }}>Record</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Revision Cycle</label>
                                <select name="revision_cycle" class="filter-select">
                                    <option value="6 months" {{ $document->revision_cycle == '6 months' ? 'selected' : '' }}>Every 6 Months</option>
                                    <option value="12 months" {{ $document->revision_cycle == '12 months' ? 'selected' : '' }}>Annual (12 Months)</option>
                                    <option value="24 months" {{ $document->revision_cycle == '24 months' ? 'selected' : '' }}>Biennial (24 Months)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="qms-card">
                        <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Security Update</h3>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="is_confidential" class="w-4 h-4 rounded text-indigo-600" {{ $document->is_confidential ? 'checked' : '' }}>
                                <div>
                                    <div class="text-sm font-semibold">Confidential</div>
                                    <div class="text-[10px] text-gray-400">Lock to owners/approvers</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="require_acknowledgment" class="w-4 h-4 rounded text-indigo-600" {{ $document->require_acknowledgment ? 'checked' : '' }}>
                                <div>
                                    <div class="text-sm font-semibold">Acknowledge Read</div>
                                    <div class="text-[10px] text-gray-400">Force user signature</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Hidden Delete Form --}}
        <form id="deleteForm" action="{{ route('qms.documentmanagement.destroy', $document->id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
