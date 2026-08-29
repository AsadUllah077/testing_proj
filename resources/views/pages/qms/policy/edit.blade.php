@extends('layouts.auth')

@section('title', 'Edit Policy')

@section('content')
    <div class="mt-4 px-3">

        {{-- Page Header --}}
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-500 mb-1.5">Governance Revision Protocol</p>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('qms.policy.show', $policy->id) }}" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Modify Policy Framework</h1>
                        <p class="text-xs font-bold text-gray-400 mt-0.5">{{ $policy->policy_number }} · v{{ $policy->version }}</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="saveDraft()" class="qms-btn qms-btn-secondary shadow-sm">Save Draft</button>
                    <button type="button" onclick="document.getElementById('formSubmit').requestSubmit()" class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100">Publish Revision</button>
                </div>
            </div>
        </div>

        <form id="formSubmit"
              method="POST"
              action_url="{{ route('qms.policy.update', $policy->id) }}"
              back_url="{{ route('qms.policy.show', $policy->id) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Title & Context --}}
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Identity & Designation</div>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label !mb-1.5">Primary Policy Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="{{ old('title', $policy->title) }}" 
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" required />
                            </div>
                            <div>
                                <label class="form-label">Responsible Department</label>
                                <select name="department" class="filter-select !py-2.5">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->department }}" {{ old('department', $policy->department) == $dept->department ? 'selected' : '' }}>
                                            {{ $dept->department }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Rich Text Editor --}}
                    <div class="qms-card">
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-50">
                            <div class="qms-card-title !mb-0">Policy Core Documentation</div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="execCmd('bold')" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600"><b>B</b></button>
                                <button type="button" onclick="execCmd('italic')" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600"><i>I</i></button>
                                <button type="button" onclick="execCmd('underline')" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600"><u>U</u></button>
                                <div class="w-px h-4 bg-gray-200 mx-2"></div>
                                <button type="button" onclick="execCmd('insertUnorderedList')" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">List</button>
                            </div>
                        </div>
                        
                        <div id="contentEditor" 
                             contenteditable="true" 
                             class="min-h-[400px] prose max-w-none text-sm text-gray-800 focus:outline-none p-2">{!! old('content', $policy->content) !!}</div>
                        <input type="hidden" name="content" id="contentHidden" value="{{ old('content', $policy->content) }}" />
                        
                        <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                            <div class="flex gap-3">
                                <span class="text-lg">⚠️</span>
                                <div class="text-xs text-amber-800 leading-relaxed font-medium">
                                    <strong>Versioning Notice:</strong> Modifying the policy content will automatically increment to the next major version (e.g., v{{ $policy->version }} → v{{ ((int)explode('.', $policy->version)[0] + 1) }}.0) and restart the approval workflow.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Existing Attachments --}}
                    @if($policy->attachments->count() > 0)
                    <div class="qms-card">
                        <div class="qms-card-title mb-4">Managed Attachments</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($policy->attachments as $file)
                            <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-2xl bg-gray-50 group hover:border-indigo-200 transition-all">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">📄</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-gray-900 truncate">{{ $file->file_name }}</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">{{ round($file->file_size / 1024, 1) }} KB</p>
                                </div>
                                <button type="button" onclick="deleteAttachment({{ $file->id }}, this)" class="p-1.5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">🗑️</button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right: Classification & Status --}}
                <div class="space-y-6">
                    <div class="qms-card">
                        <h3 class="qms-card-title mb-6">Status & Priority</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="form-label uppercase tracking-widest !text-[10px] font-bold text-gray-400 mb-2">Publishing Status</label>
                                <select name="status" class="filter-select !font-bold">
                                    <option value="draft" {{ $policy->status === 'draft' ? 'selected' : '' }}>🔵 &nbsp; Draft Stage</option>
                                    <option value="under_review" {{ $policy->status === 'under_review' ? 'selected' : '' }}>🟠 &nbsp; Under Review</option>
                                    <option value="active" {{ $policy->status === 'active' ? 'selected' : '' }}>🟢 &nbsp; Active / Released</option>
                                    <option value="archived" {{ $policy->status === 'archived' ? 'selected' : '' }}>🔴 &nbsp; Archived Protocol</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label uppercase tracking-widest !text-[10px] font-bold text-gray-400 mb-3">Governance Priority</label>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach(['low' => 'Low', 'medium' => 'Mid', 'high' => 'High'] as $val => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="priority" value="{{ $val }}" class="sr-only priority-radio" {{ $policy->priority === $val ? 'checked' : '' }}>
                                            <div class="text-center py-2.5 rounded-xl border-2 text-[10px] font-bold uppercase tracking-wider transition-all
                                                {{ $policy->priority === $val ? 
                                                    ($val === 'high' ? 'bg-red-50 text-red-600 border-red-500 shadow-sm shadow-red-100' : ($val === 'medium' ? 'bg-amber-50 text-amber-600 border-amber-500 shadow-sm shadow-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-500 shadow-sm shadow-emerald-100')) : 
                                                    'bg-white text-gray-400 border-gray-100 hover:border-gray-300' }}">
                                                {{ $label }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="qms-card bg-indigo-50/50 border-indigo-100">
                        <h3 class="qms-card-title mb-4 !text-indigo-600">Update Media</h3>
                        <div class="border-2 border-dashed border-indigo-200 rounded-2xl p-6 text-center hover:bg-white hover:border-indigo-400 transition-all cursor-pointer group" onclick="document.getElementById('attachments').click()">
                            <span class="text-3xl mb-2 block group-hover:scale-110 transition-transform">📁</span>
                            <p class="text-xs font-bold text-gray-600">Drag files here</p>
                            <p class="text-[9px] text-gray-400 font-bold uppercase mt-1">PDF, DOCX up to 10MB</p>
                            <input id="attachments" name="attachments[]" type="file" multiple class="hidden" onchange="updateFileList(this)">
                        </div>
                        <div id="file-list" class="mt-4 px-2"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-submit.js') }}"></script>
<script>
    const form = document.getElementById('formSubmit');
    const editor = document.getElementById('contentEditor');
    const hiddenInput = document.getElementById('contentHidden');

    // Sync editor content to hidden input
    form.addEventListener('submit', () => {
        hiddenInput.value = editor.innerHTML;
    });

    function updateFileList(input) {
        const list = document.getElementById('file-list');
        list.innerHTML = '';
        if (input.files.length > 0) {
            Array.from(input.files).forEach(file => {
                list.innerHTML += `<div class="text-[10px] font-bold text-indigo-600 bg-white p-1.5 rounded-lg border border-indigo-50 mb-1 flex justify-between">
                    <span>📄 ${file.name}</span>
                    <span>${(file.size/1024).toFixed(1)} KB</span>
                </div>`;
            });
        }
    }

    function execCmd(cmd) {
        document.execCommand(cmd, false, null);
        editor.focus();
    }

    async function deleteAttachment(id, btn) {
        if(!confirm('Permanently remove this attachment?')) return;
        try {
            const res = await fetch(`/qms/policy/attachment/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if(data.success) { btn.closest('div').remove(); }
            else { alert(data.message || 'Error deleting'); }
        } catch (e) { alert('Network error'); }
    }

    function saveDraft() {
        form.querySelector('[name="status"]').value = 'draft';
        form.requestSubmit();
    }

    // Radio button UI sync
    document.querySelectorAll('.priority-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.priority-radio').forEach(r => {
                r.nextElementSibling.className = 'text-center py-2.5 rounded-xl border-2 text-[10px] font-bold uppercase tracking-wider transition-all bg-white text-gray-400 border-gray-100 hover:border-gray-300';
            });
            const val = this.value;
            const activeClass = val === 'high' ? 'bg-red-50 text-red-600 border-red-500 shadow-sm shadow-red-100' : (val === 'medium' ? 'bg-amber-50 text-amber-600 border-amber-500 shadow-sm shadow-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-500 shadow-sm shadow-emerald-100');
            this.nextElementSibling.className = 'text-center py-2.5 rounded-xl border-2 text-[10px] font-bold uppercase tracking-wider transition-all ' + activeClass;
        });
    });
</script>
@endpush
