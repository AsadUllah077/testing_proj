@extends('layouts.auth')

@section('title', 'Draft New Controlled Document')

@section('content')
    <div class="mt-4 px-3">

        {{-- Page Header --}}
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#6378ff] mb-1">Quality Management Protocol</p>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900">Draft New Controlled Document</h1>
                <div class="flex gap-2">
                    <a href="{{ route('qms.documentmanagement.index') }}"
                       class="qms-btn qms-btn-secondary">
                        Back to Register
                    </a>
                </div>
            </div>
        </div>

        <form id="formSubmit"
              method="POST"
              action_url="{{ route('qms.documentmanagement.store') }}"
              back_url="{{ route('qms.documentmanagement.index') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Document Identity --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Document Identity</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Document Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" placeholder="e.g., Quality Manual - Section A" class="form-control" required />
                            </div>
                            <div>
                                <label class="form-label">Document Number <span class="text-red-500">*</span></label>
                                <input type="text" name="doc_no" placeholder="e.g., DOC-2025-001" class="form-control" required />
                            </div>
                        </div>
                    </div>

                    {{-- Document Content / Summary --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Document Summary & Purpose</div>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Brief Summary</label>
                                <textarea name="summary" rows="3" class="form-control !h-auto py-3" placeholder="Describe the scope and intent of this document..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Purpose</label>
                                    <input type="text" name="purpose" class="form-control" placeholder="Primary objective..." />
                                </div>
                                <div>
                                    <label class="form-label">Retention Period</label>
                                    <input type="text" name="retention" class="form-control" placeholder="e.g., 5 years / Permanent" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Controlled Distribution --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Controlled Distribution</div>
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                            <p class="text-[11px] text-gray-500 mb-3 font-semibold uppercase tracking-wider">Select departments with access</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @forelse($departments as $dept)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="distribution[]" value="{{ $dept }}" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">{{ $dept }}</span>
                                    </label>
                                @empty
                                    <p class="col-span-4 text-xs text-gray-400">No departments found. Add departments in the HR module first.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- File Upload --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">File Upload</div>
                        <div id="drop-zone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-indigo-400 transition-colors bg-gray-50/50 group">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-indigo-400 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label class="relative cursor-pointer bg-transparent rounded-md font-semibold text-indigo-600 hover:text-indigo-500">
                                        <span>Click to upload file</span>
                                        <input id="file-upload" name="document_file" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-400">PDF, DOC, DOCX up to 20MB</p>
                                <div id="file-name" class="mt-2 text-xs font-semibold text-indigo-600"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" class="qms-btn qms-btn-secondary !px-8">Cancel</button>
                        <button type="submit" class="qms-btn qms-btn-primary !px-10">Create Controlled Document</button>
                    </div>
                </div>

                {{-- Right: Classification & Meta --}}
                <div class="space-y-6">
                    <div class="qms-card">
                        <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Classification</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Document Type</label>
                                <select name="type" class="filter-select">
                                    <option value="Policy">Policy</option>
                                    <option value="SOP">SOP</option>
                                    <option value="Form">Form</option>
                                    <option value="Template">Template</option>
                                    <option value="Record">Record</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Revision Cycle</label>
                                <select name="revision_cycle" class="filter-select">
                                    <option value="6 months">Every 6 Months</option>
                                    <option value="12 months" selected>Annual (12 Months)</option>
                                    <option value="24 months">Biennial (24 Months)</option>
                                    <option value="No review">One-time / Legacy</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Document Owner</label>
                                <select name="owner_id" class="filter-select">
                                    <option value="">Select owner…</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="qms-card">
                        <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Security & Control</h3>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="is_confidential" class="w-4 h-4 rounded text-indigo-600">
                                <div>
                                    <div class="text-sm font-semibold">Confidential</div>
                                    <div class="text-[10px] text-gray-400">Restricted to owners/approvers</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" checked name="require_acknowledgment" class="w-4 h-4 rounded text-indigo-600">
                                <div>
                                    <div class="text-sm font-semibold">Acknowledgment Required</div>
                                    <div class="text-[10px] text-gray-400">Users must sign upon reading</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Hints --}}
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3 text-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-bold uppercase">Document Control Tip</span>
                        </div>
                        <p class="text-xs text-indigo-600 leading-relaxed">
                            Controlled documents are automatically versioned. Uploading a new file to an existing document entry will increment the minor version (e.g., v1.1 → v1.2).
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const fileUpload = document.getElementById('file-upload');
    const fileNameDisplay = document.getElementById('file-name');
    const dropZone = document.getElementById('drop-zone');

    fileUpload.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileNameDisplay.textContent = '📄 ' + this.files[0].name;
            dropZone.classList.add('border-indigo-400', 'bg-indigo-50/50');
        }
    });

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    dropZone.addEventListener('dragover', () => dropZone.classList.add('border-indigo-400', 'bg-indigo-50/50'));
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-indigo-400', 'bg-indigo-50/50'));
    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileUpload.files = files;
            fileNameDisplay.textContent = '📄 ' + files[0].name;
            dropZone.classList.remove('border-indigo-400', 'bg-indigo-50/50');
            dropZone.classList.add('border-green-400', 'bg-green-50/50');
        }
    });
</script>
@endpush
